<?php

namespace Drupal\slick_example;

/**
 * A Trait common during transition from hook_hook_info to plugin system.
 *
 * @todo deprecate and remove at slick:9.x-1.0, not slick:8.x-3.0.
 * @see https://www.drupal.org/node/3105648
 */
trait SlickExampleSkinTrait {

  /**
   * Sets the slick skins.
   */
  protected function definedSkins() {
    $path  = base_path() . drupal_get_path('module', 'slick_example');
    $skins = [
      'x_testimonial' => [
        'name' => 'X: Testimonial',
        'description' => $this->t('Testimonial with thumbnail and description with slidesToShow 2.'),
        'group' => 'main',
        'provider' => 'slick_example',
        'css' => [
          'theme' => [
            $path . '/css/slick.theme--x-testimonial.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

}
