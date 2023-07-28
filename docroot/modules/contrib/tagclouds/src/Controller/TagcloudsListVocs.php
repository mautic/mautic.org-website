<?php

namespace Drupal\tagclouds\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tagclouds\CloudBuilder;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\tagclouds\TagService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for user routes.
 */
class TagcloudsListVocs extends ControllerBase {

  use CsvToArrayTrait;

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
   * Constructs a new TagcloudsTermsBlock instance.
   *
   * @param \Drupal\tagclouds\TagService $tagService
   *   The tag service.
   * @param \Drupal\tagclouds\CloudBuilder $cloudBuilder
   *   The cloud builder service.
   */
  public function __construct(TagService $tagService, CloudBuilder $cloudBuilder) {
    $this->tagService = $tagService;
    $this->cloudBuilder = $cloudBuilder;
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
   * Vocabularys are wrapped in a series of boxes, labeled by name
   * description.
   *
   * @param string $tagclouds_vocs_str
   *   A comma separated list of vocabulary ids.
   *
   * @return array
   *   A render array.
   *
   * @throws NotFoundHttpException
   *   Thrown when any vocabulary in the list cannot be found.
   */
  public function listVocs($tagclouds_vocs_str = NULL) {
    $vocs = $this->csvToArray($tagclouds_vocs_str);
    if (empty($vocs)) {
      throw new NotFoundHttpException();
    }

    $boxes = [];
    foreach ($vocs as $vid) {
      $vocabulary = Vocabulary::load($vid);

      if ($vocabulary == FALSE) {
        throw new NotFoundHttpException();
      }

      $config = $this->config('tagclouds.settings');
      $tags = $this->tagService->getTags([$vid], $config->get('levels'), $config->get('page_amount'));
      $sorted_tags = $this->tagService->sortTags($tags);

      $cloud = $this->cloudBuilder->build($sorted_tags);

      if (!$cloud) {
        throw new NotFoundHttpException();
      }

      $boxes[] = [
        '#theme' => 'tagclouds_list_box',
        '#vocabulary' => $vocabulary,
        '#children' => $cloud,
      ];

    }

    // Wrap boxes in a div.
    $output = [
      '#attached' => ['library' => 'tagclouds/clouds'],
      '#type' => 'container',
      '#children' => $boxes,
      '#attributes' => ['class' => 'wrapper tagclouds'],
    ];

    return $output;
  }

}
