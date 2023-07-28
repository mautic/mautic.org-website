<?php

namespace Drupal\discourse_comments\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a 'DiscourseCommentBlock' block.
 *
 * @Block(
 *  id = "discourse_comment_block",
 *  admin_label = @Translation("Discourse comment block"),
 * )
 */
class DiscourseCommentBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    $node = $this->currentRouteMatch->getParameter('node');
    $discourse_settings = $this->configFactory->get('discourse_comments.discourse_comments_settings');
    if ($node instanceof NodeInterface && $node->hasField('discourse_field')) {
      $field_discourse = $node->get('discourse_field')->getValue();
      if (isset($field_discourse[0]['topic_id']) && is_numeric($field_discourse[0]['topic_id'])) {
        $topic = Json::decode($this->discourseApiClient->getTopic($field_discourse[0]['topic_id']));
        // Save topic count.
        $post_count = ($topic['posts_count'] > 0) ? ($topic['posts_count'] - 1) : 0;
        $field_discourse[0]['comment_count'] = $post_count;
        $node->set('discourse_field', $field_discourse)->save();
        $comments = [];
        $default_avatar_image = $this->discourseApiClient->getDefaultAvatar();
        if (isset($topic['post_stream']) && isset($topic['post_stream']['posts'])) {
          foreach ($topic['post_stream']['posts'] as $key => $post) {
            if ($key > 0) {
              if ($post['user_deleted']) {
                continue;
              }
              $comments[$key]['username'] = $post['username'];
              // Appending base url if https:// does not exist in image path.
              if (strpos($post['avatar_template'], "https://") === FALSE) {
                $avatar_image = sprintf('%s%s', $this->discourseApiClient->getBaseUrl(), str_replace('{size}', '90', $post['avatar_template']));
              }
              else {
                $avatar_image = str_replace('{size}', '90', $post['avatar_template']);
              }
              // Placing default avatar image if avatar image does not exist.
              if (@getimagesize($avatar_image)) {
                $comments[$key]['avatar_template'] = $avatar_image;
              }
              else {
                $comments[$key]['avatar_template'] = $default_avatar_image;
              }

              $date = $this->dateFormatter->format(strtotime($post['created_at']), 'custom', 'F d, Y');
              $comments[$key]['created_at'] = $date;
              $comments[$key]['post_content'] = preg_replace('/<\/?a[^>]*>/', '', $post['cooked']);
            }
          }
        }

        $build['#theme'] = 'discourse_comment_block';
        $build['#content'] = $comments;
        $build['#topic_url'] = $field_discourse[0]['topic_url'];
        $build['#forum_link'] = $discourse_settings->get('forum_link');
        $build['#forum_link_label'] = $discourse_settings->get('forum_link_label');
      }
    }

    $cache_lifetime = $discourse_settings->get('cache_lifetime');
    $build['#cache'] = [
      'contexts' => ['url.path'],
      'max-age' => $cache_lifetime * 60,
    ];

    return $build;
  }

}
