<?php

namespace Drupal\quicktabs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\quicktabs\TabTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a controller for content retrieved through AJAX.
 */
class QuickTabsController extends ControllerBase {

  /**
   * The tab type manager.
   *
   * @var \Drupal\quicktabs\TabTypeManager
   */
  protected $tabTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a BlockComponentRenderArray object.
   *
   * @param \Drupal\quicktabs\TabTypeManager $tab_type
   *   The tab type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(TabTypeManager $tab_type, EntityTypeManagerInterface $entity_type_manager) {
    $this->tabTypeManager = $tab_type;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.tab_type'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxContent($js, $instance, $tab) {
    if ($js === 'nojs') {
      return [];
    }
    else {
      $qt = $this->entityTypeManager->getStorage('quicktabs_instance')->load($instance);
      $configuration_data = $qt->getConfigurationData();
      $object = $this->tabTypeManager->createInstance($configuration_data[$tab]['type']);
      $render = $object->render($configuration_data[$tab]);

      $element_id = '#quicktabs-tabpage-' . $instance . '-' . $tab;
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new HtmlCommand($element_id, $render));
      return $ajax_response;
    }
  }

}
