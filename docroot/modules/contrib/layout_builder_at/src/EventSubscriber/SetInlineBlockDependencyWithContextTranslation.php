<?php

namespace Drupal\layout_builder_at\EventSubscriber;

use Drupal\block_content\BlockContentInterface;
use Drupal\layout_builder\EventSubscriber\SetInlineBlockDependency;

/**
 * Takes over \Drupal\layout_builder\EventSubscriber\SetInlineBlockDependency
 * to load the entity with the correct translation.
 */
class SetInlineBlockDependencyWithContextTranslation extends SetInlineBlockDependency {

  /**
   * Call getTranslationFromContext() on the entity.
   */
  protected function getInlineBlockDependency(BlockContentInterface $block_content) {
    $layout_entity_info = $this->usage->getUsage($block_content->id());
    if (empty($layout_entity_info)) {
      // If the block does not have usage information then we cannot set a
      // dependency. It may be used by another module besides layout builder.
      return NULL;
    }
    $layout_entity_storage = $this->entityTypeManager->getStorage($layout_entity_info->layout_entity_type);
    $layout_entity = $layout_entity_storage->load($layout_entity_info->layout_entity_id);
    $layout_entity = \Drupal::service('entity.repository')->getTranslationFromContext($layout_entity);
    if ($this->isLayoutCompatibleEntity($layout_entity)) {
      if ($this->isBlockRevisionUsedInEntity($layout_entity, $block_content)) {
        return $layout_entity;
      }

    }
    return NULL;
  }

}
