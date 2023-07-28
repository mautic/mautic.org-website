<?php

namespace Drupal\google_analytics\Component\Render;

use Drupal\Component\Render\MarkupInterface;

/**
 * Formats a string for JavaScript display.
 */
class GoogleAnalyticsJavaScriptSnippet implements MarkupInterface {

  /**
   * The string to escape.
   *
   * @var string
   */
  protected $string;

  /**
   * Constructs an HtmlEscapedText object.
   *
   * @param string $string
   *   The string to escape. This value will be cast to a string.
   */
  public function __construct($string) {
    $this->string = (string) $string;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->string;
  }

  /**
   * Returns a representation of the google analytics javascript snippet.
   *
   * @return mixed
   *   The safe string content.
   */
  public function jsonSerialize(): mixed {
    return $this->__toString();
  }

}
