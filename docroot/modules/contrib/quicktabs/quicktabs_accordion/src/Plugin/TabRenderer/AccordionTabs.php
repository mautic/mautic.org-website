<?php

namespace Drupal\quicktabs_accordion\Plugin\TabRenderer;

use Drupal\quicktabs\TabRendererBase;
use Drupal\quicktabs\Entity\QuickTabsInstance;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an 'AccordionTabs' tab renderer.
 *
 * @TabRenderer(
 *   id = "accordion_tabs",
 *   name = @Translation("accordion"),
 * )
 */
class AccordionTabs extends TabRendererBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function optionsForm(QuickTabsInstance $instance) {
    $options = $instance->getOptions()['accordion_tabs'];
    $form = [];
    $form['jquery_ui'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('JQuery UI options'),
    ];
    $form['jquery_ui']['collapsible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapsible'),
      '#default_value' => ($options['jquery_ui']['collapsible'] != NULL && $instance->getRenderer() == 'accordion_tabs') ? $options['jquery_ui']['collapsible'] : 0,
    ];
    $form['jquery_ui']['heightStyle'] = [
      '#type' => 'radios',
      '#title' => $this->t('JQuery UI HeightStyle'),
      '#options' => [
        'auto' => $this->t('auto'),
        'fill' => $this->t('fill'),
        'content' => $this->t('content'),
      ],
      '#default_value' => ($options['jquery_ui']['heightStyle'] != NULL && $instance->getRenderer() == 'accordion_tabs') ? $options['jquery_ui']['heightStyle'] : 'auto',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function render(QuickTabsInstance $instance) {
    $qt_id = $instance->id();
    $type = \Drupal::service('plugin.manager.tab_type');

    // The render array used to build the block.
    $build = [];
    $build['pages'] = [];

    // Add a wrapper.
    $build['#theme_wrappers'] = [
      'container' => [
        '#attributes' => [
          'class' => ['quicktabs-accordion'],
          'id' => 'quicktabs-' . $qt_id,
        ],
      ],
    ];

    $tab_pages = [];
    foreach ($instance->getConfigurationData() as $index => $tab) {
      $qsid = 'quickset-' . $qt_id;
      $object = $type->createInstance($tab['type']);
      $render = $object->render($tab);

      // If user wants to hide empty tabs and there is no content
      // then skip to next tab.
      if ($instance->getHideEmptyTabs() && empty($render)) {
        continue;
      }

      if (!empty($tab['content'][$tab['type']]['options']['display_title']) && !empty($tab['content'][$tab['type']]['options']['block_title'])) {
        $build['pages'][$index]['#title'] = $tab['content'][$tab['type']]['options']['block_title'];
      }
      $build['pages'][$index]['#block'] = $render;
      $build['pages'][$index]['#prefix'] = '<h3><a href= "#' . $qsid . '_' . $index . '">' . new TranslatableMarkup($tab['title']) . '</a></h3><div>';
      $build['pages'][$index]['#suffix'] = '</div>';
      $build['pages'][$index]['#theme'] = 'quicktabs_block_content';

      // Array of tab pages to pass as settings ////////////.
      $tab['tab_page'] = $index;
      $tab_pages[] = $tab;
    }

    $options = $instance->getOptions()['accordion_tabs'];
    $active_tab = $instance->getDefaultTab() == 9999 ? 0 : $instance->getDefaultTab();
    $active = $instance->getDefaultTab() == 9999 ? FALSE : (int) $instance->getDefaultTab();
    $collapsible = $instance->getDefaultTab() == 9999 ? TRUE : (int) $options['jquery_ui']['collapsible'];
    $build['#attached'] = [
      'library' => ['quicktabs_accordion/quicktabs.accordion'],
      'drupalSettings' => [
        'quicktabs' => [
          'qt_' . $qt_id => [
            'tabs' => $tab_pages,
            'active_tab' => $active_tab,
            'options' => [
              'active' => $active,
              'heightStyle' => $options['jquery_ui']['heightStyle'],
              'collapsible' => $collapsible,
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

}
