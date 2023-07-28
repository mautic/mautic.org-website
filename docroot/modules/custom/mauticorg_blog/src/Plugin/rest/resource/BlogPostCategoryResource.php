<?php

declare(strict_types = 1);

namespace Drupal\mauticorg_blog\Plugin\rest\resource;

use Drupal\mauticorg_blog\Cache\CacheableResourceResponseTrait;
use Drupal\mauticorg_blog\Repository\BlogPostRepositoryInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a blog post category resource.
 *
 * @RestResource(
 *   id = "mauticorg_blog_category",
 *   label = @Translation("Mautic Blog By Category"),
 *   uri_paths = {
 *     "canonical" = "/blog/category/{category}/rss.xml",
 *   }
 * )
 */
class BlogPostCategoryResource extends ResourceBase {

  use CacheableResourceResponseTrait;

  /**
   * The blog post repository.
   *
   * @var \Drupal\mauticorg_blog\Repository\BlogPostRepositoryInterface
   */
  protected $repository;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('mauticorg_blog.repository')
    );
  }

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\mauticorg_blog\Repository\BlogPostRepositoryInterface $repository
   *   The blog post repository.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    BlogPostRepositoryInterface $repository
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );
    $this->repository = $repository;
  }

  /**
   * Respond to GET requests for this resource.
   *
   * @param string $category
   *   The blog post category name.
   *
   * @return \Drupal\rest\ResourceResponse
   *   A cacheable resource response.
   */
  public function get(string $category): ResourceResponse {
    return $this->createResponse($this->repository->findByCategory($category));
  }

}
