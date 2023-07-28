<?php

namespace Drupal\quicktabs\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a tab type item annotation object.
 *
 * Plugin Namespace: Plugin\quicktabs\TabType.
 *
 * @see \Drupal\quicktabs\Plugin\TabTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class TabType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the tab type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

}
