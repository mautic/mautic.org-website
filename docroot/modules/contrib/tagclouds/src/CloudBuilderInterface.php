<?php

namespace Drupal\tagclouds;

/**
 * Interface CloudBuilderInterface.
 *
 * @package Drupal\tagclouds
 */
interface CloudBuilderInterface {

  /**
   * Returns a render array for the tags.
   *
   * @param array $terms
   *  A list of tags to render.
   */
  public function build(array $terms);
}
