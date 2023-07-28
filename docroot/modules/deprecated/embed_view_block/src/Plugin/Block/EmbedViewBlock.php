<?php

namespace Drupal\embed_view_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Provides the block for similar articles.
 *
 * @Block(
 *   id = "embed_view_block",
 *   admin_label = @Translation("Embed View block"),
 *   deriver = "Drupal\embed_view_block\Plugin\Derivative\EmbedViewBlock"
 * )
 */
class EmbedViewBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;

    $view_name = $this->getDerivativeId();

    $display_options = [];
    if ($view_name) {
      $view = Views::getView($view_name);
      foreach ($view->storage->get('display') as $name => $display) {
        $display_options[$name] = $display['display_title'] . ' (' . $display['id'] . ')';
      }
    }

    $form['view_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display'),
      '#default_value' => isset($this->configuration['view_display']) ? $this->configuration['view_display'] : NULL,
      '#options' => $display_options,
      // '#validated' => TRUE,
      '#required' => TRUE,
    ];
    /*
    $form['view_display'] = [
    '#type' => 'textfield',
    '#title' => $this->t('Display'),
    '#default_value' => isset($this->configuration['view_display']) ? $this->configuration['view_display'] : NULL,
    '#required' => TRUE,
    '#prefix' => '<div id="edit-view-display-wrapper">',
    '#suffix' => '</div>',
    ]; */

    $form['view_arg'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Argument'),
      '#default_value' => isset($this->configuration['view_arg']) ? $this->configuration['view_arg'] : NULL,
      // '#field_suffix' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    // $this->configuration['view_name'] = $form_state->getValue('view_name');
    $this->configuration['view_display'] = $form_state->getValue('view_display');
    $this->configuration['view_arg'] = $form_state->getValue('view_arg');

  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // $view_name = $this->configuration['view_name'];
    $view_name = $this->getDerivativeId();
    $view_display = $this->configuration['view_display'];
    $view_arg = $this->configuration['view_arg'];

    // Support basic token;
    $types = ['node', 'taxonomy_term', 'user'];
    $token_data = [];
    foreach ($types as $type) {
      $type_entity = \Drupal::routeMatch()->getParameter($type);
      if (!empty($type_entity)) {
        if ($type != 'taxonomy_term') {
          $token_data[$type] = $type_entity;
        }
        else {
          $token_data['term'] = $type_entity;
        }
      }
    }
    $token_service = \Drupal::token();
    $view_arg = $token_service->replace($view_arg, $token_data);

    $view = Views::getView($view_name);
    if (!$view || !$view->access($view_display)) {
      return [];
    }

    $args = explode("/", $view_arg);

    return [
      '#type' => 'view',
      '#name' => $view_name,
      '#display_id' => $view_display,
      '#arguments' => $args,
    ];

  }

}
