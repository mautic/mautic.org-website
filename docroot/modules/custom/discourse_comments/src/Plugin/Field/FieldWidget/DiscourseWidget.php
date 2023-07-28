<?php

namespace Drupal\discourse_comments\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\discourse_comments\DiscourseApiClient;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'discourse_widget' widget.
 *
 * @FieldWidget(
 *   id = "discourse_widget",
 *   module = "discourse_comments",
 *   label = @Translation("Discourse widget"),
 *   field_types = {
 *     "discourse_field"
 *   }
 * )
 */
class DiscourseWidget extends WidgetBase {

  /**
   * Discourse Api Client service.
   *
   * @var \Drupal\discourse_comments\DiscourseApiClient
   */
  private $discourseApiClient;
  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;
  /**
   * Route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, DiscourseApiClient $discourse_api_client, ConfigFactory $config_factory, RouteMatchInterface $route_match) {
    $this->discourseApiClient = $discourse_api_client;
    $this->configFactory = $config_factory;
    $this->routeMatch = $route_match;
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('discourse_comments.discourse_api_client'), $container->get('config.factory'), $container->get('current_route_match'));
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $node = $this->routeMatch->getParameter('node');
    $node_type = $this->routeMatch->getParameter('node_type');
    $content_type = NULL;
    if ($node_type != NULL) {
      $content_type = $node_type->id();
    }
    if ($node != NULL && $node instanceof Node) {
      $content_type = $node->getType();
    }

    $discourse_config = $this->configFactory->get('discourse_comments.discourse_comments_settings');
    $content_types_enabled_for_discourse = $discourse_config->get('content_types_enabled_for_discourse');
    $default_content_type_setting = 0;
    if (isset($content_types_enabled_for_discourse[$content_type]) && $content_types_enabled_for_discourse[$content_type]) {
      $default_content_type_setting = 1;
    }

    $element['warning'] = [
      '#type' => 'item',
      '#title' => $this->t('Warning'),
      '#markup' => $this->t('Further changes to this form do not reflect on the Discourse post after the initial publish.'),
    ];

    $element['push_to_discourse'] = [
      '#title' => $this->t('Push node to Discourse forum'),
      '#description' => $this->t('NOTE: Disabling this after the node is
        published to Discourse will not remove the post on Discourse.'),
      '#type' => 'checkbox',
      '#default_value' => isset($items[$delta]->push_to_discourse) ? $items[$delta]->push_to_discourse : $default_content_type_setting,
    ];

    if (isset($discourse_config) && $discourse_config->get('base_url_of_discourse') != '') {
      $options = [];
      $default_category = $discourse_config->get('default_category');
      $category_content = $this->discourseApiClient->getCategories();
      if ($category_content) {
        foreach ($category_content['category_list']['categories'] as $cat) {
          $options[$cat['id']] = $cat['name'];
        }
        $element['category'] = [
          '#type' => 'select',
          '#title' => $this->t('Category to push this node to'),
          '#options' => $options,
          '#default_value' => isset($items[$delta]->category) ? $items[$delta]->category : $default_category,
        ];
      }
    }

    $element['topic_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Topic id on Discourse'),
      '#default_value' => isset($items[$delta]->topic_id) ? $items[$delta]->topic_id : NULL,
      '#size' => 5,
      '#disabled' => TRUE,
    ];

    $element['topic_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discourse Topic URL'),
      '#default_value' => isset($items[$delta]->topic_url) ? $items[$delta]->topic_url : NULL,
      '#size' => 60,
      '#placeholder' => '',
      '#maxlength' => 256,
      '#disabled' => TRUE,
    ];

    $element['comment_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Comment Count'),
      '#default_value' => isset($items[$delta]->comment_count) ? $items[$delta]->comment_count : 0,
      '#size' => 3,
      '#disabled' => TRUE,
    ];

    $element += [
      '#type' => 'details',
      '#group' => 'advanced',
      '#weight' => 0,
    ];

    return $element;
  }

}
