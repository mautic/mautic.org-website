<?php

namespace Drupal\mautic\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Configure Mautic settings for this site.
 */
class MauticAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mautic_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mautic.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('mautic.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['mautic_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include Mautic Javascript Code'),
      '#default_value' => $config->get('mautic_enable'),
      '#description' => $this->t("If you want to embed the Mautic Javascript Code, enable this check."),
      '#required' => FALSE,
    ];

    $form['general']['mautic_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mautic URL'),
      '#default_value' => $config->get('mautic_base_url'),
      '#states' => [
        'visible' => [
          ':input[name="mautic_enable"]' => ['checked' => TRUE],
        ],
      ],
      '#size' => 60,
      '#description' => $this->t("Your Mautic javascript code. Example: http(s)://yourmautic.com/mtc.js"),
      '#required' => TRUE,
    ];

    $form['general']['header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Should the JS snippet be in the header?'),
      '#default_value' => $config->get('header'),
      '#states' => [
        'visible' => [
          ':input[name="mautic_enable"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => $this->t("As default the snippet is in the footer (it is recommended)"),
    ];

    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('acquia_lift')){
      $form['general']['lift_enable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Include Acquia Personalization Integration Javascript Code'),
        '#default_value' => $config->get('lift_enable'),
        '#description' => $this->t("If you want to embed the Acquia Personalization Integration Javascript Code, enable this check."),
        '#required' => FALSE,
        '#states' => [
          'visible' => [
            ':input[name="mautic_enable"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $form['tracking_page'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Tracking Page'),
    ];

    $mautic_pages_list = $config->get('visibility.request_path_pages');

    $form['tracking']['page_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Pages'),
      '#group' => 'tracking_page',
    ];

    if ($config->get('visibility.request_path_mode') == 2) {
      $form['tracking']['page_visibility_settings'] = [];
      $form['tracking']['page_visibility_settings']['mautic_visibility_request_path_mode'] = ['#type' => 'value', '#value' => 2];
      $form['tracking']['page_visibility_settings']['mautic_visibility_request_path_pages'] = ['#type' => 'value', '#value' => $mautic_pages_list];
    }
    else {
      $options = [
        $this->t('Every page except the listed pages'),
        $this->t('The listed pages only'),
      ];
      $description = $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
        [
          '%blog' => '/blog',
          '%blog-wildcard' => '/blog/*',
          '%front' => '<front>',
        ]
      );
      $title = $this->t('Pages');
      $form['tracking']['page_visibility_settings']['mautic_visibility_request_path_mode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Add tracking to specific pages'),
        '#options' => $options,
        '#default_value' => $config->get('visibility.request_path_mode'),
      ];
      $form['tracking']['page_visibility_settings']['mautic_visibility_request_path_pages'] = [
        '#type' => 'textarea',
        '#title' => $title,
        '#title_display' => 'invisible',
        '#default_value' => !empty($mautic_pages_list) ? $mautic_pages_list : "/admin\n/admin/*\n/batch\n/node/add*\n/node/*/*\n/user/*/*",
        '#description' => $description,
        '#rows' => 10,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);

    $url_is_valid = UrlHelper::isValid($form_state->getValue('mautic_base_url'), $absolute = TRUE);

    // Check if is a valid url.
    if (!$url_is_valid) {
      $form_state->setErrorByName('mautic_base_url', $this->t('The URL is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mautic.settings');
    $config
      ->set('mautic_enable', $form_state->getValue('mautic_enable'))
      ->set('mautic_base_url', $form_state->getValue('mautic_base_url'))
      ->set('visibility.request_path_mode', $form_state->getValue('mautic_visibility_request_path_mode'))
      ->set('visibility.request_path_pages', $form_state->getValue('mautic_visibility_request_path_pages'))
      ->set('header', $form_state->getValue('header'))
      ->set('lift_enable', $form_state->getValue('lift_enable'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
