<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements BlazyManagerInterface.
 */
abstract class BlazyManagerBase implements BlazyManagerInterface {

  // Fixed for EB AJAX issue: #2893029.
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The app root.
   *
   * @var \SplString
   */
  protected $root;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a BlazyManager object.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, RendererInterface $renderer, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache) {
    $this->entityRepository  = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler     = $module_handler;
    $this->renderer          = $renderer;
    $this->configFactory     = $config_factory;
    $this->cache             = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('config.factory'),
      $container->get('cache.default')
    );

    // @todo remove and use DI at 2.x+ post sub-classes updates.
    $instance->setRoot($container->get('app.root'));
    $instance->setLanguageManager($container->get('language_manager'));
    return $instance;
  }

  /**
   * Returns the app root.
   */
  public function root() {
    return $this->root;
  }

  /**
   * Sets app root service.
   *
   * @todo remove and use DI at 3.x+ post sub-classes updates.
   */
  public function setRoot($root) {
    $this->root = $root;
    return $this;
  }

  /**
   * Returns the language manager service.
   */
  public function languageManager() {
    return $this->languageManager;
  }

  /**
   * Sets the language manager service.
   *
   * @todo remove and use DI at 3.x+ post sub-classes updates.
   */
  public function setLanguageManager($language_manager) {
    $this->languageManager = $language_manager;
    return $this;
  }

  /**
   * Returns the entity repository service.
   */
  public function getEntityRepository() {
    return $this->entityRepository;
  }

  /**
   * Returns the entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Returns the module handler.
   */
  public function getModuleHandler() {
    return $this->moduleHandler;
  }

  /**
   * Returns the renderer.
   */
  public function getRenderer() {
    return $this->renderer;
  }

  /**
   * Returns the config factory.
   */
  public function getConfigFactory() {
    return $this->configFactory;
  }

  /**
   * Returns the cache.
   */
  public function getCache() {
    return $this->cache;
  }

  /**
   * Returns any config, or keyed by the $setting_name.
   */
  public function configLoad($setting_name = '', $settings = 'blazy.settings') {
    $config  = $this->configFactory->get($settings);
    $configs = $config->get();
    unset($configs['_core']);
    return empty($setting_name) ? $configs : $config->get($setting_name);
  }

  /**
   * Returns a shortcut for loading a config entity: image_style, slick, etc.
   */
  public function entityLoad($id, $entity_type = 'image_style') {
    return $this->entityTypeManager->getStorage($entity_type)->load($id);
  }

  /**
   * Returns a shortcut for loading multiple configuration entities.
   */
  public function entityLoadMultiple($entity_type = 'image_style', $ids = NULL) {
    return $this->entityTypeManager->getStorage($entity_type)->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array $attach = []) {
    $load   = [];
    $switch = empty($attach['media_switch']) ? '' : $attach['media_switch'];

    if ($switch && $switch != 'content') {
      $attach[$switch] = $switch;

      if (in_array($switch, $this->getLightboxes())) {
        $load['library'][] = 'blazy/lightbox';

        if (!empty($attach['colorbox'])) {
          BlazyAlter::attachColorbox($load, $attach);
        }
      }
    }

    // Allow variants of grid, columns, flexbox, native grid to co-exist.
    if (!empty($attach['style'])) {
      $attach[$attach['style']] = $attach['style'];
    }

    if (!empty($attach['fx']) && $attach['fx'] == 'blur') {
      $load['library'][] = 'blazy/fx.blur';
    }

    foreach (BlazyDefault::components() as $component) {
      if (!empty($attach[$component])) {
        $load['library'][] = 'blazy/' . $component;
      }
    }

    // Allows Blazy libraries to be disabled by a special flag _unblazy.
    if (empty($attach['_unblazy'])) {
      $load['library'][] = 'blazy/load';
      $load['drupalSettings']['blazy'] = $this->configLoad('blazy');
      $load['drupalSettings']['blazyIo'] = $this->getIoSettings($attach);
    }

    // Adds AJAX helper to revalidate Blazy/ IO, if using VIS, or alike.
    if (!empty($attach['use_ajax'])) {
      $load['library'][] = 'blazy/bio.ajax';
    }

    $this->moduleHandler->alter('blazy_attach', $load, $attach);
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function getIoSettings(array $attach = []) {
    $io = [];
    $thold = trim($this->configLoad('io.threshold')) ?: '0';
    $number = strpos($thold, '.') !== FALSE ? (float) $thold : (int) $thold;
    $thold = strpos($thold, ',') !== FALSE ? array_map('trim', explode(',', $thold)) : [$number];

    // Respects hook_blazy_attach_alter() for more fine-grained control.
    foreach (['enabled', 'disconnect', 'rootMargin', 'threshold'] as $key) {
      $default = $key == 'rootMargin' ? '0px' : FALSE;
      $value = $key == 'threshold' ? $thold : $this->configLoad('io.' . $key);
      $io[$key] = isset($attach['io.' . $key]) ? $attach['io.' . $key] : ($value ?: $default);
    }

    return (object) $io;
  }

  /**
   * Returns the common UI settings inherited down to each item.
   *
   * The `fx` sequence: hook_alter > formatters (not implemented yet) > UI.
   * The `_fx` is a special flag such as to temporarily disable till needed.
   */
  public function getCommonSettings(array &$settings) {
    $config = array_intersect_key($this->configLoad(), BlazyDefault::uiSettings());
    $config['fx'] = isset($config['fx']) ? $config['fx'] : '';
    $config['fx'] = empty($settings['fx']) ? $config['fx'] : $settings['fx'];
    $settings = array_merge($settings, $config);
    $settings['fx'] = isset($settings['_fx']) ? $settings['_fx'] : $settings['fx'];
    $settings['media_switch'] = $switch = empty($settings['media_switch']) ? '' : $settings['media_switch'];
    $settings['iframe_domain'] = $this->configLoad('iframe_domain', 'media.settings');
    $settings['is_preview'] = Blazy::isPreview();
    $settings['lightbox'] = ($switch && in_array($switch, $this->getLightboxes())) ? $switch : FALSE;
    $settings['namespace'] = empty($settings['namespace']) ? 'blazy' : $settings['namespace'];
    $settings['route_name'] = Blazy::routeMatch() ? Blazy::routeMatch()->getRouteName() : '';
    $settings['_resimage'] = $this->moduleHandler->moduleExists('responsive_image');
    $settings['resimage'] = $settings['_resimage'] && !empty($settings['responsive_image_style']);
    $settings['resimage'] = $settings['resimage'] ? $this->entityLoad($settings['responsive_image_style'], 'responsive_image_style') : FALSE;
    $settings['current_language'] = $this->languageManager->getCurrentLanguage()->getId();

    if ($switch) {
      // Allows lightboxes to provide its own optionsets, e.g.: ElevateZoomPlus.
      $settings[$switch] = empty($settings[$switch]) ? $switch : $settings[$switch];
    }

    // Formatters, Views style, not Filters.
    if (!empty($settings['style'])) {
      BlazyGrid::toNativeGrid($settings);
    }
  }

  /**
   * Returns the common settings extracted from the given entity.
   */
  public function getEntitySettings(array &$settings, $entity) {
    $internal_path = $absolute_path = NULL;

    // Deals with UndefinedLinkTemplateException such as paragraphs type.
    // @see #2596385, or fetch the host entity.
    if (!$entity->isNew()) {
      try {
        // Check if multilingual is enabled (@see #3214002).
        if ($entity->hasTranslation($settings['current_language'])) {
          // Load the translated url.
          $url = $entity->getTranslation($settings['current_language'])->toUrl();
        }
        else {
          // Otherwise keep the standard url.
          $url = $entity->toUrl();
        }

        $internal_path = $url->getInternalPath();
        $absolute_path = $url->setAbsolute()->toString();
      }
      catch (\Exception $ignore) {
        // Do nothing.
      }
    }

    // @todo Remove checks after another check, in case already set somewhere.
    $settings['current_view_mode'] = empty($settings['current_view_mode']) ? '_custom' : $settings['current_view_mode'];
    $settings['entity_id'] = empty($settings['entity_id']) ? $entity->id() : $settings['entity_id'];
    $settings['entity_type_id'] = empty($settings['entity_type_id']) ? $entity->getEntityTypeId() : $settings['entity_type_id'];
    $settings['bundle'] = empty($settings['bundle']) ? $entity->bundle() : $settings['bundle'];
    $settings['content_url'] = $settings['absolute_path'] = $absolute_path;
    $settings['internal_path'] = $internal_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getLightboxes() {
    $lightboxes = [];
    foreach (['colorbox', 'photobox'] as $lightbox) {
      if (function_exists($lightbox . '_theme')) {
        $lightboxes[] = $lightbox;
      }
    }

    if (is_file($this->root . '/libraries/photobox/photobox/jquery.photobox.js')) {
      $lightboxes[] = 'photobox';
    }

    $this->moduleHandler->alter('blazy_lightboxes', $lightboxes);
    return array_unique($lightboxes);
  }

  /**
   * {@inheritdoc}
   */
  public function getImageEffects() {
    $effects[] = 'blur';

    $this->moduleHandler->alter('blazy_image_effects', $effects);
    $effects = array_unique($effects);
    return array_combine($effects, $effects);
  }

  /**
   * {@inheritdoc}
   */
  public function isBlazy(array &$settings, array $item = []) {
    // Retrieves Blazy formatter related settings from within Views style.
    $item_id = isset($settings['item_id']) ? $settings['item_id'] : 'x';
    $content = isset($item[$item_id]) ? $item[$item_id] : $item;
    $image   = isset($item['item']) ? $item['item'] : NULL;

    // 1. Blazy formatter within Views fields by supported modules.
    $settings['_item'] = $image;
    if (isset($item['settings'])) {
      $this->isBlazyFormatter($settings, $item);
    }

    // 2. Blazy Views fields by supported modules.
    // Prevents edge case with unexpected flattened Views results which is
    // normally triggered by checking "Use field template" option.
    if (is_array($content) && isset($content['#view']) && ($view = $content['#view'])) {
      if ($blazy_field = BlazyViews::viewsField($view)) {
        $settings = array_merge(array_filter($blazy_field->mergedViewsSettings()), array_filter($settings));
      }
    }

    unset($settings['first_image']);
  }

  /**
   * Collects the first found Blazy formatter settings within Views fields.
   */
  protected function isBlazyFormatter(array &$settings, array $item = []) {
    $blazy = $item['settings'];

    // Merge the first found (Responsive) image data.
    if (!empty($blazy['blazy_data'])) {
      $settings['blazy_data'] = empty($settings['blazy_data']) ? $blazy['blazy_data'] : array_merge($settings['blazy_data'], $blazy['blazy_data']);
      $settings['_dimensions'] = !empty($settings['blazy_data']['dimensions']);
    }

    $cherries = BlazyDefault::cherrySettings() + ['uri' => ''];
    foreach ($cherries as $key => $value) {
      $fallback = isset($settings[$key]) ? $settings[$key] : $value;
      $settings[$key] = isset($blazy[$key]) && empty($fallback) ? $blazy[$key] : $fallback;
    }

    $settings['_uri'] = empty($settings['_uri']) ? $settings['uri'] : $settings['_uri'];
    unset($settings['uri']);
  }

  /**
   * Return the cache metadata common for all blazy-related modules.
   */
  public function getCacheMetadata(array $build = []) {
    $settings          = isset($build['settings']) ? $build['settings'] : $build;
    $namespace         = isset($settings['namespace']) ? $settings['namespace'] : 'blazy';
    $max_age           = $this->configLoad('cache.page.max_age', 'system.performance');
    $max_age           = empty($settings['cache']) ? $max_age : $settings['cache'];
    $id                = isset($settings['id']) ? $settings['id'] : Blazy::getHtmlId($namespace);
    $suffixes[]        = empty($settings['count']) ? count(array_filter($settings)) : $settings['count'];
    $cache['tags']     = Cache::buildTags($namespace . ':' . $id, $suffixes, '.');
    $cache['contexts'] = ['languages'];
    $cache['max-age']  = $max_age;
    $cache['keys']     = isset($settings['cache_metadata']['keys']) ? $settings['cache_metadata']['keys'] : [$id];

    if (!empty($settings['cache_tags'])) {
      $cache['tags'] = Cache::mergeTags($cache['tags'], $settings['cache_tags']);
    }

    return $cache;
  }

  /**
   * Provides attachments and cache common for all blazy-related modules.
   */
  protected function setAttachments(array &$element, array $settings, array $attachments = []) {
    $cache                = $this->getCacheMetadata($settings);
    $attached             = $this->attach($settings);
    $attachments          = empty($attachments) ? $attached : NestedArray::mergeDeep($attached, $attachments);
    $element['#attached'] = empty($element['#attached']) ? $attachments : NestedArray::mergeDeep($element['#attached'], $attachments);
    $element['#cache']    = empty($element['#cache']) ? $cache : NestedArray::mergeDeep($element['#cache'], $cache);
  }

  /**
   * Sets dimensions once to reduce method calls for Responsive image.
   */
  public function setResponsiveImageDimensions(array &$settings = [], $initial = TRUE) {
    $srcset = [];
    foreach ($this->getResponsiveImageStyles($settings['resimage'])['styles'] as $style) {
      $styled = array_merge($settings, BlazyUtil::transformDimensions($style, $settings, $initial));

      // In order to avoid layout reflow, we get dimensions beforehand.
      $srcset[$styled['width']] = round((($styled['height'] / $styled['width']) * 100), 2);
    }

    // Sort the srcset from small to large image width or multiplier.
    ksort($srcset);

    // Informs individual images that dimensions are already set once.
    $settings['blazy_data']['dimensions'] = $srcset;
    $settings['padding_bottom'] = end($srcset);
    $settings['_dimensions'] = TRUE;
  }

  /**
   * Returns the Responsive image styles and caches tags.
   *
   * @param object $responsive
   *   The responsive image style entity.
   *
   * @return array|mixed
   *   The responsive image styles and cache tags.
   */
  public function getResponsiveImageStyles($responsive) {
    $cache_tags = $responsive->getCacheTags();
    $image_styles = $this->entityLoadMultiple('image_style', $responsive->getImageStyleIds());

    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }
    return ['caches' => $cache_tags, 'styles' => $image_styles];
  }

  /**
   * Returns the thumbnail image using theme_image(), or theme_image_style().
   */
  public function getThumbnail(array $settings = [], $item = NULL) {
    if (!empty($settings['uri'])) {
      $external = UrlHelper::isExternal($settings['uri']);
      return [
        '#theme'      => $external ? 'image' : 'image_style',
        '#style_name' => empty($settings['thumbnail_style']) ? 'thumbnail' : $settings['thumbnail_style'],
        '#uri'        => $settings['uri'],
        '#item'       => $item,
        '#alt'        => $item && $item instanceof ImageItem ? $item->getValue()['alt'] : '',
      ];
    }
    return [];
  }

  /**
   * Provides alterable display styles.
   */
  public function getStyles() {
    $styles = [
      'column' => 'CSS3 Columns',
      'grid' => 'Grid Foundation',
      'flex' => 'Flexbox Masonry',
      'nativegrid' => 'Native Grid',
    ];
    $this->moduleHandler->alter('blazy_style', $styles);
    return $styles;
  }

  /**
   * Collects defined skins as registered via hook_MODULE_NAME_skins_info().
   *
   * @todo remove for sub-modules own skins as plugins at blazy:8.x-2.1+.
   * @see https://www.drupal.org/node/2233261
   * @see https://www.drupal.org/node/3105670
   */
  public function buildSkins($namespace, $skin_class, $methods = []) {
    return [];
  }

}
