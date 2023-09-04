<?php

namespace Drupal\flippy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\flippy\FlippyPager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a "Flippy" block.
 *
 * @Block(
 *   id = "flippy_block",
 *   admin_label = @Translation("Flippy Block")
 * )
 */
class FlippyBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The http request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The flippy pager service.
   *
   * @var \Drupal\flippy\FlippyPager
   */
  protected $flippyPager;

  /**
   * The flippy Settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $flippySettings;

  /**
   * The current route service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $requestStack, FlippyPager $flippyPager, ConfigFactoryInterface $configFactoryInterface, CurrentRouteMatch $routeMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->request = $requestStack->getCurrentRequest();
    $this->flippyPager = $flippyPager;
    $this->flippySettings = $configFactoryInterface->get('flippy.settings');
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('flippy.pager'),
      $container->get('config.factory'),
      $container->get('current_route_match')
    );
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $build = [];
    // Detect if we're viewing a node.
    if ($node = $this->request->attributes->get('node')) {
      // Make sure this node type is still enabled.
      if ($this->flippyPager->flippy_use_pager($node)) {
        $build = [
          '#theme'    => 'flippy',
          '#list'     => $this->flippyPager->flippy_build_list($node),
          '#node'     => $node,
          '#attached' => [
            'library' => [
              'flippy/drupal.flippy',
            ],
          ],
        ];
        // Set head elements.
        if (is_object($node)) {
          if ($this->flippySettings->get('flippy_head_' . $node->getType())) {
            $links = $this->flippyPager->flippy_build_list($node);
            if (!empty($links['prev']['nid'])) {
              $build['#attached']['html_head_link'][][] = [
                'rel' => 'prev',
                'href' => Url::fromRoute('entity.node.canonical', ['node' => $links['prev']['nid']])->toString(),
              ];
            }
            if (!empty($links['next']['nid'])) {
              $build['#attached']['html_head_link'][][] = [
                'rel' => 'next',
                'href' => Url::fromRoute('entity.node.canonical', ['node' => $links['next']['nid']])->toString(),
              ];
            }
          }
        }
      }
    }

    return $build;
  }

  /**
   * Implements \Drupal\Core\Entity\Entity::getCacheTags().
   */
  public function getCacheTags() {
    if ($node = $this->routeMatch->getParameter('node')) {
      $cache_tags = Cache::mergeTags($node->getCacheTags(), ['node_list']);
      return Cache::mergeTags(parent::getCacheTags(), $cache_tags);
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   * Implements \Drupal\Core\Entity\Entity::getCacheContexts().
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
