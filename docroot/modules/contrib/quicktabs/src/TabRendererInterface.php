<?php

namespace Drupal\quicktabs;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\quicktabs\Entity\QuickTabsInstance;

/**
 * Defines an interface for tab renderer plugins.
 */
interface TabRendererInterface extends PluginInspectionInterface {

  /**
   * Return form elements used on the edit/add from.
   *
   * @return array
   *   The options used for displaying tabs.
   */
  public function optionsForm(QuickTabsInstance $instance);

  /**
   * Return a render array for the whole Quick Tabs instance.
   *
   * @return array
   *   A render array.
   */
  public function render(QuickTabsInstance $instance);

}
