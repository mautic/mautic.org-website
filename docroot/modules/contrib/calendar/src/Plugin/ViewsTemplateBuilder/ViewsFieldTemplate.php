<?php

namespace Drupal\calendar\Plugin\ViewsTemplateBuilder;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views_templates\Plugin\ViewsDuplicateBuilderBase;
use Drupal\views_templates\ViewsTemplateLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views Template for all calendar fields.
 *
 * @ViewsBuilder(
 *  id = "calendar_field",
 *  module = "calendar",
 *  deriver = "Drupal\calendar\Plugin\Derivative\ViewsFieldTemplate"
 * )
 */
class ViewsFieldTemplate extends ViewsDuplicateBuilderBase {

  /**
   * The entity field manager object.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsTemplateLoaderInterface $loader, EntityFieldManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $loader);
    $this->fieldManager = $manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('views_templates.loader'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getReplacements($options = NULL) {
    $replacements = parent::getReplacements($options);
    if (isset($options['base_path'])) {
      $replacements['__BASE_PATH'] = $options['base_path'];
    }
    return $replacements;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterViewTemplateAfterCreation(array &$view_template, array $options = NULL) {
    parent::alterViewTemplateAfterCreation($view_template, $options);
    $field_defs = $this->fieldManager->getBaseFieldDefinitions($this->getDefinitionValue('entity_type'));
    if (empty($field_defs['status'])) {
      // If entity doesn't have a base field status remove it from View filter.
      unset($view_template['display']['default']['display_options']['filters']['status']);
    }
    $this->fieldManager->getFieldDefinitions($this->getDefinitionValue('entity_type'), 'event');
    $this->fieldManager->getFieldStorageDefinitions('node');
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config_form = parent::buildConfigurationForm($form, $form_state);
    $replacements = $this->getDefinitionValue('replacements');
    if (isset($replacements['base_path'])) {
      $config_form['base_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Base View Path'),
        '#description' => $this->t('This view will be displayed by visiting this path on your site.'),
        '#default_value' => $replacements['base_path'],
        '#required' => TRUE,
        // @todo add Validation for path element. From Views?
      ];
    }
    return $config_form;
  }

}
