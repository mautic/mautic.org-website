<?php

namespace Drupal\tagclouds\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\tagclouds\Controller\CsvToArrayTrait;
use Drupal\tagclouds\TagServiceInterface;
use Drupal\tagclouds\CloudBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for user routes.
 */
class TagcloudsPageChunk extends ControllerBase {

  use CsvToArrayTrait;

  /**
   * @var \Drupal\tagclouds\TagServiceInterface $tagcloudTag
   *   Injection of tag service.
   */
  protected $tagcloudTag;

  /**
   * @var \Drupal\tagclouds\CloudBuilderInterface $tagcloudsCloudBuilder
   *   Injection of cloud builder service.
   */
  protected $tagcloudsCloudBuilder;

  /**
   * Constructs a BlockContent object.
   *
   * @param \Drupal\tagclouds\TagServiceInterface $tag_service
   *   The tag service.
   * @param \Drupal\tagclouds\CloudBuilderInterface $cloud_builder
   *   The cloud builder.
   */
  public function __construct(TagServiceInterface $tag_service, CloudBuilderInterface $cloud_builder) {
    $this->tagcloudTag = $tag_service;
    $this->tagcloudsCloudBuilder = $cloud_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tagclouds.tag'),
      $container->get('tagclouds.cloud_builder')
    );
  }

  /**
   * Renders a list of vocabularies.
   *
   * @param string $tagclouds_vocs_str
   *   A comma separated list of vocabulary ids.
   *
   * @return array
   *   A render array.
   */
  public function chunk($tagclouds_vocs_str = '') {
    $vocs = $this->csvToArray($tagclouds_vocs_str);
    if (empty($vocs)) {
      $query = $this->entityTypeManager()
        ->getStorage('taxonomy_vocabulary')
        ->getQuery();
      $all_ids = $query->execute();
      foreach (Vocabulary::loadMultiple($all_ids) as $vocabulary) {
        $vocs[] = $vocabulary->id();
      }
    }
    $config = $this->config('tagclouds.settings');
    $tags = $this
      ->tagcloudTag
      ->getTags($vocs, $config->get('levels'), $config->get('page_amount'));

    $sorted_tags = $this->tagcloudTag->sortTags($tags);

    $output = [
      '#attached' => ['library' => 'tagclouds/clouds'],
      '#theme' => 'tagclouds_weighted',
      '#children' => $this->tagcloudsCloudBuilder->build($sorted_tags),
    ];

    return $output;
  }

}
