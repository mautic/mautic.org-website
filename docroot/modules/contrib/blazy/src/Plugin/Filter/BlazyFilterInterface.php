<?php

namespace Drupal\blazy\Plugin\Filter;

/**
 * Defines re-usable services and functions for blazy plugins.
 */
interface BlazyFilterInterface {

  /**
   * Returns the faked image item for the image, uploaded or hard-coded.
   *
   * @param array $build
   *   The content array being modified.
   * @param object $node
   *   The HTML DOM object.
   */
  public function buildImageItem(array &$build, &$node);

  /**
   * Gets the caption if available.
   *
   * @param array $build
   *   The content array being modified.
   * @param object $node
   *   The HTML DOM object.
   *
   * @return object
   *   The HTML DOM object.
   */
  public function buildImageCaption(array &$build, &$node);

  /**
   * Returns the faked image item from SRC.
   *
   * @param array $settings
   *   The content array being modified.
   * @param object $node
   *   The HTML DOM object.
   * @param string $src
   *   The corrected SRC value.
   *
   * @return object
   *   The faked or file entity image item.
   */
  public function getImageItemFromImageSrc(array &$settings, $node, $src);

  /**
   * Returns the faked image item from SRC.
   *
   * @param array $settings
   *   The content array being modified.
   * @param object $node
   *   The HTML DOM object.
   * @param string $src
   *   The corrected SRC value.
   *
   * @return object
   *   The faked or file entity image item.
   */
  public function getImageItemFromIframeSrc(array &$settings, &$node, $src);

  /**
   * Returns the main settings.
   *
   * @param string $text
   *   The provided text.
   *
   * @return array
   *   The main settings for current filter.
   */
  public function buildSettings($text);

  /**
   * Returns the item settings for the current $node.
   *
   * @param array $build
   *   The settings being modified.
   * @param object $node
   *   The HTML DOM object.
   */
  public function buildItemSettings(array &$build, $node);

}
