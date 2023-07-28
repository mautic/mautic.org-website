<?php

declare(strict_types = 1);

namespace Drupal\mauticorg_blog\Cache;

use Drupal\rest\ResourceResponse;

/**
 * Provides a cacheable resource response trait.
 */
trait CacheableResourceResponseTrait {

  /**
   * Create a cacheable resource response.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   A list of entities to return in the response.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The cacheable resource response.
   */
  public function createResponse(array $entities): ResourceResponse {
    $response = new ResourceResponse($entities);

    foreach ($entities as $entity) {
      $response->addCacheableDependency($entity);
    }

    return $response;
  }

}
