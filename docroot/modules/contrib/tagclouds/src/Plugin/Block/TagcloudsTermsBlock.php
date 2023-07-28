<?php

namespace Drupal\tagclouds\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tagclouds\TagService;
use Drupal\tagclouds\CloudBuilder;
use Drupal\Core\Cache\Cache;

/**
 * Provides a template for blocks based of each vocabulary.
 *
 * @Block(
 *   id = "tagclouds_block",
 *   admin_label = @Translation("Tagclouds terms"),
 *   category = @Translation("Tagclouds"),
 *   deriver = "Drupal\tagclouds\Plugin\Derivative\TagcloudsTermsBlock"
 * )
 *
 * @see \Drupal\tagclouds\Plugin\Derivative\TagcloudsTermsBlock
 */
class TagcloudsTermsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  public function blockForm($form, FormStateInterface $form_state) {
    $terms_limit = isset($this->configuration['tags']) ? $this->configuration['tags'] : 0;
    $vocabulary = isset($this->configuration['vocabulary']) ? $this->configuration['vocabulary'] : 'tags';
    $form['tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tags to show'),
      '#default_value' => $terms_limit,
      '#maxlength' => 3,
      '#description' => $this->t("The number of tags to show in this block. Enter '0' to display all tags."),
    ];
    $form['vocabulary'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vocabulary machine name'),
      '#default_value' => $vocabulary,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['tags'] = $form_state->getValue('tags');
    $this->configuration['vocabulary'] = $form_state->getValue('vocabulary');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $tags_limit = $this->configuration['tags'];
    $vocab_name = $this->configuration['vocabulary'];

    $content = [
      '#attached' => ['library' => 'tagclouds/clouds'],
    ];
    if ($voc = Vocabulary::load($vocab_name)) {
      $tags = $this->tagService->getTags([$vocab_name], $this->configFactory->getEditable('tagclouds.settings')->get('levels'), $tags_limit);

      $tags = $this->tagService->sortTags($tags);

      $content[] = [
        'tags' => $this->cloudBuilder->build($tags),
      ];
      if (count($tags) >= $tags_limit && $tags_limit > 0) {
        $content[] = [
          '#type' => 'more_link',
          '#title' => $this->t('more tags'),
          '#url' => Url::fromRoute('tagclouds.chunk_vocs', ['tagclouds_vocs_str' => $voc->id()]),
        ];
      }
    }

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list', 'config:tagclouds.settings', 'taxonomy_term_list']);
  }

}
