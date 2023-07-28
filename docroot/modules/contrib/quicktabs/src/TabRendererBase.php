<?php

namespace Drupal\quicktabs;

use Drupal\Component\Plugin\PluginBase;
use Drupal\quicktabs\Entity\QuickTabsInstance;

/**
 * Base implementation for plugins that render tabbed output.
 */
abstract class TabRendererBase extends PluginBase implements TabRendererInterface {

  /**
   * Gets the name of the plugin.
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function optionsForm(QuickTabsInstance $instance) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function render(QuickTabsInstance $instance);

}
