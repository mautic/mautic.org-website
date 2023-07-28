<?php

namespace Drupal\slick_extras;

/**
 * A Trait common during transition from hook_hook_info to plugin system.
 */
trait SlickExtrasSkinTrait {

  /**
   * Sets the slick skins.
   */
  protected function definedSkins() {
    $slick = base_path() . drupal_get_path('module', 'slick');
    $path  = base_path() . drupal_get_path('module', 'slick_extras');
    $skins = [
      'd3-back' => [
        'name' => 'X 3d back',
        'group' => 'main',
        'provider' => 'slick_extras',
        'css' => [
          'theme' => [
            $path . '/css/theme/slick.theme--d3-back.css' => [],
          ],
        ],
        'description' => $this->t('Adds 3d view with focal point at back, works best with 3 slidesToShow, and caption below.'),
      ],
      'boxed' => [
        'name' => 'X Boxed',
        'group' => 'main',
        'provider' => 'slick_extras',
        'description' => $this->t('Adds margins to the sides of slick-list revealing arrows.'),
        'css' => [
          'theme' => [
            $path . '/css/theme/slick.theme--boxed.css' => [],
          ],
        ],
      ],
      'boxed-carousel' => [
        'name' => 'X Box carousel',
        'group' => 'main',
        'provider' => 'slick_extras',
        'description' => $this->t('Carousel that has margins, alternative to centerMode.'),
        'css' => [
          'theme' => [
            $path . '/css/theme/slick.theme--boxed.css' => [],
            $path . '/css/theme/slick.theme--boxed--carousel.css' => [],
          ],
        ],
      ],
      'boxed-split' => [
        'name' => 'X Box split',
        'group' => 'main',
        'provider' => 'slick_extras',
        'description' => $this->t('Adds margins and split caption and image.'),
        'css' => [
          'theme' => [
            $path . '/css/theme/slick.theme--boxed.css' => [],
            $slick . '/css/theme/slick.theme--split.css' => [],
          ],
        ],
      ],
      'rounded' => [
        'name' => 'X Rounded',
        'group' => 'main',
        'provider' => 'slick_extras',
        'description' => $this->t('Rounds the .slide__image, great for 3-5 visible-slides carousel.'),
        'css' => [
          'theme' => [
            $path . '/css/theme/slick.theme--rounded.css' => [],
          ],
        ],
      ],
      'vtabs' => [
        'name' => 'X VTabs',
        'group' => 'thumbnail',
        'provider' => 'slick_extras',
        'description' => $this->t('Adds a vertical tabs like thumbnail navigation.'),
        'css' => [
          'theme' => [
            $path . '/css/theme/slick.theme--vtabs.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

}
