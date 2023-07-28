<?php

namespace Drupal\slick_devel;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Slick admin settings form.
 */
class SlickDevelSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Asset\LibraryDiscoveryInterface definition.
   *
   * @var Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LibraryDiscoveryInterface $library_discovery) {
    parent::__construct($config_factory);
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'slick_devel_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['slick_devel.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('slick_devel.settings');

    $form['slick_devel'] = [
      '#type' => 'details',
      '#title' => 'Slick development',
      '#description' => $this->t("Unless you are helping to develop the Slick module, all these are not needed to run Slick. Requires slick > 1.6.0"),
      '#open' => TRUE,
      '#collapsible' => FALSE,
    ];

    $form['slick_devel']['unminified'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable development mode'),
      '#description' => $this->t('Load the development version of the Slick library. Only useful to test new features of the library. Leave it unchecked at production.'),
      '#default_value' => $config->get('unminified'),
    ];

    $form['slick_devel']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use non-minified slick.load.js'),
      '#description' => $this->t('Replace slick.load.min.js with slick.load.js. Only useful to debug it.'),
      '#default_value' => $config->get('debug'),
    ];

    $form['slick_devel']['disable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable module slick.load.js'),
      '#description' => $this->t('Slick will not run unless you initiliaze it yourself.'),
      '#default_value' => $config->get('disable'),
      '#states' => [
        'invisible' => [
          [':input[name="debug"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['slick_devel']['replace'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace the slick.load.js with development version: slick.load.devel.js'),
      '#description' => $this->t('Use slick.load.devel.js to debug the Slick without modifying slick.load.min.js.'),
      '#default_value' => $config->get('replace'),
      '#states' => [
        'invisible' => [
          [':input[name="disable"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('slick_devel.settings')
      ->set('unminified', $form_state->getValue('unminified'))
      ->set('debug', $form_state->getValue('debug'))
      ->set('replace', $form_state->getValue('replace'))
      ->set('disable', $form_state->getValue('disable'))
      ->save();

    // Invalidate the library discovery cache to update new assets.
    $this->libraryDiscovery->clearCachedDefinitions();
    $this->configFactory->clearStaticCache();

    parent::submitForm($form, $form_state);
  }

}
