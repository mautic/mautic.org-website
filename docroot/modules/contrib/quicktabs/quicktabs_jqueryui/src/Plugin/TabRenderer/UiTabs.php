<?php

namespace Drupal\quicktabs_jqueryui\Plugin\TabRenderer;

use Drupal\quicktabs\TabRendererBase;
use Drupal\quicktabs\Entity\QuickTabsInstance;
use Drupal\Core\Template\Attribute;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'ui tabs' tab renderer.
 *
 * @TabRenderer(
 *   id = "ui_tabs",
 *   name = @Translation("jquery ui"),
 * )
 */
class UiTabs extends TabRendererBase {

  /**
   * {@inheritdoc}
   */
  public function render(QuickTabsInstance $instance) {
    $qt_id = $instance->id();
    $type = \Drupal::service('plugin.manager.tab_type');

    // The render array used to build the block.
    $build = [];
    $build['pages'] = [];

    // Pages of content that will be shown or hidden.
    $tab_pages = [];

    // Tabs used to show/hide content.
    $titles = [];

    foreach ($instance->getConfigurationData() as $index => $tab) {
      $object = $type->createInstance($tab['type']);
      $render = $object->render($tab);

      // If user wants to hide empty tabs and there is no content
      // then skip to next tab.
      if ($instance->getHideEmptyTabs() && empty($render)) {
        continue;
      }

      $tab_num = $index + 1;
      $attributes = new Attribute(['id' => 'qt-' . $qt_id . '-ui-tabs' . $tab_num]);

      if (!empty($tab['content'][$tab['type']]['options']['display_title']) && !empty($tab['content'][$tab['type']]['options']['block_title'])) {
        $build['pages'][$index]['#title'] = $tab['content'][$tab['type']]['options']['block_title'];
      }

      $build['pages'][$index]['#block'] = $render;
      $build['pages'][$index]['#prefix'] = '<div ' . $attributes . '>';
      $build['pages'][$index]['#suffix'] = '</div>';
      $build['pages'][$index]['#theme'] = 'quicktabs_block_content';

      $href = '#qt-' . $qt_id . '-ui-tabs' . $tab_num;
      $titles[] = ['#markup' => '<a href="' . $href . '">' . new TranslatableMarkup($tab['title']) . '</a>'];

      $tab_pages[] = $tab;
    }

    // Add a wrapper.
    $build['#theme_wrappers'] = [
      'container' => [
        '#attributes' => [
          'class' => ['quicktabs-ui-wrapper'],
          'id' => 'quicktabs-' . $qt_id,
        ],
      ],
    ];

    $tabs = [
      '#theme' => 'item_list',
      '#items' => $titles,
    ];

    // Add tabs to the build.
    array_unshift($build, $tabs);

    // Attach js.
    $default_tab = $instance->getDefaultTab();
    $build['#attached'] = [
      'library' => ['quicktabs_jqueryui/quicktabs.ui'],
      'drupalSettings' => [
        'quicktabs' => [
          'qt_' . $qt_id => [
            'tabs' => $tab_pages,
            'default_tab' => $default_tab,
          ],
        ],
      ],
    ];

    return $build;
  }

}
