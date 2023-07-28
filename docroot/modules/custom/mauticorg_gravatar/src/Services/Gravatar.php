<?php

namespace Drupal\mauticorg_gravatar\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use GuzzleHttp\Exception\RequestException;

/**
 * Gravatar is a service for fetching url of gravatar image.
 */
class Gravatar {
  /**
   * HTTP client factory.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Gravatar constructor.
   *
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   Http client factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ClientFactory $http_client_factory, ConfigFactoryInterface $config_factory) {
    $this->client = $http_client_factory->fromOptions();
    $this->configFactory = $config_factory;
  }

  /**
   * Returns url of gravatar image.
   */
  public function getGravatarImageUrl($email) {
    $gravatar_url = $this->configFactory->get('mauticorg_gravatar.settings')->get('gravatar_url');
    $gravatar_url = trim($gravatar_url);
    $gravatar_url = str_replace('@email', md5($email), $gravatar_url);
    $is_valid_url = TRUE;
    try {
      $response = $this->client->get($gravatar_url, ['headers' => ['Accept' => 'image/*']]);
      // Checking response code.
      $code = $response->getStatusCode();
      if ($code != 200) {
        $is_valid_url = FALSE;
      }
      // Checking content type of response.
      $header_content_type = $response->getHeader('Content-Type')[0];
      if (!empty($header_content_type)) {
        if (strpos($header_content_type, "image/") === FALSE) {
          $is_valid_url = FALSE;
        }
      }
      else {
        $is_valid_url = FALSE;
      }

    }
    catch (RequestException $e) {
      // Logging error message.
      watchdog_exception('mauticorg_gravatar', $e);
      $is_valid_url = FALSE;
    }
    // Checking valid gravatar image.
    $gravatar_url = ($is_valid_url === TRUE) ? $gravatar_url : '';
    return $gravatar_url;
  }

}
