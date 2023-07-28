<?php

namespace Drupal\slick_example\Plugin\slick;

use Drupal\slick\SlickSkinPluginBase;
use Drupal\slick_example\SlickExampleSkinTrait;

/**
 * Provides slick example skins.
 *
 * @SlickSkin(
 *   id = "slick_example_skin",
 *   label = @Translation("Slick example skin")
 * )
 */
class SlickExampleSkin extends SlickSkinPluginBase {

  use SlickExampleSkinTrait;

  /**
   * Sets the slick skins.
   *
   * @inheritdoc
   */
  protected function setSkins() {
    return $this->definedSkins();
  }

}
