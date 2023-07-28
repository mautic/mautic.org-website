<?php

namespace Drupal\quicktabs;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Quick Tabs renderer plugin manager.
 */
class TabRendererManager extends DefaultPluginManager {

  /**
   * Construct a TabRendererManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *    keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the later hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/TabRenderer', $namespaces, $module_handler, 'Drupal\quicktabs\TabRendererInterface', 'Drupal\quicktabs\Annotation\TabRenderer');

    $this->alterInfo('quicktabs_tab_renderer_info');
    $this->setCacheBackend($cache_backend, 'quicktabs_tab_renderers');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}
