<?php

namespace Drupal\moderation_scheduler\Plugin\QueueWorker;

/**
 * A report worker.
 *
 * @QueueWorker(
 *   id = "moderation_scheduler_queue_1",
 *   title = @Translation("First worker in moderation_scheduler"),
 *   cron = {"time" = 1}
 * )
 *
 * @see queue_example.module
 */
class ReportWorkerOne extends ReportWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->reportWork(1, $data);
  }

}
