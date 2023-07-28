<?php

namespace Drupal\dropsolid_purge\Plugin\Purge\DiagnosticCheck;

use Drupal\dropsolid_purge\HostingInfoFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;

/**
 * Verifies that only fully configured Varnish purgers load.
 *
 * @PurgeDiagnosticCheck(
 *   id = "dropsolid_purge_configuration",
 *   title = @Translation("Dropsolid Purge"),
 *   description = @Translation("Verifies that only fully configured Varnish purgers load."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"dropsolid_purge"}
 * )
 */
class ConfigurationCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;


  /**
   * @var \Drupal\dropsolid_purge\HostingInfoFactoryInterface
   */
  protected $hostingInfoFactory;

  /**
   * Constructs a \Drupal\purge\Plugin\Purge\DiagnosticCheck\PurgerAvailableCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purge executive service, which wipes content from external caches.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PurgersServiceInterface $purge_purgers, HostingInfoFactoryInterface $hostingInfoFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->purgePurgers = $purge_purgers;
    $this->hostingInfoFactory = $hostingInfoFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('purge.purgers'),
      $container->get('dropsolid_purge.hostinginfofactory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {

    // Load configuration objects for all enabled HTTP purgers.
    $plugins = [];
    foreach ($this->purgePurgers->getPluginsEnabled() as $id => $plugin_id) {
      if (in_array($plugin_id, ['dropsolid_purge'])) {

        if(!$this->hostingInfoFactory->getSiteName()){
          $this->recommendation = $this->t("Site name missing from settings.php file. Please consult the readme file");
          return SELF::SEVERITY_ERROR;
        }
        if(!$this->hostingInfoFactory->getSiteEnvironment()){
          $this->recommendation = $this->t("Site environment missing from settings.php file. Please consult the readme file");
          return SELF::SEVERITY_ERROR;
        }
        if(!$this->hostingInfoFactory->getSiteGroup()){
          $this->recommendation = $this->t("Site group missing from settings.php file. Please consult the readme file");
          return SELF::SEVERITY_ERROR;
        }
        if(!$this->hostingInfoFactory->getLoadBalancersConfig()){
          $this->recommendation = $this->t("No loadbalancers configured in the settings.php file. Please consult the readme file");
          return SELF::SEVERITY_ERROR;
        }else{
          foreach ($this->hostingInfoFactory->getLoadBalancersConfig() as $name => $loadBalancer) {
            if(empty($loadBalancer['ip'])){
              $this->recommendation = $this->t("Loadbalancer @name is not configured correctly. Please consult the readme file", ['@name' => $name]);
              return SELF::SEVERITY_ERROR;
            }
          }
        }

        $this->recommendation = $this->t("All purgers configured.");
        return SELF::SEVERITY_OK;
      }
    }
  }

}
