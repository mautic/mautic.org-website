<?php

namespace Drupal\quicktabs\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides block plugin definitions for quicktabs blocks.
 *
 * @see \Drupal\mymodule\Plugin\Block\QuickTabsBlock
 */
class QuickTabsBlock extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (\Drupal::entityTypeManager()->getStorage('quicktabs_instance')->loadMultiple() as $machine_name => $entity) {
      $this->derivatives[$machine_name] = $base_plugin_definition;
      $this->derivatives[$machine_name]['admin_label'] = 'QuickTabs - ' . $entity->label();
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
