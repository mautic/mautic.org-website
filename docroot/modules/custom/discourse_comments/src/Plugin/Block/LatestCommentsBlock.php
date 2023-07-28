<?php

namespace Drupal\discourse_comments\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LatestCommentsBlock' block.
 *
 * @Block(
 *  id = "latest_comments_block",
 *  admin_label = @Translation("Latest Comments block"),
 * )
 */
class LatestCommentsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\discourse_comments\DiscourseApiClient definition.
   *
   * @var \Drupal\discourse_comments\DiscourseApiClient
   */
  protected $discourseApiClient;

  /**
   * Route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->discourseApiClient = $container->get('discourse_comments.discourse_api_client');
    $instance->currentRouteMatch = $container->get('current_route_match');
    $instance->configFactory = $container->get('config.factory');
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $content = $this->discourseApiClient->getLatestComments();
    if (!$content) {
      $count = 0;
    }
    else {
      $count = count($content);
    }

    $build['#theme'] = 'latest_comments_block';
    $build['#content'] = $content;
    $build['#count'] = $count;

    $cache_lifetime = $this->configFactory->get('discourse_comments.discourse_comments_settings')->get('cache_lifetime');
    $build['#cache'] = [
      'tags' => ['latest_comment_block'],
      'contexts' => ['url.path'],
      'max-age' => $cache_lifetime * 60,
    ];

    return $build;
  }

}
