<?php

namespace Drupal\dropsolid_purge;

/**
 * Describes technical information accessors for your environment.
 */
interface HostingInfoInterface {

  /**
   * Get the load balancer IP adresses installed in front of this site.
   *
   * @return string[]
   *   Unassociative list of adresses in the form of 'I.P.V.4', or empty array.
   */
  public function getBalancerAddresses();

  /**
   * Get the token used to authenticate cache invalidations with.
   *
   * @return string[]
   *   Token string, e.g. 'oursecret' or 'sitedev'.
   */
  public function getBalancerToken();

  /**
   * Get the your site environment.
   *
   * @return string
   *   The site environment, e.g. 'dev'.
   */
  public function getSiteEnvironment();

  /**
   * Get the your site group.
   *
   * @return string
   *   The site group, e.g. 'site' or '' when unavailable.
   */
  public function getSiteGroup();

  /**
   * Get a unique identifier for your site.
   *
   * @return string
   *   Unique string for this Drupal instance, even within multisites!
   */
  public function getSiteIdentifier();

  /**
   * Get the your site name.
   *
   * @return string
   *   The site group, e.g. 'sitedev' or '' when unavailable.
   */
  public function getSiteName();

  /**
   * Get the Drupal site path.
   *
   * @return string
   *   The site path, e.g. 'site/default' or 'site/mysecondsite'.
   */
  public function getSitePath();

}
