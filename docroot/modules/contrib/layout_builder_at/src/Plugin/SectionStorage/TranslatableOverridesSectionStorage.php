<?php

namespace Drupal\layout_builder_at\Plugin\SectionStorage;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;

class TranslatableOverridesSectionStorage extends OverridesSectionStorage {

  /**
   * {@inheritdoc}
   */
  protected function handleTranslationAccess(AccessResult $result, $operation, AccountInterface $account) {
    return $result;
  }

}
