<?php

namespace Drupal\noreqnewpass\Services;

use Drupal\Core\Flood\FloodInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the database flood backend. This is the default Drupal backend.
 */
class NoreqnewpassFlood {

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * Constructs a new UserLoginForm.
   *
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   */
  public function __construct(FloodInterface $flood) {
    $this->flood = $flood;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood')
    );
  }

  /**
   * Implements Drupal\noreqnewpass\NoreqnewpassFlood::register().
   */
  public function noreqnewpassregister($name, $window = 3600, $identifier = NULL) {
    $this->flood->register($name, $window = 3600, $identifier);
  }

  /**
   * Implements Drupal\noreqnewpass\NoreqnewpassFlood::clear().
   */
  public function noreqnewpassclear($name, $identifier = NULL) {
    $this->flood->clear($name, $identifier);
  }

}
