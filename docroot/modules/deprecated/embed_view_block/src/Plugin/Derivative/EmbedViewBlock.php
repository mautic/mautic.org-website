<?php

namespace Drupal\embed_view_block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\views\Views;

/**
 * Provides block plugin definitions for custom menus.
 *
 * @see \Drupal\embed_view_block\Plugin\Block\EmbedViewBlock
 */
class EmbedViewBlock extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $view_options = Views::getViewsAsOptions(TRUE, 'enabled', NULL, FALSE, TRUE);
    foreach ($view_options as $id => $label) {
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['admin_label'] = 'Embed View:' . $label;
      $this->derivatives[$id]['category'] = 'Embed View Block';
    }
    return $this->derivatives;
  }

}
