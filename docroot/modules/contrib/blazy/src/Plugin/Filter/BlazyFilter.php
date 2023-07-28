<?php

namespace Drupal\blazy\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\blazy\BlazyUtil;

/**
 * Provides a filter to lazyload image, or iframe elements.
 *
 * Best after Align images, caption images.
 *
 * @Filter(
 *   id = "blazy_filter",
 *   title = @Translation("Blazy"),
 *   description = @Translation("Lazyload inline images, or video iframes using Blazy."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "filter_tags" = {"img" = "img", "iframe" = "iframe"},
 *     "media_switch" = "",
 *     "box_style" = "",
 *     "hybrid_style" = "",
 *     "use_data_uri" = "0",
 *   },
 *   weight = 3
 * )
 */
class BlazyFilter extends BlazyFilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $this->result = $result = new FilterProcessResult($text);
    $this->langcode = $langcode;

    $allowed_tags = array_values((array) $this->settings['filter_tags']);
    if (empty($text)) {
      return $result;
    }

    $attachments = $grid_items = $grid_nodes = [];
    $settings = $this->buildSettings($text);

    if (stristr($text, '[blazy') !== FALSE) {
      $text = BlazyFilterUtil::unwrap($text, 'blazy', 'item');
    }

    $dom = Html::load($text);

    // Works with individual images and or iframes.
    if (!empty($allowed_tags)) {
      $nodes = BlazyFilterUtil::validNodes($dom, $allowed_tags, 'data-unblazy');
      if (count($nodes) > 0) {
        foreach ($nodes as $delta => $node) {
          $settings['delta'] = $delta;

          if ($output = $this->build($node, $settings)) {
            // @todo remove deprecated too-catch-all _grid post Blazy 3.x.
            if ($settings['_grid']) {
              $grid_items[] = $output;
              $grid_nodes[] = $node;
            }
            else {
              $this->render($node, $output);
            }
          }
        }
      }
    }

    // Works with grids and entities, not always images or iframes.
    $nodes = BlazyFilterUtil::validNodes($dom, ['blazy']);
    if (count($nodes) > 0) {
      foreach ($nodes as $delta => $node) {
        $settings['delta'] = $delta;
        if ($output = $this->build($node, $settings)) {
          $this->render($node, $output);
        }
      }
    }

    // Builds the grids if so provided via [data-column], or [data-grid].
    // @todo deprecated for grid shortcode.
    $this->buildGrid($settings, $grid_nodes, $grid_items);

    // Adds the attachments.
    $attach = BlazyFilterUtil::attach($settings);
    $attachments = $this->blazyManager->attach($attach);

    // Cleans up invalid, or moved nodes.
    $this->cleanupNodes($dom);

    // Attach Blazy component libraries.
    $result->setProcessedText(Html::serialize($dom))
      ->addAttachments($attachments);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return file_get_contents(dirname(__FILE__) . "/FILTER_TIPS.txt");
    }
    else {
      return $this->t('<b>Blazy</b>: <ul><li>With HTML: <code>[blazy]..[item]IMG[/item]..[/blazy]</code></li><li>With entity, self-closed: <code>[blazy data="node:44:field_media" /]</code></li><li>Grid format:
      <code>STYLE:SMALL-MEDIUM-LARGE</code>, where <code>STYLE</code> is one of <code>column grid
      flexbox nativegrid nativegrid.masonry</code>.<br>
      <code>[blazy grid="column:2-3-4" data="node:44:field_media" /]</code><br>
      <code>[blazy grid="nativegrid:2-3-4"]...[/blazy]</code><br>
      <code>[blazy grid="nativegrid:2-3-4x4 4x3 2x2 2x4 2x2 2x3 2x3 4x2 4x2"]...[/blazy]
      </code><br>Only nativegrid can have number or dimension string (4x4...). The rest number only.</li><li>To disable, add <code>data-unblazy</code>, e.g.: <code>&lt;img data-unblazy</code> or <code>&lt;iframe data-unblazy</code>. Add width and height for SVG, and non-uploaded images without image styles.</li></ul>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // @todo add more sensible form items.
    $form['filter_tags'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable HTML tags'),
      '#options' => [
        'img' => $this->t('Image'),
        'iframe' => $this->t('Video iframe'),
      ],
      '#default_value' => empty($this->settings['filter_tags']) ? [] : array_values((array) $this->settings['filter_tags']),
      '#description' => $this->t('To disable Blazy per individual item, add attribute <code>data-unblazy</code>.'),
      '#prefix' => '<p>' . $this->t('<b>Warning!</b> Blazy Filter is useless and broken when you enable <b>Media embed</b> or <b>Display embedded entities</b>. You can disable Blazy Filter in favor of Blazy formatter embedded inside <b>Media embed</b> or <b>Display embedded entities</b> instead. However it might be useful for User Generated Contents (UGC) where Entity/Media Embed are likely more for privileged users, authors, editors, admins, alike. Or when Entity/Media Embed is disabled. Or when editors prefer pasting embed codes from video providers rather than creating media entities.') . '</p>',
    ];

    $this->mediaSwitchForm($form);

    $form['use_data_uri'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Trust data URI'),
      '#default_value' => isset($this->settings['use_data_uri']) ? $this->settings['use_data_uri'] : FALSE,
      '#description' => $this->t('Enable to support the use of data URI. Leave it unchecked if unsure, or never use data URI.'),
      '#suffix' => '<p>' . $this->t('Recommended placement after Align / Caption images. Not tested against, nor dependent on, Shortcode module. Be sure to place Blazy filter before any other Shortcode if installed.') . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettings($text) {
    $settings = parent::buildSettings($text);

    // The data-grid and data-column are deprecated for [blazy] shortcode.
    $settings['grid'] = stristr($text, 'data-grid') !== FALSE;
    $settings['column'] = stristr($text, 'data-column') !== FALSE;
    $settings['_grid'] = $settings['column'] || $settings['grid'];

    // Provides alter like formatters to modify at one go, even clumsy here.
    $build = ['settings' => $settings];
    $this->blazyManager->getModuleHandler()->alter('blazy_settings', $build, $this->settings);
    return array_merge($settings, $build['settings']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildImageItem(array &$build, &$node) {
    parent::buildImageItem($build, $node);

    $item = $build['item'];
    $settings = $build['settings'];

    if (!empty($settings['_grid']) || !empty($settings['no_item_container'])) {
      return;
    }

    // Responsive image with aspect ratio requires an extra container to work
    // with Align/ Caption images filters.
    $build['media_attributes']['class'] = [
      'media-wrapper',
      'media-wrapper--blazy',
    ];

    // Copy all attributes of the original node to the item_attributes.
    if ($node->attributes->length) {
      foreach ($node->attributes as $attribute) {
        $value = $attribute->nodeValue;
        $name = $attribute->nodeName;
        if ($name == 'src') {
          continue;
        }

        // Move classes (align-BLAH,etc) to Blazy container, not image so to
        // work with alignments and aspect ratio. Sanitization is performed at
        // BlazyManager::prepareBlazy() to avoid double escapes.
        if ($name == 'class') {
          if (mb_strpos($value, 'b-lazy') === FALSE) {
            $build['media_attributes']['class'][] = $value;
          }
        }
        // Uploaded IMG has target_id in the least, respect hard-coded IMG.
        // @todo decide to remove as this is being too risky.
        elseif ($item && !isset($item->target_id)) {
          $build['item_attributes'][$name] = $value;
        }
      }

      $build['media_attributes']['class'] = array_unique($build['media_attributes']['class']);
    }

    if (!empty($settings['type'])) {
      $build['media_attributes']['class'][] = 'media-wrapper--' . $settings['type'];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo deprecate and remove for shortcodes at Blazy 3.x.
   */
  protected function cleanupImageCaption(array &$build, &$node, &$item) {
    $settings = &$build['settings'];
    if (empty($settings['_blazy_tag'])) {
      // Mark the FIGCAPTION for deletion because the caption moved into Blazy.
      $item->setAttribute('class', 'blazy-removed');

      // Marks figures for removal as its contents are moved into grids.
      if ($settings['_grid']) {
        $node->parentNode->setAttribute('class', 'blazy-removed');
      }
    }
  }

  /**
   * Build the blazy, the node might be grid, or direct img/ iframe.
   */
  private function build(\DOMElement $node, array &$settings) {
    if ($node->tagName == 'blazy') {
      $attribute = $node->getAttribute('data');

      $settings['id'] = $settings['gallery_id'] = BlazyFilterUtil::getId($settings['plugin_id']);
      $settings['_blazy_tag'] = TRUE;
      $this->prepareSettings($node, $settings);

      if (!empty($attribute) && mb_strpos($attribute, ":") !== FALSE) {
        return $this->byEntity($node, $settings, $attribute);
      }

      return $this->byDom($node, $settings);
    }

    $build = ['settings' => $settings];
    return $this->buildItem($build, $node);
  }

  /**
   * Build the blazy using the node ID and field_name.
   */
  private function byEntity(\DOMElement $object, array $settings, $attribute) {
    list($entity_type, $id, $field_name, $field_image) = array_pad(array_map('trim', explode(":", $attribute, 4)), 4, NULL);
    if (empty($field_name)) {
      return [];
    }

    $entity = $this->blazyManager->entityLoad($id, $entity_type);
    $settings['entity_type_id'] = $entity_type;
    $settings['entity_id'] = $id;
    $settings['field_name'] = $field_name;
    $settings['image'] = $field_image;

    if ($entity && $entity->hasField($field_name)) {
      $settings['bundle'] = $entity->bundle();
      $list = $entity->get($field_name);

      if ($list) {
        $definition = $list->getFieldDefinition();
        $field_type = $settings['field_type'] = $definition->get('field_type');
        $field_settings = $definition->get('settings');
        $handler = isset($field_settings['handler']) ? $field_settings['handler'] : NULL;
        $strings = ['link', 'string', 'string_long'];
        $texts = ['text', 'text_long', 'text_with_summary'];

        $formatter = NULL;
        // @todo refine for main stage, etc.
        if ($field_type == 'entity_reference' || $field_type == 'entity_reference_revisions') {
          if ($handler == 'default:media') {
            $formatter = 'blazy_media';
          }
        }
        elseif ($field_type == 'image') {
          $formatter = 'blazy_image';
        }
        elseif (in_array($field_type, $strings)) {
          $formatter = 'blazy_oembed';
        }
        elseif (in_array($field_type, $texts)) {
          $formatter = 'blazy_text';
        }

        if ($formatter) {
          return $list->view([
            'type' => $formatter,
            'settings' => $settings,
          ]);
        }
      }
    }

    return [];
  }

  /**
   * Build the blazy using the DOM lookups.
   */
  private function byDom(\DOMElement $object, array &$settings) {
    $text = BlazyFilterUtil::getHtml($object);
    if (empty($text)) {
      return [];
    }

    $dom = Html::load($text);
    $nodes = BlazyFilterUtil::getNodes($dom, '//item');
    if ($nodes->length == 0) {
      return [];
    }

    $build = ['settings' => $settings];

    foreach ($nodes as $delta => $node) {
      if (!($node instanceof \DOMElement)) {
        continue;
      }

      $sets = $build['settings'];
      $sets['delta'] = $delta;
      $sets['thumbnail_uri'] = $node->getAttribute('data-thumb');

      $element = ['attributes' => [], 'item' => NULL, 'settings' => $sets];
      $content = $this->buildItem($element, $node) ?: ['#markup' => $dom->saveHtml($node)];

      $element['content'] = $content;
      unset($element['captions']);

      $build[$delta] = $element;
    }

    return $this->blazyManager->build($build);
  }

  /**
   * Build the individual item.
   */
  private function buildItem(array &$build, $node) {
    $media = NULL;

    // If using grid, node is grid item, else img or iframe.
    if ($node->tagName == 'item') {
      $this->buildItemAttributes($build, $node);
      $text = BlazyFilterUtil::getHtml($node);

      if (!empty($text)) {
        $dom = Html::load($text);
        $items = BlazyFilterUtil::getNodes($dom, '//iframe | //img');
        if ($items->length > 0) {
          $media = $items->item(0);
        }
      }
    }
    else {
      $media = $node;
    }

    if ($media == NULL) {
      return [];
    }

    // Provides individual item settings.
    $this->buildItemSettings($build, $media);

    // Extracts image item from SRC attribute.
    $this->buildImageItem($build, $media);

    // Extracts image caption if available.
    $this->buildImageCaption($build, $media);

    // Marks invalid, unknown, missing IMG or IFRAME for removal.
    // Be sure to not affect external images, only strip missing local URI.
    $uri = $build['settings']['uri'];
    $missing = !empty($uri) && (BlazyUtil::isValidUri($uri) && !is_file($uri));
    if (empty($uri) || $missing) {
      $media->setAttribute('class', 'blazy-removed');
      return [];
    }

    return $this->blazyManager->getBlazy($build);
  }

  /**
   * Cleanups invalid nodes or those of which their contents are moved.
   *
   * @param \DOMDocument $dom
   *   The HTML DOM object being modified.
   */
  private function cleanupNodes(\DOMDocument $dom) {
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query("//*[contains(@class, 'blazy-removed')]");
    if ($nodes->length > 0) {
      BlazyFilterUtil::removeNodes($nodes);
    }
  }

  /**
   * Build the grid.
   *
   * @param array $settings
   *   The settings array.
   * @param array $grid_nodes
   *   The grid nodes.
   * @param array $grid_items
   *   The renderable array of blazy item.
   *
   * @todo deprecate and remove for shortcodes at Blazy 3.x.
   */
  private function buildGrid(array &$settings, array $grid_nodes, array $grid_items = []) {
    if (empty($settings['_grid']) || empty($grid_items[0])) {
      return;
    }

    $settings['_uri'] = isset($grid_items[0]['#build'], $grid_items[0]['#build']['settings']['uri']) ? $grid_items[0]['#build']['settings']['uri'] : '';

    $first = $grid_nodes[0];
    $dom = $first->ownerDocument;
    $xpath = new \DOMXPath($dom);
    $query = $settings['style'] = $settings['column'] ? 'column' : 'grid';
    $grid = FALSE;

    // This is weird, variables not working for xpath?
    $node = $query == 'column' ? $xpath->query('//*[@data-column]') : $xpath->query('//*[@data-grid]');
    if ($node->length > 0 && $node->item(0) && $node->item(0)->hasAttribute('data-' . $query)) {
      $grid = $node->item(0)->getAttribute('data-' . $query);
    }

    if ($grid) {
      $grids = array_map('trim', explode(' ', $grid));

      foreach (['small', 'medium', 'large'] as $key => $item) {
        if (isset($grids[$key])) {
          $settings['grid_' . $item] = $grids[$key];
          $settings['grid'] = $grids[$key];
        }
      }

      $build = [
        'items' => $grid_items,
        'settings' => $settings,
      ];

      $output = $this->blazyManager->build($build);
      $altered_html = $this->blazyManager->getRenderer()->render($output);

      // Checks if the IMG is managed by caption filter identified by figure.
      if ($first->parentNode && $first->parentNode->tagName == 'figure') {
        $first = $first->parentNode;
      }

      // Create the parent grid container, and put it before the first.
      // This extra container ensures hook_blazy_build_alter() aint screw up.
      $parent = $first->parentNode ? $first->parentNode : $first;

      $container = $parent->insertBefore($dom->createElement('div'), $first);
      $container->setAttribute('class', 'blazy-wrapper blazy-wrapper--filter');

      $updated_nodes = Html::load($altered_html)->getElementsByTagName('body')
        ->item(0)
        ->childNodes;

      foreach ($updated_nodes as $updated_node) {
        // Import the updated from the new DOMDocument into the original
        // one, importing also the child nodes of the updated node.
        $updated_node = $dom->importNode($updated_node, TRUE);
        $container->appendChild($updated_node);
      }

      // Cleanups old nodes already moved into grids.
      BlazyFilterUtil::removeNodes($grid_nodes);
    }
  }

}
