<?php

declare(strict_types = 1);

namespace Drupal\mauticorg_blog\Repository;

/**
 * Provides a blog post repository interface.
 */
interface BlogPostRepositoryInterface {

  /**
   * Find all the blog posts.
   *
   * @return \Drupal\node\NodeInterface[]
   *   A list of blog posts.
   */
  public function findAll(): array;

  /**
   * Find blog posts by category.
   *
   * @param string $category
   *   The blog category name.
   *
   * @return \Drupal\node\NodeInterface[]
   *   A list of blog posts.
   */
  public function findByCategory(string $category): array;

  /**
   * Find blog posts by tag.
   *
   * @param string $tag
   *   The blog tag name.
   *
   * @return \Drupal\node\NodeInterface[]
   *   A list of blog posts.
   */
  public function findByTag(string $tag): array;

}
