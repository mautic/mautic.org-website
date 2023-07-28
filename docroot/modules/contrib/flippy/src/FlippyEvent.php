<?php

namespace Drupal\flippy;

use Symfony\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

/**
 * Defines a Flippy Node event.
 */
class FlippyEvent extends Event {

  protected $queries;
  protected $node;

  /**
   * FlippyEvent constructor.
   *
   * @param array $queries
   *   The queries for this event.
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   */
  public function __construct(array $queries, NodeInterface $node) {
    $this->queries = $queries;
    $this->node = $node;
  }

  /**
   * Getter for query array.
   *
   * @return array
   *   The queries for this event.
   */
  public function getQueries() {
    return $this->queries;
  }

  /**
   * Setter for query array.
   *
   * @param array $queries
   *   The queries for this event.
   */
  public function setQueries(array $queries) {
    $this->queries = $queries;
  }

  /**
   * Getter for node.
   *
   * @return \Drupal\node\NodeInterface
   *   The node object for this event.
   */
  public function getNode() {
    return $this->node;
  }

}
