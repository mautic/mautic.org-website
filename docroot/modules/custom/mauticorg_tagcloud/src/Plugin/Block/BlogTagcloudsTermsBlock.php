<?php

namespace Drupal\mauticorg_tagcloud\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tagclouds\TagService;
use Drupal\tagclouds\CloudBuilder;
use Drupal\Core\Cache\Cache;

/**
 * Provides a template for blocks based of each vocabulary.
 *
 * @Block(
 *   id = "blog_tagclouds_block",
 *   admin_label = @Translation("Blog Tags cloud"),
 *   category = @Translation("Tagclouds"),
 * )
 */
class BlogTagcloudsTermsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The tag service.
   *
   * @var \Drupal\tagclouds\TagService
   */
  protected $tagService;

  /**
   * The cloud builder service.
   *
   * @var \Drupal\tagclouds\CloudBuilder
   */
  protected $cloudBuilder;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new TagcloudsTermsBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\tagclouds\TagService $tagService
   *   The tag service.
   * @param \Drupal\tagclouds\CloudBuilder $cloudBuilder
   *   The cloud builder service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TagService $tagService, CloudBuilder $cloudBuilder, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tagService = $tagService;
    $this->cloudBuilder = $cloudBuilder;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tagclouds.tag'),
      $container->get('tagclouds.cloud_builder'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'cache' => [
        'max_age' => 0,
        'contexts' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $tags_limit = isset($this->configuration['tags']) ? $this->configuration['tags'] : 30;
    $vocab_name = isset($this->configuration['vocabulary']) ? $this->configuration['vocabulary'] : 'tags';

    $content = [
      '#attached' => ['library' => 'tagclouds/clouds'],
    ];
    if (Vocabulary::load($vocab_name)) {
      $tags = $this->tagService->getTags([$vocab_name], $this->configFactory->getEditable('tagclouds.settings')->get('levels'), $tags_limit);

      $tags = $this->tagService->sortTags($tags);

      $content[] = [
        'tags' => $this->cloudBuilder->build($tags),
      ];
    }

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list',
      'config:tagclouds.settings',
      'taxonomy_term_list',
    ]);
  }

}
