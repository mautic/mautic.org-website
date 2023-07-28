<?php

/**
 * @file
 * Hooks and preprocess functions for the Slick module.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Serialization\Json;
use Drupal\blazy\Blazy;
use Drupal\slick\SlickDefault;
use Drupal\slick\Entity\Slick;

/**
 * Prepares variables for slick.html.twig templates.
 */
function template_preprocess_slick(&$variables) {
  $element   = $variables['element'];
  $defaults  = SlickDefault::htmlSettings();
  $settings  = isset($element['#settings']) ? array_merge($defaults, $element['#settings']) : $defaults;
  $customs   = isset($element['#options']) ? $element['#options'] : [];
  $optionset = isset($element['#optionset']) ? $element['#optionset'] : Slick::loadWithFallback($settings['optionset']);
  $js        = $customs ? array_merge($optionset->getSettings(), $customs) : $optionset->getSettings();

  // Prepare attributes.
  $attributes = &$variables['attributes'];
  $custom_classes = empty($attributes['class']) ? [] : $attributes['class'];
  $attributes['class'] = array_merge(['slick'], $custom_classes);
  $content_attributes = new Attribute();
  $display = $variables['display'] = $settings['display'];

  $id = empty($settings['id']) ? Blazy::getHtmlId('slick') : $settings['id'];
  $attributes['id'] = $display == 'thumbnail' ? $id . '-thumbnail' : $id;

  // @see SlickManager::buildGrid(), and this should make sense.
  $settings['count']   = isset($settings['count']) ? $settings['count'] : count($element['#items']);
  $settings['unslick'] = $settings['unslick'] || $settings['count'] == 1;

  foreach (SlickDefault::jsSettings() as $key => $default) {
    $settings[$key] = isset($js[$key]) ? strip_tags($js[$key]) : $default;
  }

  // Blazy can still lazyload an unslick.
  // The lazy supercedes JS lazyLoad for background, breakpoints, media, etc.
  $settings['lazy'] = $settings['lazy'] ?: $js['lazyLoad'];
  if ($display != 'thumbnail' && ($settings['lazy'] == 'blazy' || !empty($settings['blazy']))) {
    $js['lazyLoad'] = 'blazy';
  }

  // Make slick language-direction-aware.
  $language = \Drupal::languageManager()->getCurrentLanguage();
  if ($language->getDirection() == 'rtl') {
    $attributes['dir'] = $language->getDirection();
    $js['rtl'] = $language->getDirection() ? TRUE : FALSE;
  }

  // Remove settings that aren't supported by the active library.
  Slick::removeUnsupportedSettings($js);

  // Prevents broken slick when only one item given, or an enforced unslick.
  if (!$settings['unslick']) {
    $content_attributes->setAttribute('id', $attributes['id'] . '-slider');

    $variables['arrow_attributes'] = new Attribute();
    $variables['arrow_attributes']['role'] = 'navigation';
    if ($display == 'main' && !empty($js['downArrow']) && !empty($js['downArrowTarget'])) {
      $variables['arrow_down_attributes'] = new Attribute();
    }

    // focusOnSelect won't work with empty slide value, so add proper selector.
    // Respects core Grid markups which may wrap .slick__slide within anon DIV.
    // Unfortunately focusOnSelect is removed by Accessible Slick. We kept it
    // here for just in case it will be re-enacted due to being overlooked.
    if (empty($js['slide']) && !empty($js['focusOnSelect'])) {
      // Different versions have different logic causing breaking changes.
      // Basically the fundamental issue: `rows > 0` vs `rows > 1`. This is
      // enough to break slides with duplicate slide markups + anonymous DIV.
      // What we do here is to accommodate both versions.
      $add = ($js['rows'] < 2 && $js['slidesPerRow'] < 2);

      // Fix for breaking changes with Slick 1.8.1/1.9.0 and Accessible Slick.
      // This is no use for Accessible Slick due to no-synching w/ thumbnail.
      if (!empty($settings['breaking'])) {
        $add = $js['rows'] < 2;
      }
      $js['slide'] = $add ? '.slick__slide' : $js['slide'];
    }

    // Add the configuration as JSON object into the slick container.
    $js = isset($variables['js']) ? array_merge($js, $variables['js']) : $js;
    if ($json = $optionset->removeDefaultValues($js)) {
      $content_attributes->setAttribute('data-slick', Json::encode($json));
    }
  }

  // Pass settings and attributes to twig.
  $variables['js'] = $js;
  $variables['settings'] = $settings;
  $variables['content_attributes'] = $content_attributes;

  // Process individual item.
  $variables['items'] = [];
  foreach ($element['#items'] as $delta => $item) {
    $item_settings = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
    $item_attrs = isset($item['attributes']) ? $item['attributes'] : [];
    $content_attrs = isset($item['content_attributes']) ? $item['content_attributes'] : [];
    $item_settings['current_item'] = $display;

    unset($item['settings'], $item['attributes'], $item['content_attributes'], $item['item']);

    $theme = $settings['vanilla'] ? 'vanilla' : ($display == 'thumbnail' ? 'thumbnail' : 'slide');
    $slide = [
      '#theme' => 'slick_' . $theme,
      '#item' => $item,
      '#delta' => $delta,
      '#settings' => $item_settings,
      '#attributes' => $item_attrs,
      '#content_attributes' => $content_attrs,
    ];
    $variables['items'][$delta] = $slide;
    unset($slide);
  }
}

