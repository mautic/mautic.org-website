<?php

namespace Drupal\dropsolid_purge\Plugin\Purge\Purger;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise;
use GuzzleHttp\Pool;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\dropsolid_purge\HostingInfoInterface;
use Drupal\dropsolid_purge\Hash;

/**
 * Dropslid purger.
 *
 * @PurgePurger(
 *   id = "dropsolid_purge",
 *   label = @Translation("Dropsolid Varnish Purge"),
 *   configform = "",
 *   cooldown_time = 0.2,
 *   description = @Translation("Invalidates varnish for Dropsolid"),
 *   multi_instance = FALSE,
 *   types = {"tag", "everything"},
 * )
 */

// Add support for the following types in the future
// types = {"url", "wildcardurl", "tag", "everything"},

class DropsolidPurger extends PurgerBase implements PurgerInterface {

  /**
   * Maximum number of requests to send concurrently.
   */
  const CONCURRENCY = 6;

  /**
   * Float describing the number of seconds to wait while trying to connect to
   * a server.
   */
  const CONNECT_TIMEOUT = 1.5;

  /**
   * Float describing the timeout of the request in seconds.
   */
  const TIMEOUT = 3.0;

  /**
   * Batches of cache tags are split up into multiple requests to prevent HTTP
   * request headers from growing too large or Varnish refusing to process them.
   */
  const TAGS_GROUPED_BY = 15;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Supporting variable for ::debug(), which keeps a call graph in it.
   *
   * @var string[]
   */
  protected $debug = [];

  /**
   * @var \Drupal\dropsolid_purge\HostingInfoInterface
   */
  protected $hostingInfo;

  /**
   * Constructs a DropsolidPurger object.
   *
   * @param \Drupal\dropsolid_purge\HostingInfoInterface $dropsolid_purge_hostinginfo
   *   Technical information accessors for your environment.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client that can perform remote requests.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(HostingInfoInterface $dropsolid_purge_hostinginfo, ClientInterface $http_client, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $http_client;
    $this->hostingInfo = $dropsolid_purge_hostinginfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('dropsolid_purge.hostinginfo'),
      $container->get('http_client'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Log the caller graph using $this->logger()->debug() messages.
   *
   * @param string $caller
   *   Name of the PHP method that is calling ::debug().
   */
  protected function debug($caller) {
    if (!$this->logger()->isDebuggingEnabled()) {
      return;
    }

    // Generate a caller name used both in logging and call counting.
    $caller = str_replace(
      $this->getClassName(__CLASS__),
      '',
      $this->getClassName($caller)
    );

    // Define a simple closure to print with prefixed indentation.
    $log = function($output) {
      $space = str_repeat('  ', count($this->debug));
      $this->logger()->debug($space . $output);
    };

    if (!in_array($caller, $this->debug)) {
      $this->debug[] = $caller;
      $log("--> $caller():");
    }
    else {
      unset($this->debug[array_search($caller, $this->debug)]);
      $log("      (finished)");
    }
  }

  /**
   * Extract debug information from a request.
   *
   * @param \Psr\Http\Message\RequestInterface $r
   *   The HTTP request object.
   *
   * @return string[]
   */
  protected function debugInfoForRequest(RequestInterface $r) {
    $info = [];
    $info['req http']   = $r->getProtocolVersion();
    $info['req uri']    = $r->getUri()->__toString();
    $info['req method'] = $r->getMethod();
    $info['req headers'] = [];
    foreach ($r->getHeaders() as $h => $v) {
      $info['req headers'][] = $h . ': ' . $r->getHeaderLine($h);
    }
    return $info;
  }

