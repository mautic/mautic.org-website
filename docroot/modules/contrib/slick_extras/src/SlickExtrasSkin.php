<?php

namespace Drupal\slick_extras;

use Drupal\slick\SlickSkinInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Implements SlickSkinInterface as registered via hook_slick_skins_info().
 *
 * @todo deprecate and remove at slick:9.x-1.0, not slick:8.x-3.0.
 * @see https://www.drupal.org/node/3105648
 */
class SlickExtrasSkin implements SlickSkinInterface {

  use StringTranslationTrait;
  use SlickExtrasSkinTrait;

  /**
   * {@inheritdoc}
   */
  public function skins() {
    return $this->definedSkins();
  }

}
