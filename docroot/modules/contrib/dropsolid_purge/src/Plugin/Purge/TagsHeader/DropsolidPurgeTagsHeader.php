<?php

namespace Drupal\dropsolid_purge\Plugin\Purge\TagsHeader;

use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderBase;
use Drupal\dropsolid_purge\Hash;

/**
 * Exports the X-Dropsolid-Purge-Tags header.
 *
 * @PurgeTagsHeader(
 *   id = "dropsolidpurgetagsheader",
 *   header_name = "X-Dropsolid-Purge-Tags",
 * )
 */
class DropsolidPurgeTagsHeader extends TagsHeaderBase implements TagsHeaderInterface {

  /**
   * {@inheritdoc}
   */
  public function getValue(array $tags) {
    return implode(' ', Hash::cacheTags($tags));
  }

}