/**
 * Prepares variables for slick-wrapper.html.twig templates.
 */
function template_preprocess_slick_wrapper(&$variables) {
  foreach (['attributes', 'items', 'settings'] as $key) {
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : [];
  }
}

/**
 * Prepares common variables for slick item templates.
 */
function _slick_preprocess_slick_item(&$variables) {
  $element = $variables['element'];
  foreach (['attributes', 'content_attributes', 'delta', 'item', 'settings'] as $key) {
    $default = $key == 'delta' ? NULL : [];
    $variables[$key] = isset($element["#$key"]) ? $element["#$key"] : $default;
  }

  // Because Twig prepends new classes, not appends as expected by BEM.
  $attributes = &$variables['attributes'];
  $classes = empty($attributes['class']) ? [] : (array) $attributes['class'];
  $attributes['class'] = array_merge(['slick__slide', 'slide'], $classes);
}

/**
 * Prepares variables for slick-vanilla.html.twig templates.
 */
function template_preprocess_slick_vanilla(&$variables) {
  _slick_preprocess_slick_item($variables);
}

/**
 * Prepares variables for slick-thumbnail.html.twig templates.
 */
function template_preprocess_slick_thumbnail(&$variables) {
  _slick_preprocess_slick_item($variables);
}

/**
 * Prepares variables for slick-slide.html.twig templates.
 */
function template_preprocess_slick_slide(&$variables) {
  _slick_preprocess_slick_item($variables);

  // All slide types -- main, thumbnail, grid, overlay -- may have captions.
  foreach (['alt', 'data', 'link', 'overlay', 'title'] as $key) {
    $variables['item']['caption'][$key] = isset($variables['item']['caption'][$key]) ? $variables['item']['caption'][$key] : [];
  }

  $item = &$variables['item'];
  $sets = &$variables['settings'];
  $variables['caption_attributes'] = new Attribute();

  // split: Split image from captions if we do have captions, and main image.
  // fullwidth: If full skins, add wrappers to hold caption and overlay.
  // detroy: Remove .slide__content if it is an enforced unslick grid.
  // wrapper: Don't add divities for a single item to have clean markups.
  $item['slide']     = isset($item['slide']) ? $item['slide'] : [];
  $item['caption']   = array_filter($item['caption']);
  $sets['split']     = !empty($item) && (!empty($sets['caption']) || !empty($sets['title']));
  $sets['data']      = !empty($item['caption']['alt']) || !empty($item['caption']['title']) || !empty($item['caption']['data']);
  $sets['fullwidth'] = !empty($sets['skin']) && strpos($sets['skin'], 'full') !== FALSE;
  $sets['grid']      = empty($sets['grid']) ? FALSE : $sets['grid'];
  $sets['detroy']    = $sets['current_item'] == 'main' && !empty($sets['grid']) && !empty($sets['unslick']);
  $sets['wrapper']   = $sets['count'] > 1 && $sets['current_item'] != 'grid';
  $sets['use_ca']    = empty($sets['grid']) || !empty($variables['content_attributes']);
}

/**
 * Prepares variables for slick-grid.html.twig templates.
 *
 * Slick now uses core theme_item_list via BlazyGrid instead. This function is
 * no longer functional, nor called. Should you need to modify its output, use
 * hook_slick_grid_item_alter instead, see SlickManager::buildGridItem().
 * This is still kept to avoid WSOD during careless update.
 *
 * @deprecated in slick:8.x-2.3 and is removed from slick:8.x-3.0. Use
 *   hook_slick_grid_item_alter if necessary instead.
 * @see https://www.drupal.org/node/3239708
 */
function template_preprocess_slick_grid(&$variables) {
  $element                 = $variables['element'];
  $variables['settings']   = $sets = $element['#settings'];
  $variables['delta']      = isset($element['#delta']) ? $element['#delta'] : 0;
  $variables['grid_id']    = 'grid';
  $variables['attributes'] = isset($variables['attributes']) ? $variables['attributes'] : [];

  $variables['items'] = [];
  foreach ($variables['element']['#items'] as $delta => $item) {
    $slide = ['content' => [], 'attributes' => []];
    $variables['items'][$delta] = $slide;
  }
}
