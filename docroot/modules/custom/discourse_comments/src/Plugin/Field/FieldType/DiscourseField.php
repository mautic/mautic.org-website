<?php

namespace Drupal\discourse_comments\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'discourse_field' field type.
 *
 * @FieldType(
 *   id = "discourse_field",
 *   label = @Translation("Discourse field"),
 *   description = @Translation("Discourse field settings") * ),
 *   default_widget = "discourse_widget",
 */
class DiscourseField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['topic_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Topic id on Discourse'))
      ->setDescription(t('Topic id on Discourse'))
      ->setSetting('case_sensitive', TRUE)
      ->setRequired(FALSE);

    $properties['topic_url'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Discourse Topic URL'))
      ->setDescription(t('Topic URL on Discourse'))
      ->setSetting('case_sensitive', TRUE)
      ->setRequired(FALSE);

    $properties['comment_count'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Comment count for topic on discourse'))
      ->setDescription(t('Topic id on Discourse'))
      ->setSetting('case_sensitive', TRUE)
      ->setRequired(FALSE);

    $properties['push_to_discourse'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Push node to discourse forum'))
      ->setSettings([
        'display_label' => TRUE,
      ]);
    $properties['category'] = DataDefinition::create('integer')
      ->setLabel(t('Category'))
      ->setDescription(t('Category of the topic'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'topic_id' => [
          'type' => 'varchar',
          'length' => 128,
          'binary' => TRUE,
        ],
        'topic_url' => [
          'type' => 'varchar',
          'length' => 256,
          'binary' => TRUE,
        ],
        'comment_count' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'push_to_discourse' => [
          'type' => 'int',
          'length' => 1,
        ],
        'category' => [
          'type' => 'int',
          'length' => 3,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $topic_id = $this->get('topic_id')->getValue();
    return $topic_id === NULL || $topic_id === '';
  }

}
