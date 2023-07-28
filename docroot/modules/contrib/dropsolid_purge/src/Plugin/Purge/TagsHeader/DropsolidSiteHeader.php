<?php

namespace Drupal\dropsolid_purge\Plugin\Purge\TagsHeader;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderBase;
use Drupal\dropsolid_purge\HostingInfoInterface;

/**
 * Exports the X-Dropsolid-Site header.
 *
 * @PurgeTagsHeader(
 *   id = "dropsolidpurgesiteheader",
 *   header_name = "X-Dropsolid-Site",
 * )
 */
class DropsolidSiteHeader extends TagsHeaderBase implements TagsHeaderInterface {

  /**
   * The identifier for this site.
   *
   * @var string
   */
  protected $identifier = '';

  /**
   * Constructs a DropsolidSiteHeader object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\dropsolid_purge\HostingInfoInterface $dropsolid_purge_hostinginfo
   *   Provides technical information accessors for your environment.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, HostingInfoInterface $dropsolid_purge_hostinginfo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->identifier = $dropsolid_purge_hostinginfo->getSiteIdentifier();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('dropsolid_purge.hostinginfo')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $tags) {
    return $this->identifier;
  }

}
