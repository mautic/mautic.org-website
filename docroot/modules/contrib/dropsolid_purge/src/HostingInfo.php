<?php

namespace Drupal\dropsolid_purge;

use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\dropsolid_purge\HostingInfoInterface;
use Drupal\dropsolid_purge\Hash;
use Drupal\dropsolid_purge\HostingInfoFactoryInterface;

/**
 * Provides technical information accessors for your environment.
 */
class HostingInfo implements HostingInfoInterface {

  /**
   * The load balancer IP adresses installed in front of this site.
   *
   * @var string[]
   */
  protected $balancerAddresses = [];

  /**
   * The token used to authenticate cache invalidations with
   *
   * @var string
   */
  protected $balancerToken = '';

  /**
   * Your site environment.
   *
   * @var string
   */
  protected $siteEnvironment = '';

  /**
   * Your site group.
   *
   * @var string
   */
  protected $siteGroup = '';

  /**
   * Unique identifier for this site.
   *
   * @var string
   */
  protected $siteIdentifier = '';

  /**
   * Your site name.
   *
   * @var string
   */
  protected $siteName = '';

  /**
   * The Drupal site path.
   *
   * @var string
   */
  protected $sitePath = '';

  /**
   * @var \Drupal\dropsolid_purge\HostingInfoInterface
   */
  private $hostingInfoFactory;

  /**
   * Constructs a HostingInfo object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal site settings object.
   * @param \Drupal\dropsolid_purge\HostingInfoFactoryInterface $hostingInfoFactory
   *   HostingInfoFactory
   */
  public function __construct(RequestStack $request_stack, Settings $settings, HostingInfoFactoryInterface $hostingInfoFactory) {

    // Generate the Drupal sitepath by querying the SitePath from this request.
    // This is to cover a Drupal multisite environment (e.g sites/default)
    $this->sitePath = DrupalKernel::findSitePath($request_stack->getCurrentRequest());

    // Get the loadbalancers from the settings.
    $this->balancerAddresses = $hostingInfoFactory->getLoadBalancers();

    $this->siteEnvironment = $hostingInfoFactory->getSiteEnvironment();
    $this->siteName = $hostingInfoFactory->getSiteName();
    $this->siteGroup = $hostingInfoFactory->getSiteGroup();

    // Determine the authentication token is going to be, usually the site name.
    $this->balancerToken = $this->siteName;
    if (is_string($token = $settings->get('dropsolid_purge_token'))) {
      if ($token) {
        $this->balancerToken = $token;
      }
    }

    // Use the sitename and site path directory as site identifier.
    $this->siteIdentifier = Hash::siteIdentifier(
      $this->siteName,
      $this->sitePath,
      $this->siteEnvironment,
      $this->siteGroup
    );

    $this->hostingInfoFactory = $hostingInfoFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalancerAddresses() {
    return $this->balancerAddresses;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalancerToken() {
    return $this->balancerToken;
  }

  /**
  * {@inheritdoc}
  */
  public function getSiteEnvironment() {
    return $this->siteEnvironment;
  }

  /**
  * {@inheritdoc}
  */
  public function getSiteGroup() {
    return $this->siteGroup;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteIdentifier() {
    return $this->siteIdentifier;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteName() {
    return $this->siteName;
  }

  /**
   * {@inheritdoc}
   */
  public function getSitePath() {
    return $this->sitePath;
  }

}
