<?php

namespace Drupal\mauticorg_gravatar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The settings form.
 */
class MauticorgGravatarSettings extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'mauticorg_gravatar.settings';

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
    return 'mauticorg_gravatar_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['gravatar_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gravatar URL'),
      '#description' => $this->t('Insert gravatar URL with placeholder @email. Example: https://secure.gravatar.com/avatar/@email?s=60&d=mm&r=g'),
      '#default_value' => $config->get('gravatar_url'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->configFactory->getEditable(static::SETTINGS)
      ->set('gravatar_url', $form_state->getValue('gravatar_url'))
      ->save();

  }

}
