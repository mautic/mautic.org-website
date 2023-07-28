/**
 * @file
 * Adds left- and right-arrow button support to flippy.
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.flippy = {
    attach: function (context, settings) {
      $(document).keydown(function (event) {
        switch (event.which) {
          case 37:
            leftArrowPressed();
            break;

          case 39:
            rightArrowPressed();
            break;
        }
      });

      var hammer = new Hammer(document);
      hammer.on('swipeleft', function () {
        leftArrowPressed();
      });

      hammer.on('swiperight', function () {
        rightArrowPressed();
      });

      function leftArrowPressed() {
        if ($('.flippy-previous a', context).length) {
          window.location = $('.flippy-previous a', context).attr('href');
        }
      }

      function rightArrowPressed() {
        if ($('.flippy-next a', context).length) {
          window.location = $('.flippy-next a', context).attr('href');
        }
      }
    }
  };
})(jQuery, Drupal);
