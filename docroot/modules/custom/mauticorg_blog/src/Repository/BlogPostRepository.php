<?php

declare(strict_types = 1);

namespace Drupal\mauticorg_blog\Repository;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a blog post repository.
 */
class BlogPostRepository implements BlogPostRepositoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for BlogPostRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function findAll(): array {
    return $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'status' => NodeInterface::PUBLISHED,
        'type' => 'blog',
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function findByCategory(string $category): array {
    $category = str_replace('-', ' ', $category);
    $categories = (function (string $category): array {
      $terms = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'vid' => 'blog_category',
          'name' => $category,
        ]);

      return array_map(function (TermInterface $category): string {
        return $category->id();
      }, $terms);
    })($category);

    if (empty($categories)) {
      return [];
    }

    return $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'status' => NodeInterface::PUBLISHED,
        'type' => 'blog',
        'field_category' => $categories,
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function findByTag(string $tag): array {
    $tag = str_replace('/-/', ' ', $tag);
    $tags = (function (string $tag): array {
      $terms = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'vid' => 'tags',
          'name' => $tag,
        ]);

      return array_map(function (TermInterface $tag): string {
        return $tag->id();
      }, $terms);
    })($tag);

    if (empty($tags)) {
      return [];
    }

    return $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'status' => NodeInterface::PUBLISHED,
        'type' => 'blog',
        'field_tags' => $tags,
      ]);
  }

}
