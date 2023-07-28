<?php

namespace Drupal\mauticorg_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Page 404 Content' Block.
 *
 * @Block(
 *   id = "page_404_content_block",
 *   admin_label = @Translation("Page 404 Content Block"),
 *   category = @Translation("Page 404 Content Block"),
 * )
 */
class Page404ContentBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Configure value of bottom_block_content_page_404.
    $bottom_block_content_page_404 = $this->configFactory->get('mauticorg_core.site_settings')->get('bottom_block_content_page_404')['value'];
    return [
      '#markup' => $bottom_block_content_page_404,
    ];
  }

}
