<?php

namespace Drupal\moderation_scheduler;

/**
 * Contains all events dispatched by Moderation Scheduler.
 */
final class ModerationSchedulerEvents {

  /**
   * The event triggered after a node is published via cron.
   *
   * This event allows modules to react after a node is published. The event
   * listener method receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\moderation_scheduler\ModerationSchedulerEvent
   *
   * @var string
   */
  const PUBLISH = 'moderation_scheduler.publish';

}
