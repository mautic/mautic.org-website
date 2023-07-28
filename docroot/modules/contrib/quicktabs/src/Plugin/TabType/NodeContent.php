<?php

namespace Drupal\quicktabs\Plugin\TabType;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\quicktabs\TabTypeBase;

/**
 * Provides a 'node content' tab type.
 *
 * @TabType(
 *   id = "node_content",
 *   name = @Translation("node"),
 * )
 */
class NodeContent extends TabTypeBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function optionsForm(array $tab) {
    $plugin_id = $this->getPluginDefinition()['id'];

    $form = [];
    $form['nid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node'),
      '#description' => $this->t('The node ID of the node.'),
      '#maxlength' => 10,
      '#size' => 20,
      '#default_value' => isset($tab['content'][$plugin_id]['options']['nid']) ? $tab['content'][$plugin_id]['options']['nid'] : '',
    ];
    $display_repository = \Drupal::service('entity_display.repository');
    $view_modes = $display_repository->getViewModes('node');
    $options = [];
    foreach ($view_modes as $view_mode_name => $view_mode) {
      $options[$view_mode_name] = $view_mode['label'];
    }
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $options,
      '#default_value' => isset($tab['content'][$plugin_id]['options']['view_mode']) ? $tab['content'][$plugin_id]['options']['view_mode'] : 'full',
    ];
    $form['hide_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the title of this node'),
      '#default_value' => isset($tab['content'][$plugin_id]['options']['hide_title']) ? $tab['content'][$plugin_id]['options']['hide_title'] : 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $tab) {
    $options = $tab['content'][$tab['type']]['options'];
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($options['nid']);

    if ($node !== NULL) {

      $access_result = $node->access('view', \Drupal::currentUser(), TRUE);
      // Return empty render array if user doesn't have access.
      if ($access_result->isForbidden()) {
        return [];
      }

      $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $options['view_mode']);

      if ($options['hide_title']) {
        $build['#node']->setTitle(NULL);
      }

      return $build;
    }

  }

}
