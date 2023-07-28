<?php

namespace Drupal\mauticorg_core\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor for ContentTypeFacetLabelProcessor.
 *
 * @FacetsProcessor(
 *   id = "content_type_label_facet",
 *   label = @Translation("Custom content type labels"),
 *   description = @Translation("Transform facet labels."),
 *   stages = {
 *     "build" = 35
 *   }
 * )
 */
class ContentTypeFacetLabelProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var \Drupal\facets\Result\Result $result */
    foreach ($results as $result) {
      if ($result->getRawValue() == 'blog') {
        $result->setDisplayValue("Blog Posts");
      }

      if ($result->getRawValue() == 'landing_page') {
        $result->setDisplayValue("Articles");
      }
    }

    return $results;
  }

}
