<?php

namespace Drupal\moderation_scheduler\Plugin\QueueWorker;

/**
 * A report worker.
 *
 * @QueueWorker(
 *   id = "moderation_scheduler_queue_2",
 *   title = @Translation("Second worker in moderation_scheduler"),
 *   cron = {"time" = 20}
 * )
 *
 * @see queue_example.module
 */
class ReportWorkerTwo extends ReportWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->reportWork(2, $data);
  }

}
