<?php

namespace Drupal\noreqnewpass\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure hello settings for this site.
 */
class NoReqNewPassSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'noreqnewpass_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'noreqnewpass.settings_form',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('noreqnewpass.settings_form');
    $form['noreqnewpass_disable'] = [
      '#type' => 'checkbox',
      '#title' => 'Disable Request new password link',
      '#default_value' => $config->get('noreqnewpass_disable'),
      '#description' => 'If checked, Request new password link will be disabled.',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('noreqnewpass.settings_form')
      ->set('noreqnewpass_disable', $form_state->getValue('noreqnewpass_disable'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
