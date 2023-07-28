<?php

namespace Drupal\tagclouds\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class TagcloudsAdminPage extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  protected $languageManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    parent::__construct($config_factory);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tagclouds_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tagclouds.settings');
    $options = [
      'title,asc' => $this->t('by title, ascending'), 'title,desc' => $this->t('by title, descending'),
      'count,asc' => $this->t('by count, ascending'), 'count,desc' => $this->t('by count, descending'),
      'random,none' => $this->t('random')
    ];
    $sort_order = $config->get('sort_order');
    $form['sort_order'] = [
      '#type' => 'radios',
      '#title' => $this->t('Tagclouds sort order'),
      '#options' => $options,
      '#default_value' => (!empty($sort_order)) ? $sort_order : 'title,asc',
      '#description' => $this->t('Determines the sort order of the tags on the freetagging page.'),
    ];

    $options_display = ['style' => $this->t('Display Tags with Style'), 'count' => $this->t('Display Tags with Count')];
    $display_type = $config->get('display_type');
    $form['display_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Tagclouds Display Type'),
      '#options' => $options_display,
      '#default_value' => (!empty($display_type)) ? $display_type : 'style',
      '#description' => $this->t('Determines the style of the page.'),
    ];

    $form['display_node_link'] = [
     '#type' => 'checkbox',
     '#title' => $this->t('Link term to node when only one content is tagged'),
     '#default_value' => $config->get('display_node_link'),
     '#description' => $this->t('When there is only one content tagged with a certain term, link that term to this node instead of the term list page.'),
    ];

    $page_amount = $config->get('page_amount');
    $form['page_amount'] = [
      '#type' => 'textfield',
      '#size' => 5,
      '#title' => $this->t('Amount of tags on the pages'),
      '#default_value' => is_numeric($page_amount) ? $page_amount : 60,
      '#description' => $this->t("The amount of tags that will show up in a cloud on the
        pages. Enter '0' to display all tags. Amount of tags in blocks must be
        configured in the block settings of the various cloud blocks."),
    ];

    $levels = $config->get('levels');
    $form['levels'] = [
      '#type' => 'textfield',
      '#size' => 5,
      '#title' => $this->t('Number of levels'),
      '#default_value' => is_numeric($levels) ? $levels : 6,
      '#description' => $this->t('The number of levels between the least popular
        tags and the most popular ones. Different levels will be assigned a different
        class to be themed in tagclouds.css'),
    ];

    $lang = $this->languageManager->getLanguages();
    if (count($lang) > 1) {
      $form['language_separation'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Separation of Tags per language'),
        '#default_value' => $config->get('language_separation'),
        '#description' => $this->t('If you have more than one language installed this setting would allow you to separate the tags for each language.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tagclouds.settings')
      ->set('sort_order', $form_state->getValue('sort_order'))
      ->set('display_type', $form_state->getValue('display_type'))
      ->set('display_node_link', $form_state->getValue('display_node_link'))
      ->set('page_amount', $form_state->getValue('page_amount'))
      ->set('levels', $form_state->getValue('levels'));

    if ($form_state->hasValue('language_separation')) {
      $this->config('tagclouds.settings')
        ->set('language_separation', $form_state->getValue('language_separation'));
    }
    $this->config('tagclouds.settings')->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function getEditableConfigNames() {
    return ['tagclouds.settings'];
  }
}
