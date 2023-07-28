<?php

namespace Drupal\quicktabs\Plugin\TabType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\quicktabs\TabTypeBase;
use Drupal\views\Views;

/**
 * Provides a 'view content' tab type.
 *
 * @TabType(
 *   id = "view_content",
 *   name = @Translation("view"),
 * )
 */
class ViewContent extends TabTypeBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function optionsForm(array $tab) {
    $plugin_id = $this->getPluginDefinition()['id'];
    $views = $this->getViews();
    $views_keys = array_keys($views);
    $selected_view = (isset($tab['content'][$plugin_id]['options']['vid']) ? $tab['content'][$plugin_id]['options']['vid'] : (isset($views_keys[0]) ? $views_keys[0] : ''));

    $form = [];
    $form['vid'] = [
      '#type' => 'select',
      '#options' => $views,
      '#default_value' => $selected_view,
      '#title' => $this->t('Select a view'),
      '#ajax' => [
        'callback' => [static::class, 'viewsDisplaysAjaxCallback'],
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => 'Please wait...',
        ],
        'effect' => 'fade',
      ],
    ];
    $form['display'] = [
      '#type' => 'select',
      '#title' => 'display',
      '#options' => ViewContent::getViewDisplays($selected_view),
      '#default_value' => isset($tab['content'][$plugin_id]['options']['display']) ? $tab['content'][$plugin_id]['options']['display'] : '',
      '#prefix' => '<div id="view-display-dropdown-' . $tab['delta'] . '">',
      '#suffix' => '</div>',
    ];
    $form['args'] = [
      '#type' => 'textfield',
      '#title' => 'arguments',
      '#size' => '40',
      '#required' => FALSE,
      '#default_value' => isset($tab['content'][$plugin_id]['options']['args']) ? $tab['content'][$plugin_id]['options']['args'] : '',
      '#description' => $this->t('Additional arguments to send to the view as if they were part of the URL in the form of arg1/arg2/arg3. You may use %1, %2, ..., %N to grab arguments from the URL.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $tab) {
    $options = $tab['content'][$tab['type']]['options'];
    $args = empty($options['args']) ? [] : array_map('trim', explode('/', $options['args']));

    if (isset($args)) {
      $current_path = \Drupal::service('path.current')->getPath();

      // If the request is a ajax callback we need to use $_SERVER['HTTP_REFERER'] to get current path.
      if (strpos($current_path, '/quicktabs/ajax/') !== FALSE) {
        $request = \Drupal::request();
        if ($request->server->get('HTTP_REFERER')) {
          $referer = parse_url($request->server->get('HTTP_REFERER'), PHP_URL_PATH);

          // Stripping the language path prefix.
          $current_language = \Drupal::service('language_manager')->getCurrentLanguage()->getId();
          $path = str_replace("/$current_language/", '/', $referer);

          $current_path = \Drupal::service('path_alias.manager')->getPathByAlias($path);
        }

      }
      $url_args = explode('/', $current_path);
      foreach ($url_args as $id => $arg) {
        $args = str_replace("%$id", $arg, $args);
      }
      $args = preg_replace(',/?(%\d),', '', $args);
    }
    $view = Views::getView($options['vid']);

    // Return empty render array if user doesn't have access.
    if (!$view->access($options['display'], \Drupal::currentUser())) {
      return [];
    }

    // Return empty if the view is empty.
    $view_results = views_get_view_result($options['vid'], $options['display']);
    if (!$view_results && !empty($options['vid']) && !empty($options['display'])) {
      // If the initial view is empty, check the attachments.
      $view = Views::getView($options['vid']);
      $view->setDisplay($options['display']);
      $display = $view->getDisplay();
      $attachments = $display->getAttachedDisplays();

      // If there are attachments, check if they are empty.
      if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
          if (!empty(views_get_view_result($options['vid'], $attachment))) {
            $view_results = TRUE;
            continue;
          }
        }
      }
    }

    if (empty($view_results)) {
      return [];
    }

    else {
      $render = $view->buildRenderable($options['display'], $args);
    }

    // Set additional cache keys that depend on the arguments provided for this
    // view.
    // Until this is fixed in core:
    // https://www.drupal.org/project/drupal/issues/2823914
    $render['#cache']['keys'] = array_merge($render['#cache']['keys'], $args);

    return $render;
  }

  /**
   * Ajax callback to change views displays when view is selected.
   */
  public static function viewsDisplaysAjaxCallback(array &$form, FormStateInterface $form_state) {
    $tab_index = $form_state->getTriggeringElement()['#array_parents'][2];
    $element_id = '#view-display-dropdown-' . $tab_index;
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new ReplaceCommand($element_id, $form['configuration_data_wrapper']['configuration_data'][$tab_index]['content']['view_content']['options']['display']));

    return $ajax_response;
  }

  /**
   * Get list of enabled views.
   */
  private function getViews() {
    $views = [];
    foreach (Views::getEnabledViews() as $view_name => $view) {
      $views[$view_name] = $view->label() . ' (' . $view_name . ')';
    }

    ksort($views);
    return $views;
  }

  /**
   * Get displays for a given view.
   */
  public function getViewDisplays($view_name) {
    $displays = [];
    if (empty($view_name)) {
      return $displays;
    }

    $view = \Drupal::entityTypeManager()->getStorage('view')->load($view_name);
    if (!$view) {
      return $displays;
    }
    foreach ($view->get('display') as $id => $display) {
      $enabled = !empty($display['display_options']['enabled']) || !array_key_exists('enabled', $display['display_options']);
      if ($enabled) {
        $displays[$id] = $id . ': ' . $display['display_title'];
      }
    }

    return $displays;
  }

}
