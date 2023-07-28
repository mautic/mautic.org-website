<?php

namespace Drupal\tagclouds;

/**
 * Interface TagServiceInterface.
 *
 * @package Drupal\tagclouds
 */
interface TagServiceInterface {

  /**
   * Orders a set of tags.
   *
   * @param array $tags
   *   An array of tag objects.
   * @param string $sort_order
   *   (optional) Contains the sort and the order string.
   *   Possible values:
   *     "title, asc", "title, desc", "count, asc", "count, desc".
   *
   * @todo If you feel like making this more modular, please send me patches.
   *
   * @return array
   *   A list of sorted tag objects.
   */
  public function sortTags(array $tags, $sort_order = NULL);

  /**
   * Return an array of tags.
   *
   * Gets the information from the database, passes it along to
   * the weight builder and returns these weighted tags. Note that the tags are
   * unordered at this stage, hence they need ordering either by calling
   * sortTags() or by your own ordering data.
   *
   * @param array $vids
   *   Vocabulary ids representing the vocabularies where you want the tags from.
   * @param int $steps
   *   (optional) The amount of tag-sizes you will be using. If you give "12"
   *   you still get six different "weights". Defaults to 6.
   * @param int $size
   *   (optional) The number of tags that will be returned. Default to 60.
   * @param string $display
   *   (optional) The type of display "style"=weighted,"count"=numbered display.
   *
   * @return array
   *   An <em>unordered</em> array with tags-objects, containing the attribute
   *   $tag->weight.
   */
  public function getTags(array $vids, $steps = 6, $size = 60, $display = NULL);

}
