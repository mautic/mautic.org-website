<?php

namespace Drupal\quicktabs;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of quicktabs_instances.
 *
 * @todo Would making this sortable help in specifying the importance of a quicktabs instance?
 */
class QuickTabsInstanceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['storage'] = $this->t('Normal');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['storage'] = $this->t('Storage');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->hasLinkTemplate('edit')) {
      $operations['edit'] = [
        'title' => $this->t('Edit Quick Tab'),
        'weight' => 10,
        'url' => $entity->toUrl('edit'),
      ];
      $operations['delete'] = [
        'title' => $this->t('Delete Quick Tab'),
        'weight' => 20,
        'url' => $entity->toUrl('delete'),
      ];
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate Quick Tab'),
        'weight' => 40,
        'url' => $entity->toUrl('duplicate'),
      ];
    }
    return $operations;
  }

}
