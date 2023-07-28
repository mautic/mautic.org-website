<?php

namespace Drupal\dropsolid_purge;

/**
 * Describes technical information accessors for your environment.
 */
/**
 * Interface HostingInfoFactoryInterface
 * @package Drupal\dropsolid_purge
 */
interface HostingInfoFactoryInterface {

  /**
   * Get site environment
   *
   * @return string
   */
  public function getSiteEnvironment();

  /**
   * Get site name
   *
   * @return string
   */
  public function getSiteName();

  /**
   * Get site group
   *
   * @return string
   */
  public function getSiteGroup();

  /**
   * Get Loadbalancers
   *
   * @return array
   */
  public function getLoadBalancers();

  /**
   * Get the config for the loadbalancers
   *
   * @return array
   */
  public function getLoadBalancersConfig();

}
