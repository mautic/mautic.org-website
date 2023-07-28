<?php

namespace Drupal\quicktabs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for QuickTabsInstance.
 */
interface QuickTabsInstanceInterface extends ConfigEntityInterface {

  /**
   * Returns the label for the current instance.
   *
   * @return string
   *   Label string.
   */
  public function getLabel();

  /**
   * Returns the machine name of the plugin that will render this instance.
   *
   * @return string
   *   Renderer name string.
   */
  public function getRenderer();

  /**
   * Returns the array of options that current instance will use to build a tab.
   *
   * @return array
   *   Instance options array.
   */
  public function getOptions();

  /**
   * Returns boolean value of empty tabs setting.
   *
   * @return bool
   *   Empty tabs setting.
   */
  public function getHideEmptyTabs();

  /**
   * Returns the number of the default tab for this instance.
   *
   * @return string
   *   Default tab number.
   */
  public function getDefaultTab();

  /**
   * Returns the array of data that will be used to build the tabs.
   *
   * @return array
   *   Data for tabs.
   */
  public function getConfigurationData();

  /**
   * Sets the configuration data array.
   *
   * @param array $configuration_data
   *   Configuration data array.
   */
  public function setConfigurationData(array $configuration_data);

}
