<?php

namespace Drupal\dropsolid_purge;

/**
 * Helper class that centralizes string hashing for security and maintenance.
 */
/**
 * Class Hash
 * @package Drupal\dropsolid_purge
 */
class Hash {

  /**
   * Create a hash with the given input and length.
   *
   * @param string $input
   *   The input string to be hashed.
   * @param int $length
   *   The length of the hash.
   *
   * @return string
   *   Cryptographic hash with the given length.
   */
  static private function hash($input, $length) {
    // MD5 is the fastest algorithm beyond CRC32 (which is 30% faster, but high
    // collision risk), so this is the best bet for now. If collisions are going
    // to be a major problem in the future, we might have to consider a hash DB.
    return substr(hash('md5', $input), 0, $length);
  }

  /**
   * Create unique hashes/IDs for a list of cache tag strings.
   *
   * @param string[] $tags
   *   Non-associative array cache tags.
   *
   * @return string[]
   *   Non-associative array with hashed copies of the given cache tags.
   */
  static public function cacheTags(array $tags) {
    $hashes = [];
    foreach ($tags as $tag) {
      $hashes[] = SELF::hash($tag, 4);
    }
    return $hashes;
  }

  /**
   * Create a unique hash that identifies this site.
   *
   * @param string $site_name
   *   The identifier of the site.
   * @param string $site_path
   *   The path of the site, e.g. 'site/default' or 'site/database_a'.
   * @param string $site_environment
   *   The environment it is on
   * @param string $site_group
   *   The group it is part of
   *
   * @return string
   *   Cryptographic hash that's long enough to be unique.
   */
  static public function siteIdentifier($site_name, $site_path,$site_environment,$site_group) {
    return SELF::hash($site_name . $site_path . $site_environment . $site_group, 16);
  }

}
