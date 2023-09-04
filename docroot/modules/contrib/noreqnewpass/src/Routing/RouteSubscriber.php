<?php

namespace Drupal\noreqnewpass\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Always deny access to '/user/logout'.
    // Note that the second parameter of setRequirement() is a string.
    if ($route = $collection->get('user.pass')) {
      $noreqnewpass_disable = \Drupal::config('noreqnewpass.settings_form')->get('noreqnewpass_disable');
      if ($noreqnewpass_disable) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

}
