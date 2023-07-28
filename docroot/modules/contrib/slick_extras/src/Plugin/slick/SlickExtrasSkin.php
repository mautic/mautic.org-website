<?php

namespace Drupal\slick_extras\Plugin\slick;

use Drupal\slick\SlickSkinPluginBase;
use Drupal\slick_extras\SlickExtrasSkinTrait;

/**
 * Provides slick extras skins.
 *
 * @SlickSkin(
 *   id = "slick_extras_skin",
 *   label = @Translation("Slick extras skin")
 * )
 */
class SlickExtrasSkin extends SlickSkinPluginBase {

  use SlickExtrasSkinTrait;

  /**
   * Sets the slick skins.
   *
   * @inheritdoc
   */
  protected function setSkins() {
    return $this->definedSkins();
  }

}
