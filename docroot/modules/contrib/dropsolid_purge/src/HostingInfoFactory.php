<?php
/**
 * Created by PhpStorm.
 * User: nielsaers
 * Date: 20/03/2018
 * Time: 11:28
 */

namespace Drupal\dropsolid_purge;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\dropsolid_purge\HostingInfoFactoryInterface;


/**
 * Class HostingInfoFactory
 * @package Drupal\dropsolid_purge
 */
class HostingInfoFactory implements HostingInfoFactoryInterface {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $settings;


  /**
   * HostingInfoFactory constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  function __construct(ConfigFactoryInterface $configFactory) {
    $this->settings = $configFactory->get('dropsolid_purge.config');
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteEnvironment() {
    if ($this->settings->get('site_environment')) {
      return $this->settings->get('site_environment');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteName() {
    if ($this->settings->get('site_name')) {
      return $this->settings->get('site_name');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteGroup() {
    if ($this->settings->get('site_group')) {
      return $this->settings->get('site_group');
    }
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   */
  public function getLoadBalancers() {

    $loadBalancers = [];

    if($this->settings->get('loadbalancers')){
      foreach ($this->settings->get('loadbalancers') as $name => $loadbalancer) {
        // Build the loadbalancer uri
        if($loadbalancer['ip'] && !empty($loadbalancer['ip'])){
          $loadbalancerUri = ($loadbalancer['protocol']) ? $loadbalancer['protocol'] : 'http';
          $loadbalancerUri .= '://';
          $loadbalancerUri .= $loadbalancer['ip'];
          if (isset($loadbalancer['port']) && !empty($loadbalancer['port'])) {
            $loadbalancerUri .= ':' . $loadbalancer['port'];
          }
        }

        // Only add the url if it is complete and exists
        if(isset($loadbalancerUri)){
          $loadBalancers[$name] = $loadbalancerUri;
        }
      }
    }

    return $loadBalancers;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   */
  public function getLoadBalancersConfig() {
    if ($this->settings->get('loadbalancers')) {
      return $this->settings->get('loadbalancers');
    }
  }
}