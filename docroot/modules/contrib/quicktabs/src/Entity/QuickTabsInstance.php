<?php

namespace Drupal\quicktabs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the QuickTabsInstance entity.
 *
 * The QuickTabsInstnace entity stores information about a quicktab.
 *
 * @ConfigEntityType(
 *   id = "quicktabs_instance",
 *   label = @Translation("Quick Tabs"),
 *   module = "quicktabs",
 *   handlers = {
 *     "list_builder" = "Drupal\quicktabs\QuickTabsInstanceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\quicktabs\Form\QuickTabsInstanceEditForm",
 *       "edit" = "Drupal\quicktabs\Form\QuickTabsInstanceEditForm",
 *       "delete" = "Drupal\quicktabs\Form\QuickTabsInstanceDeleteForm",
 *       "duplicate" = "Drupal\quicktabs\Form\QuickTabsInstanceDuplicateForm",
 *     },
 *   },
 *   config_prefix = "quicktabs_instance",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit" = "/admin/structure/quicktabs/{quicktabs_instance}/edit",
 *     "add" = "/admin/structure/quicktabs/add",
 *     "delete" = "/admin/structure/quicktabs/{quicktabs_instance}/delete",
 *     "duplicate" = "/admin/structure/quicktabs/{quicktabs_instance}/duplicate"
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "label" = "label",
 *     "renderer" = "renderer",
 *     "options" = "options",
 *     "hide_empty_tabs" = "hide_empty_tabs",
 *     "default_tab" = "default_tab",
 *     "configuration_data" = "configuration_data"
 *   },
 *   admin_permission = "administer quicktabs",
 * )
 */
class QuickTabsInstance extends ConfigEntityBase implements QuickTabsInstanceInterface {

  const QUICKTABS_DELTA_NONE = '9999';

  /**
   * The QuickTabs Instance ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the QuickTabs Instance.
   *
   * @var string
   */
  protected $label;

  /**
   * The renderer of the QuickTabs Instance.
   *
   * @var string
   */
  protected $renderer;

  /**
   * Options provided by rederer plugins.
   *
   * @var bool
   */
  protected $options;

  /**
   * Whether or not to hide empty tabs.
   *
   * @var bool
   */
  protected $hide_empty_tabs;

  /**
   * Whether or not to hide empty tabs.
   *
   * @var bool
   */
  protected $default_tab;

  /**
   * Required to render this instance.
   *
   * @var array
   */
  protected $configuration_data;

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderer() {
    return $this->renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getHideEmptyTabs() {
    return $this->hide_empty_tabs;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultTab() {
    return $this->default_tab;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationData() {
    return $this->configuration_data;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationData(array $configuration_data) {
    $this->configuration_data = $configuration_data;
  }

  /**
   * Returns a render array to be used in a block or page.
   *
   * @return array
   *   A render array.
   */
  public function getRenderArray() {
    $type = \Drupal::service('plugin.manager.tab_renderer');
    $renderer = $type->createInstance($this->getRenderer());

    \Drupal::moduleHandler()->alter('quicktabs_instance', $this);

    return $renderer->render($this);
  }

  /**
   * Loads a quicktabs_instance from configuration and returns it.
   *
   * @param string $id
   *   The qti ID to load.
   *
   * @return \Drupal\quicktabs\Entity\QuickTabsInstance
   *   The loaded entity.
   */
  public static function getQuickTabsInstance($id) {
    $qt = \Drupal::service('entity_type.manager')->getStorage('quicktabs_instance')->load($id);
    return $qt;
  }

}
