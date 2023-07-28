<?php

namespace Drupal\quicktabs\Plugin\TabRenderer;

use Drupal\quicktabs\TabRendererBase;
use Drupal\quicktabs\Entity\QuickTabsInstance;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Component\Utility\Html;

/**
 * Provides a 'QuickTabs' tab renderer.
 *
 * @TabRenderer(
 *   id = "quick_tabs",
 *   name = @Translation("quicktabs"),
 * )
 */
class QuickTabs extends TabRendererBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function optionsForm(QuickTabsInstance $instance) {
    $options = $instance->getOptions()['quick_tabs'];
    $renderer = $instance->getRenderer();
    $form['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Tab Class'),
      '#description' => $this->t('Additional classes to provide on each tab. Separated by a space.'),
      '#default_value' => ($renderer == 'quick_tabs' && isset($options['class']) && $options['class']) ? $options['class'] : '',
      '#weight' => -8,
    ];
    $form['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#options' => [
        '' => $this->t('-None-'),
        'pamela' => $this->t('Pamela'),
        'on-the-gray' => $this->t('On the Gray'),
        'tabsbar' => $this->t('Tabs Bar'),
        'material-tabs' => $this->t('Material Tabs'),
      ],
      '#default_value' => ($renderer == 'quick_tabs' && isset($options['style']) && $options['style']) ? $options['style'] : '',
      '#weight' => -7,
    ];
    $form['ajax'] = [
      '#type' => 'radios',
      '#title' => $this->t('Ajax'),
      '#options' => [
        TRUE => $this->t('Yes: Load only the first tab on page view'),
        FALSE => $this->t('No: Load all tabs on page view.'),
      ],
      '#default_value' => ($renderer == 'quick_tabs' && $options['ajax'] !== NULL) ? intval($options['ajax']) : 0,
      '#description' => $this->t('Choose how the content of tabs should be loaded.<p>By choosing "Yes", only the first tab will be loaded when the page first viewed. Content for other tabs will be loaded only when the user clicks the other tab. This will provide faster initial page loading, but subsequent tab clicks will be slower. This can place less load on a server.</p><p>By choosing "No", all tabs will be loaded when the page is first viewed. This will provide slower initial page loading, and more server load, but subsequent tab clicks will be faster for the user. Use with care if you have heavy views.</p><p>Warning: if you enable Ajax, any block you add to this quicktabs block will be accessible to anonymous users, even if you place role restrictions on the quicktabs block. Do not enable Ajax if the quicktabs block includes any blocks with potentially sensitive information.</p>'),
      '#weight' => -6,
    ];
    return $form;
  }

  /**
   * Returns a render array to be used in a block or page.
   *
   * @return array
   *   A render array.
   */
  public function render(QuickTabsInstance $instance) {
    $qt_id = $instance->id();
    $type = \Drupal::service('plugin.manager.tab_type');

    // The render array used to build the block.
    $build = [];
    $build['pages'] = [];
    $build['pages']['#theme_wrappers'] = [
      'container' => [
        '#attributes' => [
          'class' => ['quicktabs-main'],
          'id' => 'quicktabs-container-' . $qt_id,
        ],
      ],
    ];

    // Pages of content that will be shown or hidden.
    $tab_pages = [];

    // Tabs used to show/hide content.
    $titles = [];

    $is_ajax = $instance->getOptions()['quick_tabs']['ajax'];
    $style = $instance->getOptions()['quick_tabs']['style'] ?? '';
    $custom_class = $instance->getOptions()['quick_tabs']['class'] ?? '';
    foreach ($instance->getConfigurationData() as $index => $tab) {
      // Build the pages //////////////////////////////////////.
      $default_tab = $instance->getDefaultTab() == 9999 ? 0 : $instance->getDefaultTab();
      if ($is_ajax) {
        if ($default_tab == $index) {
          $object = $type->createInstance($tab['type']);
          $render = $object->render($tab);
        }
        else {
          $render = ['#markup' => $this->t('Loading content ...')];
        }
      }
      else {
        $object = $type->createInstance($tab['type']);
        $render = $object->render($tab);
      }

      // If user wants to hide empty tabs and there is no content
      // then skip to next tab.
      if ($instance->getHideEmptyTabs() && empty($render)) {
        continue;
      }

      $classes = ['quicktabs-tabpage'];

      if ($default_tab != $index) {
        $classes[] = 'quicktabs-hide';
      }

      $render['#prefix'] = '<div>';
      $render['#suffix'] = '</div>';
      $block_id = 'quicktabs-tabpage-' . $qt_id . '-' . $index;
      $tab_id = 'quicktabs-tab-' . $qt_id . '-' . $index;

      if (!empty($tab['content'][$tab['type']]['options']['display_title']) && !empty($tab['content'][$tab['type']]['options']['block_title'])) {
        $build['pages'][$index]['#title'] = $tab['content'][$tab['type']]['options']['block_title'];
      }
      $build['pages'][$index]['#block'] = $render;
      $build['pages'][$index]['#classes'] = implode(' ', $classes);
      $build['pages'][$index]['#id'] = $block_id;
      $build['pages'][$index]['#tabid'] = $tab_id;
      $build['pages'][$index]['#theme'] = 'quicktabs_block_content';

      // Build the tabs ///////////////////////////////.
      $wrapper_attributes = [
        'role' => 'tab',
        'aria-controls' => $block_id,
        'aria-selected' => 'false',
        'id' => $tab_id,
        'tabIndex' => '-1',
      ];
      if ($default_tab == $index) {
        $wrapper_attributes['class'] = ['active'];
        $wrapper_attributes['aria-selected'] = 'true';
        $wrapper_attributes['tabindex'] = '0';
      }
      if ($custom_class) {
        $wrapper_attributes['class'][] = $custom_class;
      }
      $title_class = strip_tags($tab['title']);
      $wrapper_attributes['class'][] = strtolower(Html::cleanCssIdentifier($title_class));

      $link_classes = [];
      if ($is_ajax) {
        $link_classes[] = 'use-ajax';

        if ($default_tab == $index) {
          $link_classes[] = 'quicktabs-loaded';
        }
      }
      else {
        $link_classes[] = 'quicktabs-loaded';
      }

      $titles[] = [
        '0' => Link::fromTextAndUrl(
          new TranslatableMarkup($tab['title']),
          Url::fromRoute(
            'quicktabs.ajax_content',
            [
              'js' => 'nojs',
              'instance' => $qt_id,
              'tab' => $index,
            ],
            [
              'attributes' => [
                'class' => $link_classes,
                'data-quicktabs-tab-index' => $index,
              ],
            ]
          )
        )->toRenderable(),
        '#wrapper_attributes' => $wrapper_attributes,
      ];

      // Array of tab pages to pass as settings ////////////.
      $tab['tab_page'] = $index;
      $tab_pages[] = $tab;
    }

    $tabs = [
      '#theme' => 'item_list__quicktabs__' . $qt_id,
      '#items' => $titles,
      '#attributes' => [
        'class' => ['quicktabs-tabs'],
        'role' => 'tablist',
      ],
    ];

    // Add tabs to the build.
    array_unshift($build, $tabs);

    // Attach libraries.
    $libraries = ['quicktabs/quicktabs'];
    if ($style) {
      $libraries[] = 'quicktabs/' . $style;
    }
    $build['#attached'] = [
      'library' => $libraries,
      'drupalSettings' => [
        'quicktabs' => [
          'qt_' . $qt_id => [
            'tabs' => $tab_pages,
          ],
        ],
      ],
    ];

    // Add a wrapper.
    $classes = ['quicktabs-wrapper'];
    if ($style) {
      $classes[] = $style;
    }
    $build['#theme_wrappers'] = [
      'container' => [
        '#attributes' => [
          'class' => $classes,
          'id' => 'quicktabs-' . $qt_id,
        ],
      ],
    ];

    return $build;
  }

}
