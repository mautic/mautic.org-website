<?php

namespace Drupal\quicktabs;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Base implementation for plugins that add tabbed output.
 */
abstract class TabTypeBase extends PluginBase implements TabTypeInterface {

  use DependencySerializationTrait;

  /**
   * Gets the name of the plugin.
   */
  protected function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function optionsForm(array $tab);

  /**
   * {@inheritdoc}
   */
  abstract public function render(array $tab);

}
