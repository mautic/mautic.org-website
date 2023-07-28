<?php

namespace Drupal\quicktabs;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for tab type plugins.
 */
interface TabTypeInterface extends PluginInspectionInterface {

  /**
   * Return form elements used on the edit/add from.
   *
   * @param array $tab
   *   The array tab for display.
   *
   * @return array
   *   The options used for displaying tabs.
   */
  public function optionsForm(array $tab);

  /**
   * Return a render array for an individual tab tat the theme layer to process.
   *
   * @return string
   *   @todo test if changing type to array works
   */
  public function render(array $tab);

}
