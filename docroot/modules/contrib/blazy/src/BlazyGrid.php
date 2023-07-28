<?php

namespace Drupal\blazy;

/**
 * Provides grid utilities.
 */
class BlazyGrid {

  /**
   * Returns items wrapped by theme_item_list(), can be a grid, or plain list.
   *
   * @param array $items
   *   The grid items being modified.
   * @param array $settings
   *   The given settings.
   *
   * @return array
   *   The modified array of grid items.
   */
  public static function build(array $items = [], array $settings = []) {
    $settings += BlazyDefault::htmlSettings() + BlazyDefault::gridSettings();
    $style = $settings['style'];
    $settings['_grid'] = $is_grid = isset($settings['_grid']) ? $settings['_grid'] : ($style && $settings['grid']);
    $item_class = $is_grid ? 'grid' : 'blazy__item';
    $settings['count'] = empty($settings['count']) ? count($items) : $settings['count'];

    $contents = [];
    foreach ($items as $key => $item) {
      // Support non-Blazy which normally uses item_id.
      $wrapper_attrs = isset($item['attributes']) ? $item['attributes'] : [];
      $content_attrs = isset($item['content_attributes']) ? $item['content_attributes'] : [];
      $sets = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
      $sets = isset($item['#build']) && isset($item['#build']['settings']) ? array_merge($sets, $item['#build']['settings']) : $sets;
      $sets['delta'] = $key;

      // Supports both single formatter field and complex fields such as Views.
      $classes = isset($wrapper_attrs['class']) ? $wrapper_attrs['class'] : [];
      $wrapper_attrs['class'] = array_merge([$item_class], $classes);
      self::gridItemAttributes($wrapper_attrs, $sets);

      // Good for Bootstrap .well/ .card class, must cast or BS will reset.
      $classes = empty($content_attrs['class']) ? [] : $content_attrs['class'];
      $content_attrs['class'] = array_merge(['grid__content'], $classes);

      // Remove known unused array.
      unset($item['settings'], $item['attributes'], $item['content_attributes']);
      if (isset($item['item']) && is_object($item['item'])) {
        unset($item['item']);
      }

      $content['content'] = $is_grid ? [
        '#theme'      => 'container',
        '#children'   => $item,
        '#attributes' => $content_attrs,
      ] : $item;

      $content['#wrapper_attributes'] = $wrapper_attrs;
      $contents[] = $content;
    }

    // Supports field label via Field UI, unless use_field takes place.
    $title = '';
    if (empty($settings['use_field'])
      && isset($settings['label'], $settings['label_display'])
      && $settings['label_display'] != 'hidden') {
      $title = $settings['label'];
    }

    $attrs = [];
    self::attributes($attrs, $settings);

    $wrapper = ['item-list--blazy', 'item-list--blazy-' . $style];
    $wrapper = $style ? $wrapper : ['item-list--blazy'];
    $wrapper = array_merge(['item-list'], $wrapper);

    return [
      '#theme'              => 'item_list',
      '#items'              => $contents,
      '#context'            => ['settings' => $settings],
      '#attributes'         => $attrs,
      '#wrapper_attributes' => ['class' => $wrapper],
      '#title'              => $title,
    ];
  }

  /**
   * Provides reusable container attributes.
   */
  public static function attributes(array &$attributes, array $settings = []) {
    $is_gallery = !empty($settings['lightbox']) && !empty($settings['gallery_id']);

    // Provides data-attributes to avoid conflict with original implementations.
    Blazy::containerAttributes($attributes, $settings);

    // Provides gallery ID, although Colorbox works without it, others may not.
    // Uniqueness is not crucial as a gallery needs to work across entities.
    if (!empty($settings['id'])) {
      $attributes['id'] = $is_gallery ? $settings['gallery_id'] : $settings['id'];
    }

    // Provides grid container attributes.
    self::gridContainerAttributes($attributes, $settings);
  }

