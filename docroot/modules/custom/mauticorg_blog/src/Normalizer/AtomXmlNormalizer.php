<?php

declare(strict_types = 1);

namespace Drupal\mauticorg_blog\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Drupal\taxonomy\TermInterface;

/**
 * Provides an atom xml normalizer for blog posts.
 */
class AtomXmlNormalizer extends NormalizerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected $format = ['atom_xml'];

  /**
   * Constructor for AtomXmlNormalizer.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL): bool {
    return $data instanceof NodeInterface
      && $data->getType() === 'blog'
      && $this->checkFormat($format);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($node, $format = NULL, array $context = []): array {
    $build = [
      'guid' => $node->uuid(),
      'title' => htmlentities($node->getTitle()),
      'pubDate' => $this->formatTimestampToDate((int) $node->getCreatedTime()),
      'updated' => $this->formatTimestampToDate((int) $node->getChangedTime()),
      'link' => Url::fromRoute(
        'entity.node.canonical',
        ['node' => $node->id()],
        ['absolute' => TRUE]
      )->toString(),
      'author' => htmlentities($node->getOwner()->get('field_display_name')->value),
      'category' => array_map(function (TermInterface $category): string {
        return htmlentities($category->getName());
      }, $node->get('field_category')->referencedEntities()),
      'content:encoded' => $node->get('body')->value,
    ];

    if (!$node->get('field_tags')->isEmpty()) {
      $build += [
        'tags' => array_map(function (TermInterface $tag): string {
          return htmlentities($tag->getName());
        }, $node->get('field_tags')->referencedEntities()),
      ];
    }

    if (!$node->get('field_featured_image')->isEmpty()) {
      $build += [
        'media:content' => [
          '@url' => $this->transformImageField(
            $node,
            function (FileInterface $file): string {
              $image_style = $this->entityTypeManager
                ->getStorage('image_style')
                ->load('blog_detail_featured_image_720x330');

              return $image_style->buildUrl($file->getFileUri());
            }
          ),
          '@type' => $this->transformImageField(
            $node,
            function (FileInterface $file): string {
              return $file->getMimeType();
            }
          ),
        ],
      ];
    }

    return $build;
  }

  /**
   * Format a timestamp to RFC 822.
   *
   * @param int $timestamp
   *   The timestamp.
   *
   * @return string
   *   The date in RFC 822 format.
   */
  protected function formatTimestampToDate(int $timestamp): string {
    return (new \DateTime())
      ->setTimestamp($timestamp)
      ->format(\DateTimeInterface::RSS);
  }

  /**
   * Transform the media image field to a string.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The blog post.
   * @param \Closure $callback
   *   A callback function transforming the file entity referenced by the media.
   *
   * @return string
   *   A string transformed from the media entity.
   */
  protected function transformImageField(
    NodeInterface $node,
    \Closure $callback
  ): string {
    $media = $node->get('field_featured_image')->referencedEntities()[0] ?? NULL;
    if (!$media) {
      return '';
    }

    $file = $media->get('field_media_image')->referencedEntities()[0] ?? NULL;
    if (!$file) {
      return '';
    }

    return \call_user_func($callback, $file);
  }

}
