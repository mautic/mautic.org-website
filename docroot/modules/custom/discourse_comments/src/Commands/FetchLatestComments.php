<?php

namespace Drupal\discourse_comments\Commands;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\discourse_comments\DiscourseApiClient;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile for adding drush commands.
 */
class FetchLatestComments extends DrushCommands {

  /**
   * Discourse api client.
   *
   * @var \Drupal\discourse_comments\DiscourseApiClient
   */
  protected $discourseApi;

  /**
   * Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * FetchLatestComments constructor.
   *
   * @param \Drupal\discourse_comments\DiscourseApiClient $discourse_api_client
   *   DiscourseApiClient service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend service.
   */
  public function __construct(DiscourseApiClient $discourse_api_client, CacheBackendInterface $cacheBackend) {
    $this->discourseApi = $discourse_api_client;
    $this->cache = $cacheBackend;
  }

  /**
   * Fetches latest 5 comments from discourse which have corresponding node.
   *
   * @command fetch:latest_comments
   * @aliases fetch_comments
   * @usage fetch:latest_comments
   *   Fetches latest comments and add them in cache.
   */
  public function fetch() {
    $latest_comments = $this->discourseApi->fetchLatestComments(5);
    if ($latest_comments) {
      $message = sprintf('%s comments fetched.', count($latest_comments));
      $this->output->writeln($message);
    }
    else {
      $this->output->writeln('No comments fetched.');
    }

    Cache::invalidateTags(['latest_comment_block']);
  }

}
