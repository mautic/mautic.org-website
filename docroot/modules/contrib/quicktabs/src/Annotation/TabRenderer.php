<?php

namespace Drupal\quicktabs\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a tab renderer item annotation object.
 *
 * Plugin Namespace: Plugin\quicktabs\TabRenderer.
 *
 * @see \Drupal\quicktabs\Plugin\TabRendererManager
 * @see plugin_api
 *
 * @Annotation
 */
class TabRenderer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the tab renderer.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

}
