<?php

namespace Drupal\tagclouds\Controller;

/**
 * Provides method to convert cvs list of vocabulary id an to array.
 */
trait CsvToArrayTrait {

  /**
   * Returns and array of ids when given a comma separated string of ids.
   *
   * @param string $strings
   *   The string of ids eg "tag1, tag2".
   *
   * @return array
   *   An array of ids.
   */
  protected function csvToArray($strings) {
    return !empty($strings) ? explode(',', $strings) : [];
  }

}
