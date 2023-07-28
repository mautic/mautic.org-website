<?php

namespace Drupal\mauticorg_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The settings form.
 */
class MauticorgSiteSettings extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'mauticorg_core.site_settings';

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
    return 'mauticorg_site_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['copyright_content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Copyright Content'),
      '#description' => $this->t('Insert Copyright Content.'),
      '#default_value' => $config->get('copyright_content')['value'],
      '#format' => $config->get('copyright_content')['format'],
    ];

    $form['bottom_block_content_page_404'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Bottom Block Content of Page 404'),
      '#description' => $this->t('Insert bottom block content of page 404.'),
      '#default_value' => $config->get('bottom_block_content_page_404')['value'],
      '#format' => $config->get('bottom_block_content_page_404')['format'],
    ];

    $form['bottom_block_content_page_403'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Bottom Block Content of Page 403'),
      '#description' => $this->t('Insert bottom block content of page 403.'),
      '#default_value' => $config->get('bottom_block_content_page_403')['value'],
      '#format' => $config->get('bottom_block_content_page_403')['format'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->configFactory->getEditable(static::SETTINGS)
      ->set('copyright_content', $form_state->getValue('copyright_content'))
      ->set('bottom_block_content_page_404', $form_state->getValue('bottom_block_content_page_404'))
      ->set('bottom_block_content_page_403', $form_state->getValue('bottom_block_content_page_403'))
      ->save();

  }

}
