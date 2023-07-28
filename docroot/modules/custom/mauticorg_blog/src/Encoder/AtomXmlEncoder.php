<?php

declare(strict_types = 1);

namespace Drupal\mauticorg_blog\Encoder;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\serialization\Encoder\XmlEncoder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder as BaseXmlEncoder;

/**
 * Provides an atom xml encoder for blog posts.
 */
class AtomXmlEncoder extends XmlEncoder {

  /**
   * The site config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  protected static $format = ['atom_xml'];

  /**
   * Constructor for AtomXmlEncoder.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack
  ) {
    $this->config = $config_factory->get('system.site');
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseEncoder(): EncoderInterface {
    $encoder = new BaseXmlEncoder();
    $encoder->setRootNodeName('rss');

    return $encoder;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []): string {
    $document = [
      '@xmlns:atom' => 'http://www.w3.org/2005/Atom',
      '@xmlns:media' => 'http://search.yahoo.com/mrss/',
      '@xmlns:content' => 'http://purl.org/rss/1.0/modules/content/',
      '@version' => '2.0',
      'channel' => [
        'title' => $this->config->get('name'),
        'description' => $this->config->get('slogan'),
        'link' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
        'atom:link' => [
          '@href' => Url::fromRoute(
            '<current>',
            [],
            [
              'query' => $this->request->query->all(),
              'absolute' => TRUE,
            ]
          )->toString(),
          '@rel' => 'self',
        ],
        'item' => $data,
      ],
    ];

    return parent::encode($document, $format, $context);
  }

}