  /**
   * Limit to grid only, so to be usable for plain list.
   */
  public static function gridContainerAttributes(array &$attributes, array $settings = []) {
    $style = $settings['style'];

    if (!empty($settings['_grid'])) {
      $attributes['class'][] = 'blazy--grid block-' . $style . ' block-count-' . $settings['count'];

      // If Native Grid style with numeric grid, assumed non-two-dimensional.
      if ($style == 'nativegrid') {
        $attributes['class'][] = empty($settings['nativegrid.masonry']) ? 'is-b-native' : 'is-b-masonry';
      }

      // Adds common grid attributes for CSS3 column, Foundation, etc.
      // Only if using the plain grid column numbers (1 - 12).
      if ($settings['grid_large'] = $settings['grid']) {
        foreach (['small', 'medium', 'large'] as $key) {
          $value = empty($settings['grid_' . $key]) ? NULL : $settings['grid_' . $key];
          if ($value && is_numeric($value)) {
            $attributes['class'][] = $key . '-block-' . $style . '-' . $value;
          }
        }
      }
    }
  }

  /**
   * LProvides grid item attributes, relevant for Native Grid.
   */
  public static function gridItemAttributes(array &$attributes, array $settings = []) {
    if (isset($settings['grid_large_dimensions']) && $dim = $settings['grid_large_dimensions']) {
      $key = $settings['delta'];
      if (isset($dim[$key])) {
        $attributes['data-b-w'] = $dim[$key]['width'];
        if (!empty($dim[$key]['height'])) {
          $attributes['data-b-h'] = $dim[$key]['height'];
        }
      }
      else {
        // Supports a grid repeat for the lazy.
        $count = empty($settings['count']) ? 0 : $settings['count'];
        $height = $dim[0]['height'];
        $width = $dim[0]['width'];
        if ($count > count($dim) && !empty($width)) {
          $attributes['data-b-w'] = $width;
          if (!empty($height)) {
            $attributes['data-b-h'] = $height;
          }
        }
      }
    }
  }

  /**
   * Checks if a grid expects a two-dimensional grid.
   */
  public static function isNativeGrid($grid) {
    return !empty($grid) && !is_numeric($grid);
  }

  /**
   * Checks if a grid uses a native grid, but expecting a masonry.
   */
  public static function isNativeGridAsMasonry(array $settings = []) {
    $grid = $settings['grid'];
    return !self::isNativeGrid($grid) && $settings['style'] == 'nativegrid';
  }

  /**
   * Extracts grid like: 4x4 4x3 2x2 2x4 2x2 2x3 2x3 4x2 4x2, or single 4x4.
   */
  public static function toDimensions($grid) {
    $dimensions = [];
    if (self::isNativeGrid($grid)) {
      $values = array_map('trim', explode(" ", $grid));
      foreach ($values as $value) {
        $width = $value;
        $height = 0;
        if (mb_strpos($value, 'x') !== FALSE) {
          list($width, $height) = array_pad(array_map('trim', explode("x", $value, 2)), 2, NULL);
        }

        $dimensions[] = ['width' => $width, 'height' => $height];
      }
    }

    return $dimensions;
  }

  /**
   * Passes grid like: 4x4 4x3 2x2 2x4 2x2 2x3 2x3 4x2 4x2 to settings.
   */
  public static function toNativeGrid(array &$settings = []) {
    if (empty($settings['grid'])) {
      return;
    }

    if ($settings['grid_large'] = $settings['grid']) {
      if (self::isNativeGridAsMasonry($settings)) {
        $settings['nativegrid.masonry'] = TRUE;
      }

      // If Native Grid style with numeric grid, assumed non-two-dimensional.
      foreach (['small', 'medium', 'large'] as $key) {
        $value = empty($settings['grid_' . $key]) ? NULL : $settings['grid_' . $key];
        if ($dimensions = self::toDimensions($value)) {
          $settings['grid_' . $key . '_dimensions'] = $dimensions;
        }
      }
    }
  }

}