  /**
   * Extract debug information from a response.
   *
   * @param \Psr\Http\Message\ResponseInterface $r
   *   The HTTP response object.
   * @param \GuzzleHttp\Exception\RequestException $r
   *   Optional exception in case of failures.
   *
   * @return string[]
   */
  protected function debugInfoForResponse(ResponseInterface $r, RequestException $e = NULL) {
    $info = [];
    $info['rsp http'] = $r->getProtocolVersion();
    $info['rsp status'] = $r->getStatusCode();
    $info['rsp reason'] = $r->getReasonPhrase();
    if (!is_null($e)) {
      $info['rsp summary'] = json_encode($e->getResponseBodySummary($r));
    }
    $info['rsp headers'] = [];
    foreach ($r->getHeaders() as $h => $v) {
      $info['rsp headers'][] = $h . ': ' . $r->getHeaderLine($h);
    }
    return $info;
  }

  /**
   * Generate a short and readable class name.
   *
   * @param string|object $class
   *   Fully namespaced class or an instantiated object.
   *
   * @return string
   */
  protected function getClassName($class) {
    if (is_object($class)) {
      $class = get_class($class);
    }
    if ($pos = strrpos($class, '\\')) {
      $class = substr($class, $pos + 1);
    }
    return $class;
  }

  /**
   * Retrieve request options used for all of Dropsolid Purge's balancer requests.
   *
   * @param array[] $extra
   *   Associative array of options to merge onto the standard ones.
   *
   * @return array
   */
  protected function getGlobalOptions(array $extra = []) {
    $opt = [
      // Disable exceptions for 4XX HTTP responses, those aren't failures to us.
      'http_errors' => FALSE,

      // Prevent inactive balancers from sucking all runtime up.
      'connect_timeout' => self::CONNECT_TIMEOUT,

      // Prevent unresponsive balancers from making Drupal slow.
      'timeout' => self::TIMEOUT,

      // Deliberately disable SSL verification to prevent unsigned certificates
      // from breaking down a website when purging a https:// URL!
      'verify' => FALSE,

      // Trigger \Drupal\dropsolid_purge\Http\LoadBalancerMiddleware which acts as
      // honest broker by throwing the right exceptions for our bal requests.
      'dropsolid_purge_middleware' => TRUE,
    ];
    return array_merge($opt, $extra);
  }

