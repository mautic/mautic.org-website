<?php

namespace Drupal\discourse_comments\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'comment_count_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "comment_count_formatter",
 *   label = @Translation("Comment count formatter"),
 *   field_types = {
 *     "discourse_field"
 *   }
 * )
 */
class CommentCountFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $item->comment_count];
    }

    return $elements;
  }

}
