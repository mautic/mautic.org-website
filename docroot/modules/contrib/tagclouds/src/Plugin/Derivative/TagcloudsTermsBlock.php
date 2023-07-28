<?php

namespace Drupal\tagclouds\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Derivative\DeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides dynamic definitions for tagclouds blocks based on Voacabularies.
 *
 * @see \Drupal\tagclouds\Plugin\Block\TagcloudsTermsBlock
 * @see plugin_api
 */
class TagcloudsTermsBlock extends DeriverBase implements DeriverInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (Vocabulary::loadMultiple() as $voc) {
      $this->derivatives[$voc->id()] = $base_plugin_definition;
      $this->derivatives[$voc->id()]['admin_label'] = $this->t('Tags in @voc', ['@voc' => $voc->label()]);
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