  /**
   * Concurrently execute the given requests.
   *
   * @param string $caller
   *   Name of the PHP method that is executing the requests.
   * @param \Closure $requests
   *   Generator yielding requests which will be passed to \GuzzleHttp\Pool.
   */
  protected function getResultsConcurrently($caller, $requests) {
    $this->debug(__METHOD__);
    $results = [];

    // Create a concurrently executed Pool which collects a boolean per request.
    $pool = new Pool($this->client, $requests(), [
      'options' => $this->getGlobalOptions(),
      'concurrency' => self::CONCURRENCY,
      'fulfilled' => function($response, $result_id) use (&$results) {
        if ($this->logger()->isDebuggingEnabled()) {
          $this->debug(__METHOD__ . '::fulfilled');
          $this->logDebugTable($this->debugInfoForResponse($response));
        }
        $results[$result_id][] = TRUE;
      },
      'rejected' => function($reason, $result_id) use (&$results, $caller) {
        $this->debug(__METHOD__ . '::rejected');
        $this->logFailedRequest($caller, $reason);
        $results[$result_id][] = FALSE;
      },
    ]);

    // Initiate the transfers and create a promise.
    $promise = $pool->promise();

    // Force the pool of requests to complete.
    $promise->wait();

    $this->debug(__METHOD__);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    // The max amount of outgoing HTTP requests that can be made during script
    // execution time. Although always respected as outer limit, it will be lower
    // in practice as PHP resource limits (max execution time) bring it further
    // down. However, the maximum amount of requests will be higher on the CLI.
    $balancers = count($this->hostingInfo->getBalancerAddresses());
    if ($balancers) {
      return intval(ceil(200 / $balancers));
    }
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRuntimeMeasurement() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {

    // Since we implemented ::routeTypeToMethod(), this Latin preciousness
    // shouldn't ever occur and when it does, will be easily recognized.
    throw new \Exception("Malum consilium quod mutari non potest!");
  }

  /**
   * Invalidate a set of tag invalidations.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   */
  public function invalidateTags(array $invalidations) {
    $this->debug(__METHOD__);

    // Set invalidation states to PROCESSING. Detect tags with spaces in them,
    // as space is the only character Drupal core explicitely forbids in tags.
    foreach ($invalidations as $invalidation) {
      $tag = $invalidation->getExpression();
      if (strpos($tag, ' ') !== FALSE) {
        $invalidation->setState(InvalidationInterface::FAILED);
        $this->logger->error(
          "Tag '%tag' contains a space, this is forbidden.", ['%tag' => $tag]
        );
      }
      else {
        $invalidation->setState(InvalidationInterface::PROCESSING);
      }
    }

    // Create grouped sets of 12 so that we can spread out the BAN load.
    $group = 0;
    $groups = [];
    foreach ($invalidations as $invalidation) {
      if ($invalidation->getState() !== InvalidationInterface::PROCESSING) {
        continue;
      }
      if (!isset($groups[$group])) {
        $groups[$group] = ['tags' => [], ['objects' => []]];
      }
      if (count($groups[$group]['tags']) >= self::TAGS_GROUPED_BY) {
        $group++;
      }
      $groups[$group]['objects'][] = $invalidation;
      $groups[$group]['tags'][] = $invalidation->getExpression();
    }

    // Test if we have at least one group of tag(s) to purge, if not, bail.
    if (!count($groups)) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::FAILED);
      }
      return;
    }

    // Now create requests for all groups of tags.
    $site = $this->hostingInfo->getSiteIdentifier();
    $loadbalancers = $this->hostingInfo->getBalancerAddresses();
    $requests = function() use ($groups, $loadbalancers, $site) {
      foreach ($groups as $group_id => $group) {
        // We implode using a pipe because in the vcl we do a regex check
        $tags = implode('|', Hash::cacheTags($group['tags']));
        foreach ($loadbalancers as $loadbalancer) {
          yield $group_id => function($poolopt) use ($site, $tags, $loadbalancer) {
            $opt = [
              'headers' => [
                'X-Dropsolid-Purge' => $site,
                'X-Dropsolid-Purge-Tags' => $tags,
                'Accept-Encoding' => 'gzip',
                'User-Agent' => 'Dropsolid Purge',
              ]
            ];
            if (is_array($poolopt) && count($poolopt)) {
              $opt = array_merge($poolopt, $opt);
            }
            return $this->client->requestAsync('BAN', $loadbalancer, $opt);
          };
        }
      }
    };

    // Execute the requests generator and retrieve the results.
    $results = $this->getResultsConcurrently('invalidateTags', $requests);

    // Triage the results and set all invalidation states correspondingly.
    foreach ($groups as $group_id => $group) {
      if ((!isset($results[$group_id])) || (!count($results[$group_id]))) {
        foreach ($group['objects'] as $invalidation) {
          $invalidation->setState(InvalidationInterface::FAILED);
        }
      }
      else {
        if (in_array(FALSE, $results[$group_id])) {
          foreach ($group['objects'] as $invalidation) {
            $invalidation->setState(InvalidationInterface::FAILED);
          }
        }
        else {
          foreach ($group['objects'] as $invalidation) {
            $invalidation->setState(InvalidationInterface::SUCCEEDED);
          }
        }
      }
    }

    $this->debug(__METHOD__);
  }

  /**
   * Invalidate the entire website.
   *
   * This supports invalidation objects of the type 'everything'.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   */
  public function invalidateEverything(array $invalidations) {
    $this->debug(__METHOD__);

    // Set the 'everything' object(s) into processing mode.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
    }

    // Fetch the site identifier and start with a successive outcome.
    $overall_success = TRUE;

    // Synchronously request each balancer to wipe out everything for this site.
    foreach ($this->hostingInfo->getBalancerAddresses() as $loadbalancerUri) {
      try {
        $this->client->request('BAN', $loadbalancerUri , [
          'dropsolid_purge_middleware' => TRUE,
          'connect_timeout' => self::CONNECT_TIMEOUT,
          'http_errors' => FALSE,
          'timeout' => self::TIMEOUT,
          'headers' => [
            'X-Dropsolid-Purge-All' => TRUE,
            'X-Dropsolid-Purge' => $this->hostingInfo->getSiteIdentifier(),
            'Accept-Encoding' => 'gzip',
            'User-Agent' => 'Dropsolid Purge',
          ]
        ]);
      }
      catch (\Exception $e) {
        $this->logFailedRequest('invalidateEverything', $e);
        $overall_success = FALSE;
      }
    }

    // Set the object states according to our overall result.
    foreach ($invalidations as $invalidation) {
      if ($overall_success) {
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
      else {
        $invalidation->setState(InvalidationInterface::FAILED);
      }
    }

    $this->debug(__METHOD__);
  }

  /**
   * Render debugging information as table to $this->logger()->debug().
   *
   * @param mixed[] $table
   *   Associative array with each key being the row title. Each value can be
   *   a string, or when it is a array itself, the row will be repeated.
   * @param int $left
   *   Amount of characters that the left size of the table can be long.
   */
  protected function logDebugTable(array $table, $left = 15) {
    $longest_key = max(array_map('strlen', array_keys($table)));
    $logger = $this->logger();
    if ($longest_key > $left) {
      $left = $longest_key;
    }
    foreach ($table as $title => $value) {
      $spacing = str_repeat(' ', $left - strlen($title));
      $title = strtoupper($title) . $spacing . ' | ';
      if (is_array($value)) {
        foreach ($value as $repeated_value) {
          $logger->debug($title . $repeated_value);
        }
      }
      else {
        $logger->debug($title . $value);
      }
    }
  }

  /**
   * Write an error to the log for a failed request.
   *
   * @param string $caller
   *   Name of the PHP method that executed the request.
   * @param \Exception $e
   *   The exception thrown by Guzzle.
   */
  protected function logFailedRequest($caller, \Exception $e) {
    $msg = "::@caller() -> @class:";
    $vars = [
      '@caller' => $caller,
      '@class' => $this->getClassName($e),
      '@msg' => $e->getMessage(),
    ];

    // Add request information when this is present in the exception.
    if ($e instanceof ConnectException) {
      $vars['@msg'] = str_replace(
        '(see http://curl.haxx.se/libcurl/c/libcurl-errors.html)',
        '', $e->getMessage());
      $vars['@msg'] .= '; This is allowed to happen accidentally when load'
        . ' balancers are slow. However, if all cache invalidations fail, your'
        . ' queue may stall and you should debug the issue';
    }
    elseif ($e instanceof RequestException) {
      $req = $e->getRequest();
      $msg .= " HTTP @status; @method @uri;";
      $vars['@uri'] = $req->getUri();
      $vars['@method'] = $req->getMethod();
      $vars['@status'] = $e->hasResponse() ? $e->getResponse()->getStatusCode() : '???';
    }

    // Log the normal message to the emergency output stream.
    $this->logger()->critical("$msg @msg", $vars);

    // In debugging mode, follow with quite some more data.
    if ($this->logger()->isDebuggingEnabled()) {
      $table = ['exception' => get_class($e)];
      if ($e instanceof RequestException) {
        $table = array_merge($table, $this->debugInfoForRequest($e->getRequest()));
        $table['rsp'] = ($has_rsp = $e->hasResponse()) ? 'YES' : 'No response';
        if ($has_rsp && ($rsp = $e->getResponse())) {
          $table = array_merge($table, $this->debugInfoForResponse($rsp, $e));
        }
      }
      $this->logDebugTable($table);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function routeTypeToMethod($type) {
    $methods = [
      'tag'         => 'invalidateTags',
//      'url'         => 'invalidateUrls',
//      'wildcardurl' => 'invalidateWildcardUrls',
      'everything'  => 'invalidateEverything'
    ];
    return isset($methods[$type]) ? $methods[$type] : 'invalidate';
  }

}
