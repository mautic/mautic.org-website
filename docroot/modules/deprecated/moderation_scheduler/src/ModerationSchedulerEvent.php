<?php

namespace Drupal\moderation_scheduler;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a moderation scheduler event for event listeners.
 */
class ModerationSchedulerEvent extends Event {

  /**
   * Node object.
   *
   * @var Drupal\Core\Entity\EntityInterface
   */
  protected $node;

  /**
   * Constructs a scheduler event object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node object that caused the event to fire.
   */
  public function __construct(EntityInterface $node) {
    $this->node = $node;
  }

  /**
   * Gets node object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The node object that caused the event to fire.
   */
  public function getNode() {
    return $this->node;
  }

}
