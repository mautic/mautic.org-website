<?php

/**
 * @file
 * Post update hooks for Blazy.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\views\ViewEntityInterface;
use Drupal\blazy\BlazyDefault;

/**
 * Clear cache to enable CSP module support.
 */
function blazy_post_update_csp_support() {
  // Empty hook to clear caches.
}

/**
 * Changed grid type to string to support Native Grid for field formatters.
 */
function blazy_post_update_schema_formatter_grid_int_to_string(array &$sandbox = []) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (EntityViewDisplayInterface $display) {
    $needs_save = FALSE;
    foreach ($display->getComponents() as $field_name => &$component) {
      $config = isset($component['settings']) ? $component['settings'] : [];
      if (!isset($config['style'], $config['grid'], $config['grid_small'])) {
        continue;
      }

      foreach (BlazyDefault::gridBaseSettings() as $key => $value) {
        if (isset($config[$key])) {
          $saved_value = $config[$key];
          $component['settings'][$key] = empty($saved_value) ? '' : (string) $saved_value;
          $needs_save = TRUE;
        }
      }

      // Removed old deprecated/ unused formatter settings.
      foreach (['breakpoints', 'sizes', 'grid_header'] as $key) {
        if (isset($config[$key]) && empty($config[$key])) {
          unset($component['settings'][$key]);
          $needs_save = TRUE;
        }
      }

      if ($needs_save) {
        $display->setComponent($field_name, $component);
      }
    }

    return $needs_save;
  };

  $config_entity_updater->update($sandbox, 'entity_view_display', $callback);
}

/**
 * Changed grid type to string to support Native Grid for Views styles.
 */
function blazy_post_update_schema_view_grid_int_to_string(array &$sandbox = []) {
  if (!\Drupal::moduleHandler()->moduleExists('views')) {
    return;
  }

  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (ViewEntityInterface $view) {
    $needs_save = FALSE;
    $deps = $view->getDependencies() ?: [];

    if (!in_array('blazy', $deps['module'])) {
      return $needs_save;
    }

    foreach ($view->get('display') as &$display) {
      $style = isset($display['display_options'], $display['display_options']['style']) ? $display['display_options']['style'] : [];

      if (!isset($style['options'])) {
        continue;
      }

      $config = $style['options'];
      if (!isset($config['style'], $config['grid'], $config['grid_small'])) {
        continue;
      }

      foreach (BlazyDefault::gridBaseSettings() as $key => $value) {
        if (isset($config[$key])) {
          $saved_value = $config[$key];
          $display['display_options']['style']['options'][$key] = empty($saved_value) ? '' : (string) $saved_value;
          $needs_save = TRUE;
        }
      }
    }

    // Looks like ConfigEntityUpdater::update failed with View, manuallly save.
    if ($needs_save) {
      $view->save();
    }

    return $needs_save;
  };

  $config_entity_updater->update($sandbox, 'view', $callback);
}
