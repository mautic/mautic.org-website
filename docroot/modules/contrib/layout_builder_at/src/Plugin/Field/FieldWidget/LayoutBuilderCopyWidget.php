<?php

namespace Drupal\layout_builder_at\Plugin\Field\FieldWidget;

use Drupal\block_content\BlockContentInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\SectionComponent;

/**
 * A widget to display the copy widget form.
 *
 * @FieldWidget(
 *   id = "layout_builder_at_copy",
 *   label = @Translation("Layout Builder Asymmetric Translation"),
 *   description = @Translation("A field widget for Layout Builder. This exposes a checkbox on the entity form to copy the blocks on translation."),
 *   field_types = {
 *     "layout_section",
 *   },
 *   multiple_values = FALSE,
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class LayoutBuilderCopyWidget extends WidgetBase {

  /**
   * Options for appearance.
   *
   * @return array
   */
  protected function options() {
    return [
      'unchecked' => $this->t('Unchecked'),
      'checked' => $this->t('Checked'),
      'checked_hidden' => $this->t('Checked and hidden'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'appearance' => 'unchecked',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['appearance'] = [
      '#type' => 'select',
      '#title' => t('Checkbox appearance'),
      '#options' => $this->options(),
      '#default_value' => $this->getSetting('appearance'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Appearance: @checked', ['@checked' => $this->options()[$this->getSetting('appearance')]]);
    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];

    // Store field information in $form_state.
    if (!static::getWidgetState($parents, $field_name, $form_state)) {
      $field_state = [
        'items_count' => count($items),
        'array_parents' => [],
      ];
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }

    // Collect widget elements.
    $elements = [];

    $delta = 0;
    $element = [
      '#title' => $this->fieldDefinition->getLabel(),
      '#description' => FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription())),
    ];
    $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

    if ($element) {
      if (isset($get_delta)) {
        // If we are processing a specific delta value for a field where the
        // field module handles multiples, set the delta in the result.
        $elements[$delta] = $element;
      }
      else {
        // For fields that handle their own processing, we cannot make
        // assumptions about how the field is structured, just merge in the
        // returned element.
        $elements = $element;
      }
    }

    // Populate the 'array_parents' information in $form_state->get('field')
    // after the form is built, so that we catch changes in the form structure
    // performed in alter() hooks.
    $elements['#after_build'][] = [get_class($this), 'afterBuild'];
    $elements['#field_name'] = $field_name;
    $elements['#field_parents'] = $parents;
    // Enforce the structure of submitted values.
    $elements['#parents'] = array_merge($parents, [$field_name]);
    // Most widgets need their internal structure preserved in submitted values.
    $elements += ['#tree' => TRUE];

    $return = [
      // Aid in theming of widgets by rendering a classified container.
      '#type' => 'container',
      // Assign a different parent, to keep the main id for the widget itself.
      '#parents' => array_merge($parents, [$field_name . '_wrapper']),
      '#attributes' => [
        'class' => [
          'field--type-' . Html::getClass($this->fieldDefinition->getType()),
          'field--name-' . Html::getClass($field_name),
          'field--widget-' . Html::getClass($this->getPluginId()),
        ],
      ],
      'widget' => $elements,
    ];

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $entity = $items->getEntity();
    $access = FALSE;

    if ($entity instanceof ContentEntityInterface) {
      $access = $entity->isNewTranslation() && !$entity->isDefaultTranslation();
    }

    $element['#layout_builder_at_access'] = $access;

    $checked = FALSE;
    $v = $this->getSetting('appearance');
    if ($v == 'checked' || $v == 'checked_hidden') {
      $checked = TRUE;
    }
    $element['value'] = $element + [
      '#access' => TRUE,
      '#type' => 'checkbox',
      '#default_value' => $checked,
      '#title' => $this->t('Copy blocks into translation'),
    ];

    if ($v == 'checked_hidden') {
      $element['value']['#access'] = FALSE;
    }

    return $element;
  }

  /**
   * Extract form values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    // @todo This isn't resilient to being set twice, during validation and
    //   save https://www.drupal.org/project/drupal/issues/2833682.
    if (!$form_state->isValidationComplete()) {
      return;
    }

    $field_name = $this->fieldDefinition->getName();

    // We can only copy if the field is set and access is TRUE.
    if (isset($form[$field_name]['widget']['#layout_builder_at_access']) && !$form[$field_name]['widget']['#layout_builder_at_access']) {
      return;
    }

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], [$field_name]);
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    $values = $this->massageFormValues($values, $form, $form_state);
    if (isset($values['value']) && $values['value']) {

      // Replicate.
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      /** @var \Drupal\Core\Entity\ContentEntityInterface $default_entity */
      $entity = $items->getEntity();

      $sourceLanguage = NULL;
      if ($form_state->hasValue('source_langcode')) {
        $sourceLanguageArray = $form_state->getValue('source_langcode');
        if (isset($sourceLanguageArray['source'])) {
          $sourceLanguage = $sourceLanguageArray['source'];
        }
      }

      $default_entity = is_null($sourceLanguage) ? $entity->getUntranslated() : $entity->getTranslation($sourceLanguage);

      /** @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $layout */
      $layout = $default_entity->get(OverridesSectionStorage::FIELD_NAME);
      $uuid = \Drupal::service('uuid');

      /** @var \Drupal\layout_builder\Section[] $sections */
      $sections = $layout->getSections();
      $new_sections = [];
      foreach ($sections as $delta => $section) {
        $cloned_section = clone $section;

        // Remove components from the cloned section.
        foreach ($cloned_section->getComponents() as $c) {
          $cloned_section->removeComponent($c->getUuid());
        }

        // Sort the components by weight.
        $components = $section->getComponents();
        uasort($components, function (SectionComponent $a, SectionComponent $b) {
          return $a->getWeight() > $b->getWeight() ? 1 : -1;
        });

        foreach ($components as $component) {
          $add_component = TRUE;
          $cloned_component = clone $component;
          $configuration = $component->get('configuration');

          // Replicate inline block content.
          if ($this->isInlineBlock($configuration['id'])) {

            /** @var \Drupal\block_content\BlockContentInterface $block */
            /** @var \Drupal\block_content\BlockContentInterface $replicated_block */
            $block = \Drupal::service('entity_type.manager')->getStorage('block_content')->loadRevision($configuration['block_revision_id']);
            $replicated_block = $this->cloneEntity('block_content', $block->id());
            if ($replicated_block) {
              $replicated_block->set('langcode', $entity->language()->getId());
              $replicated_block->save();
              $configuration = $this->updateComponentConfiguration($configuration, $replicated_block);
              $cloned_component->setConfiguration($configuration);

              // Store usage.
              \Drupal::service('inline_block.usage')->addUsage($replicated_block->id(), $entity);
            }
            else {
              $add_component = FALSE;
              $this->messenger()->addMessage($this->t('The inline block "@label" was not duplicated.', ['@label' => $block->label()]));
            }
          }

          // Add component.
          if ($add_component) {
            $cloned_component->set('uuid', $uuid->generate());
            $cloned_section->appendComponent($cloned_component);
          }
        }

        $new_sections[] = $cloned_section;
      }

      $items->setValue($new_sections);
    }
    else {
      $items->setValue(NULL);
    }
  }

  /**
   * Replicate an entity.
   *
   * @param $entity_type_id
   * @param $entity_id
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
  */
  protected function cloneEntity($entity_type_id, $entity_id) {
    $clone = NULL;

    try {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      /** @var \Drupal\Core\Entity\EntityInterface $clone */
      $entity = \Drupal::service('entity_type.manager')->getStorage($entity_type_id)->load($entity_id);
      $clone = $entity->createDuplicate();

      /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions */
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
      foreach ($field_definitions as $definition) {

        // Support for Entity reference revisions.
        if ($definition->getFieldStorageDefinition()->getType() == 'entity_reference_revisions') {
          $new_values = [];
          $target_type = $definition->getFieldStorageDefinition()->getSetting('target_type');
          $values = $clone->get($definition->getName())->getValue();
          if (!empty($values)) {
            foreach ($values as $value) {
              /** @var \Drupal\Core\Entity\EntityInterface $reference */
              /** @var \Drupal\Core\Entity\EntityInterface $reference_clone */
              $reference = \Drupal::service('entity_type.manager')->getStorage($target_type)->load($value['target_id']);
              $reference_clone = $reference->createDuplicate();
              $reference_clone->save();
              $new_values[] = [
                'target_id' => $reference_clone->id(),
                'target_revision_id' => $reference_clone->getRevisionId(),
              ];
            }

            if (!empty($new_values)) {
              $clone->set($definition->getName(), $new_values);
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('layout_builder_at')->error('Error cloning entity: @message', ['@message' => $e->getMessage()]);
    }

    return $clone;
  }

  /**
   * Does the block id represent an inline block.
   *
   * @param $block_id
   *   The block id.
   * @return bool
   *   True if this is an inline block else false.
   */
  protected function isInlineBlock($block_id) {
    return substr($block_id, 0, 13) === 'inline_block:';
  }

  /**
   * Modify the supplied component configuration based on modified block.
   *
   * @param array $configuration
   *   The Layout Builder component configuration array.
   * @param BlockContentInterface $replicated_block
   *   The cloned block.
   * @return array
   *   A modified configuration array.
   */
  protected function updateComponentConfiguration(array $configuration, BlockContentInterface $replicated_block) {
    $configuration["block_revision_id"] = $replicated_block->getRevisionId();
    return $configuration;
  }
}
