<?php

namespace Drupal\mauticorg_form\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The settings form.
 */
class MauticorgFormSettings extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'mauticorg_form.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mauticorg_form_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['js_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JS Code'),
      '#description' => $this->t('Add js code without script tags.'),
      '#default_value' => $config->get('js_code'),
    ];

    $form['css_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CSS Code'),
      '#description' => $this->t('Add css code without style tags.'),
      '#default_value' => $config->get('css_code'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->configFactory->getEditable(static::SETTINGS)
      ->set('js_code', $form_state->getValue('js_code'))
      ->set('css_code', $form_state->getValue('css_code'))
      ->save();

  }

}
