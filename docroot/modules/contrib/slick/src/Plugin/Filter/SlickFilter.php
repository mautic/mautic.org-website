<?php

namespace Drupal\slick\Plugin\Filter;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyUtil;
use Drupal\blazy\Plugin\Filter\BlazyFilter;
use Drupal\slick\SlickDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter for a Slick.
 *
 * Best after Blazy, Align images, caption images.
 *
 * @Filter(
 *   id = "slick_filter",
 *   title = @Translation("Slick"),
 *   description = @Translation("Creates slideshow/ carousel with Slick shortcode."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "optionset" = "default",
 *     "media_switch" = "",
 *   },
 *   weight = 4
 * )
 *
 * @todo replace methods by Drupal\blazy\Plugin\Filter\BlazyFilterUtil post 2.5.
 * @todo use Drupal\blazy\Plugin\Filter\BlazyFilterBase instead post 2.5.
 */
class SlickFilter extends BlazyFilter {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->admin = $container->get('slick.admin');
    $instance->manager = $container->get('slick.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'settings' => array_merge($this->pluginDefinition['settings'], SlickDefault::filterSettings()),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $this->result = $result = new FilterProcessResult($text);
    $this->langcode = $langcode;

    if (empty($text) || stristr($text, '[slick') === FALSE) {
      return $result;
    }

    $attachments = [];
    $settings = $this->buildSettings($text);
    $text = self::unwrap($text, 'slick', 'slide');
    $dom = Html::load($text);
    $nodes = self::getValidNodes($dom, ['slick']);

    if (count($nodes) > 0) {
      foreach ($nodes as $node) {
        if ($output = $this->build($node, $settings)) {
          $this->renderNode($node, $output);
        }
      }

      $attach = self::attach($settings);
      $attachments = $this->manager->attach($attach);
    }

    // Attach Blazy component libraries.
    $result->setProcessedText(Html::serialize($dom))
      ->addAttachments($attachments);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettings($text) {
    $this->settings['no_item_container'] = TRUE;
    $settings = parent::buildSettings($text);

    // @todo remove post Blazy 2.5+.
    $settings['plugin_id'] = $this->getPluginId();
    $settings['item_id'] = 'slide';
    $settings['namespace'] = 'slick';
    $settings['visible_items'] = 0;

    // Provides alter like formatters to modify at one go, even clumsy here.
    $build = ['settings' => $settings];
    $this->manager->getModuleHandler()->alter('slick_settings', $build, $this->settings);
    return array_merge($settings, $build['settings']);
  }

  /**
   * Build the slick.
   */
  private function build($object, array $settings) {
    $attribute = $object->getAttribute('data');

    $settings['id'] = $settings['gallery_id'] = Blazy::getHtmlId(str_replace('_', '-', $settings['plugin_id']));

    if (!empty($attribute) && mb_strpos($attribute, ":") !== FALSE) {
      return $this->byEntity($object, $settings, $attribute);
    }

    return $this->byDom($object, $settings);
  }

  /**
   * Build the slick using the node ID and field_name.
   */
  private function byEntity(\DOMElement $object, array $settings, $attribute) {
    list($entity_type, $id, $field_name, $field_image) = array_pad(array_map('trim', explode(":", $attribute, 4)), 4, NULL);
    if (empty($field_name)) {
      return [];
    }

    $entity = $this->manager->entityLoad($id, $entity_type);
    $settings['entity_type_id'] = $entity_type;
    $settings['entity_id'] = $id;
    $settings['field_name'] = $field_name;
    $settings['image'] = $field_image;

    if ($entity && $entity->hasField($field_name)) {
      $list = $entity->get($field_name);
      $settings['bundle'] = $entity->bundle();
      $settings['count'] = count($list);
      $build = ['settings' => $settings];

      $this->prepareBuild($build, $object);
      $settings = $build['settings'];

      if ($list) {
        $definition = $list->getFieldDefinition();
        $field_type = $settings['field_type'] = $definition->get('field_type');
        $field_settings = $definition->get('settings');
        $handler = isset($field_settings['handler']) ? $field_settings['handler'] : NULL;
        $texts = ['text', 'text_long', 'text_with_summary'];

        $formatter = NULL;
        // @todo refine for main stage, etc.
        if ($field_type == 'entity_reference' || $field_type == 'entity_reference_revisions') {
          if ($handler == 'default:media') {
            $formatter = 'slick_media';
          }
          else {
            // @todo refine for Paragraphs, etc.
            if ($field_type == 'entity_reference_revisions') {
              $formatter = 'slick_paragraphs_media';
            }
            else {
              $settings['vanilla'] = TRUE;
              $exists = $this->manager->getModuleHandler()->moduleExists('slick_entityreference');
              if ($exists) {
                $formatter = 'slick_entityreference';
              }
            }
          }
        }
        elseif ($field_type == 'image') {
          $formatter = 'slick_image';
        }
        elseif (in_array($field_type, $texts)) {
          $formatter = 'slick_text';
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
   * Build the slick using the DOM lookups.
   */
  private function byDom(\DOMElement $object, array $settings) {
    $text = self::getHtml($object);
    if (empty($text)) {
      return [];
    }

    $dom = Html::load($text);
    $nodes = self::getNodes($dom);
    if ($nodes->length == 0) {
      return [];
    }

    $settings['count'] = $nodes->length;
    $build = ['settings' => $settings];
    $this->prepareBuild($build, $object);

    foreach ($nodes as $delta => $node) {
      if (!($node instanceof \DOMElement)) {
        continue;
      }

      $sets = $build['settings'];
      $sets['delta'] = $delta;
      $sets['thumbnail_uri'] = $node->getAttribute('data-thumb');
      $element = ['caption' => [], 'item' => NULL, 'settings' => $sets];

      $this->buildItem($element, $node);

      if (empty($element['slide'])) {
        $element['slide'] = ['#markup' => $dom->saveHtml($node)];
      }

      $build['items'][$delta] = $element;

      // Build individual slick thumbnail.
      if (!empty($sets['nav'])) {
        $this->buildNav($build, $element);
      }
    }

    return $this->manager->build($build);
  }

  /**
   * Build the slide item.
   */
  private function buildItem(array &$build, $node) {
    $text = self::getHtml($node);
    if (empty($text)) {
      return;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $children = $xpath->query("//iframe | //img");

    $this->buildNodeItemAttributes($build, $node);

    if ($children->length > 0) {
      // Can only have the first found for the main slide stage.
      $child = $children->item(0);

      // Provides individual item settings.
      $this->buildItemSettings($build, $child);

      // Extracts image item from SRC attribute.
      $this->buildImageItem($build, $child);

      // Extracts image caption if available.
      $this->buildImageCaption($build, $child);

      if (!empty($build['settings']['uri'])) {
        $build['slide'] = $this->blazyManager->getBlazy($build);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildImageCaption(array &$build, &$node) {
    $item = parent::buildImageCaption($build, $node);

    if (!empty($build['captions'])) {
      $build['caption'] = $build['captions'];
      unset($build['captions']);
    }
    return $item;
  }

  /**
   * Prepares the slick.
   */
  private function prepareBuild(array &$build, $node) {
    $settings = &$build['settings'];
    $options = [];
    if ($check = $node->getAttribute('options')) {
      $check = str_replace("'", '"', $check);
      if ($check) {
        $options = Json::decode($check);
      }
    }

    // @todo remove check post Blazy 2.5+.
    if (method_exists(get_parent_class($this), 'prepareSettings')) {
      parent::prepareSettings($node, $settings);
    }
    else {
      if ($check = $node->getAttribute('settings')) {
        $check = str_replace("'", '"', $check);
        $check = Json::decode($check);
        if ($check) {
          $settings = array_merge($settings, $check);
        }
      }

      self::toGrid($node, $settings);
    }

    if (!isset($settings['nav'])) {
      $settings['nav'] = (!empty($settings['optionset_thumbnail']) && $settings['count'] > 1);
    }

    $settings['_grid'] = !empty($settings['style']) && !empty($settings['grid']);
    $settings['visible_items'] = $settings['_grid'] && empty($settings['visible_items']) ? 6 : $settings['visible_items'];

    $build['options'] = $options;
  }

  /**
   * Build the slick navigation.
   */
  private function buildNav(array &$build, array $element) {
    $sets = $element['settings'];
    $item = $element['item'];
    $delta = $sets['delta'];
    $caption = empty($sets['thumbnail_caption']) ? NULL : $sets['thumbnail_caption'];
    $text = (empty($item) || empty($item->{$caption})) ? [] : ['#markup' => Xss::filterAdmin($item->{$caption})];

    // Thumbnail usages: asNavFor pagers, dot, arrows, photobox thumbnails.
    $thumb = [
      'settings' => $sets,
      'slide' => $this->manager->getThumbnail($sets, $item),
      'caption' => $text,
    ];

    $build['thumb']['items'][$delta] = $thumb;
    unset($thumb);
  }

  /**
   * Returns the expected caption DOMelement.
   */
  protected function getCaptionElement($node) {
    $caption = NULL;
    // @todo remove check post Blazy 2.5+.
    if (method_exists(get_parent_class($this), 'getCaptionElement')) {
      $caption = parent::getCaptionElement($node);
    }

    // @todo figure out better traversal with DOM.
    if (empty($caption) && $node->parentNode) {
      $parent = $node->parentNode->parentNode;
      if ($parent && $grandpa = $parent->parentNode) {
        if ($grandpa->parentNode) {
          $divs = $grandpa->parentNode->getElementsByTagName('div');
        }
        else {
          $divs = $grandpa->getElementsByTagName('div');
        }

        if ($divs) {
          foreach ($divs as $div) {
            $class = $div->getAttribute('class');
            if ($class == 'blazy__caption') {
              $caption = $div;
              break;
            }
          }
        }
      }
    }
    return $caption;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return file_get_contents(dirname(__FILE__) . "/FILTER_TIPS.txt");
    }
    else {
      return $this->t('<b>Slick</b>: Create a slideshow/ carousel: <br><ul><li><b>With self-closing using data entity, <code>data=ENTITY_TYPE:ID:FIELD_NAME:FIELD_IMAGE</code></b>:<br><code>[slick data="node:44:field_media" /]</code>. <code>FIELD_IMAGE</code> is optional.</li><li><b>With any HTML</b>: <br><code>[slick settings="{}" options="{}"]...[slide]...[/slide]...[/slick]</li></code></ul>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $definition = [
      'settings' => $this->settings,
      'background' => TRUE,
      'caches' => FALSE,
      'image_style_form' => TRUE,
      'media_switch_form' => TRUE,
      'multimedia' => TRUE,
      'thumb_captions' => 'default',
      'thumb_positions' => TRUE,
      'nav' => TRUE,
    ];

    $element = [];
    $this->admin->buildSettingsForm($element, $definition);

    if (isset($element['media_switch'])) {
      unset($element['media_switch']['#options']['content']);
    }

    if (isset($element['closing'])) {
      $element['closing']['#suffix'] = $this->t('Best after Blazy, Align / Caption images filters -- all are not required to function. Not tested against, nor dependent on, Shortcode module. Be sure to place Slick filter before any other Shortcode if installed.');
    }

    return $element;
  }

  /**
   * Unwrap the enclosing tags.
   *
   * @todo remove/ replace all methods below by BlazyFilterUtil post Blazy 2.5+.
   */
  private static function unwrap($string, $container = 'slick', $item = 'slide') {
    // Might not be available with self-closing [TAG data="BLAH" /].
    if (mb_strpos($string, "[$item") !== FALSE) {
      $string = self::unwrapItem($string, $item);
    }

    return self::unwrapItem($string, $container);
  }

  /**
   * Unwrap the enclosing tags.
   */
  private static function unwrapItem($string, $item) {
    $patterns = [
      // Not supported, but for completion [TAG data="BLAH"]A.B.C[/TAG].
      "~(<p\>)\[$item?(.*?)\](.*?)\[/$item\](<\/p>)~",
      // Normal WYSIWYG editor outputs with HTML correction filter enabled:
      // <p>[TAG data="BLAH" /]</p>.
      // <p>[TAG settings="BLAH"]</p>.
      // <p>[/TAG]</p>.
      "~(<p\>)\[(/)?$item(.*?)\](<\/p>)~",
      // Abnormal non-WYSIWYG editor outputs: <p>[/TAG]<br />.
      "~(<p\>)\[(/)?$item(.*?)\](<br \/>)~",
      // Abnormal non-WYSIWYG editor outputs, letfovers: [TAG]</p>.
      "~\[(/)?$item(.*?)\](<\/p>)~",
    ];

    $replacements = [
      "<$item$2>$3</$item>",
      "<$2$item$3>",
      "<$2$item$3>",
      "<$1$item$2>",
    ];

    return preg_replace($patterns, $replacements, $string);
  }

  /**
   * Returns the inner HTMLof the DOMElement node.
   *
   * See http://www.php.net/manual/en/class.domelement.php#101243
   */
  private static function getHtml($node) {
    $text = '';
    foreach ($node->childNodes as $child) {
      if ($child instanceof \DOMElement) {
        $text .= $child->ownerDocument->saveXML($child);
      }
    }
    return $text;
  }

  /**
   * Returns settings for attachments.
   */
  private static function attach(array $settings = []) {
    $all = ['blazy' => TRUE, 'filter' => TRUE, 'ratio' => TRUE];
    $all['media_switch'] = $switch = $settings['media_switch'];

    if (!empty($settings[$switch])) {
      $all[$switch] = $settings[$switch];
    }

    return $all;
  }

  /**
   * Returns valid nodes based on the allowed tags.
   */
  private static function getValidNodes(\DOMDocument $dom, array $allowed_tags = [], $exclude = '') {
    $valid_nodes = [];
    foreach ($allowed_tags as $allowed_tag) {
      $nodes = $dom->getElementsByTagName($allowed_tag);
      if ($nodes->length > 0) {
        foreach ($nodes as $node) {
          if ($exclude && $node->hasAttribute($exclude)) {
            continue;
          }

          $valid_nodes[] = $node;
        }
      }
    }
    return $valid_nodes;
  }

  /**
   * Returns attributes extracted from a DOMElement if any.
   */
  private static function getAttribute($node, array $excludes = []) {
    $attributes = [];
    if ($node && $node->attributes->length) {
      foreach ($node->attributes as $attribute) {
        $name = $attribute->nodeName;
        $value = $attribute->nodeValue;
        if ($excludes && in_array($name, $excludes)) {
          continue;
        }
        $attributes[$name] = ($name == 'class') ? [$value] : $value;
      }
    }
    return $attributes ? BlazyUtil::sanitize($attributes) : [];
  }

  /**
   * Returns DOMElement nodes expected to be slide items.
   */
  private static function getNodes($dom, $tag = '//slide') {
    $xpath = new \DOMXPath($dom);

    return $xpath->query($tag);
  }

  /**
   * Extract grids from the node attribute.
   */
  private static function toGrid(\DOMElement $node, array &$settings) {
    if ($check = $node->getAttribute('grid')) {
      list($settings['style'], $grid, $settings['visible_items']) = array_pad(array_map('trim', explode(":", $check, 3)), 3, NULL);

      if ($grid) {
        list(
          $settings['grid_small'],
          $settings['grid_medium'],
          $settings['grid']
        ) = array_pad(array_map('trim', explode("-", $grid, 3)), 3, NULL);
      }
    }
  }

  /**
   * Render the output.
   *
   * @todo remove for parent::render() method post Blazy 2.5+/
   */
  private function renderNode(\DOMElement $node, array $output) {
    $dom = $node->ownerDocument;
    $altered_html = $this->blazyManager->getRenderer()->render($output);

    // Load the altered HTML into a new DOMDocument, retrieve element.
    $updated_nodes = Html::load($altered_html)->getElementsByTagName('body')
      ->item(0)
      ->childNodes;

    foreach ($updated_nodes as $updated_node) {
      // Import the updated from the new DOMDocument into the original
      // one, importing also the child nodes of the updated node.
      $updated_node = $dom->importNode($updated_node, TRUE);
      $node->parentNode->insertBefore($updated_node, $node);
    }

    // Finally, remove the original blazy node.
    if ($node->parentNode) {
      $node->parentNode->removeChild($node);
    }
  }

  /**
   * Build the slide item attributes.
   *
   * @todo remove for parent::buildItemAttributes() method post Blazy 2.5+.
   */
  private function buildNodeItemAttributes(array &$build, $node) {
    if ($caption = $node->getAttribute('caption')) {
      if (method_exists(get_parent_class($this), 'filterHtml')) {
        $safe_caption = parent::filterHtml($caption);
        $build['captions']['alt'] = ['#markup' => $safe_caption];
      }
      $node->removeAttribute('caption');
    }

    if ($attributes = self::getAttribute($node)) {
      // Move it to .slide__content for better displays like .well/ .card.
      if (!empty($attributes['class'])) {
        $build['content_attributes']['class'] = $attributes['class'];
        unset($attributes['class']);
      }
      $build['attributes'] = $attributes;
    }
  }

}
