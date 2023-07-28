<?php

namespace Drupal\moderation_scheduler\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Class ModerationSchedulerField.
 *
 * @ViewsField("moderation_scheduler_query__field_scheduled_time")
 */
class ModerationSchedulerField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $dateimte = $this->getValue($values);
    if ($dateimte) {
      return new FormattableMarkup($dateimte);
    }
  }

}
