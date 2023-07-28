<?php

namespace Drupal\quicktabs;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Quick Tabs plugin manager.
 */
class TabTypeManager extends DefaultPluginManager {

  /**
   * Construct a TabTypeManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *    keyed by the corresponding namespace to look for plugin implementations.
   * @param |Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the later hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/TabType', $namespaces, $module_handler, 'Drupal\quicktabs\TabTypeInterface', 'Drupal\quicktabs\Annotation\TabType');

    $this->alterInfo('quicktabs_tab_type_info');
    $this->setCacheBackend($cache_backend, 'quicktabs_tab_types');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}
