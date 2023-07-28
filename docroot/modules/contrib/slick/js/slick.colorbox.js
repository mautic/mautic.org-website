/**
 * @file
 * Provides Colorbox integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Slick Colorbox utility functions.
   *
   * @namespace
   */
  Drupal.slickColorbox = Drupal.slickColorbox || {

    /**
     * Sets method related to Slick methods.
     *
     * @name set
     *
     * @param {string} method
     *   The method to apply to .slick__slider element.
     */
    set: function (method) {
      var $box = $.colorbox.element();
      var $slider = $box.closest('.slick__slider');
      var $clone = $slider.find('.slick-cloned .litebox');
      var total = $slider.find('.slick__slide:not(.slick-cloned) .litebox').length;
      var $counter = $('#cboxCurrent');
      var curr;

      if (!$slider.length) {
        return;
      }

      // Fixed for unwanted clones with Infinite being enabled.
      // This basically tells Colorbox to not count/ process clones.
      var attach = function (attach) {
        if ($clone.length) {
          $clone.each(function (i, box) {
            $(box)[attach ? 'addClass' : 'removeClass']('cboxElement');
            Drupal[attach ? 'attachBehaviors' : 'detachBehaviors'](box);
          });
        }
      };

      // Cannot use dataSlickIndex which maybe negative with slick clones.
      curr = Math.abs($box.closest('.slick__slide').data('delta'));
      if (isNaN(curr)) {
        curr = 0;
      }

      if (method === 'cbox_load') {
        attach(false);
      }
      else if (method === 'cbox_complete') {
        // Actually only needed at first launch, but no first launch event.
        if ($counter.length) {
          var current = drupalSettings.colorbox.current || false;
          if (current) {
            current = current.replace('{current}', (curr + 1)).replace('{total}', total);
          }
          else {
            current = Drupal.t('@curr of @total', {'@curr': (curr + 1), '@total': total});
          }
          $counter.text(current);
        }
      }
      else if (method === 'cbox_closed') {
        // DOM fix randomly weird messed up DOM (blank slides) after closing.
        window.setTimeout(function () {
          attach(true);

          // Fixes Firefox, IE width recalculation after closing the colorbox.
          $slider.slick('refresh');
        }, 10);
      }
      else if (method === 'slickPause') {
        $slider.slick(method);
      }
    }
  };

  /**
   * Adds each slide a reliable ordinal to get correct current with clones.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The slick HTML element.
   */
  function doSlickColorbox(i, elm) {
    $('.slick__slide', elm).each(function (j, el) {
      $(el).attr('data-delta', j);
    });
  }

  /**
   * Attaches slick behavior to HTML element identified by .slick--colorbox.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slickColorbox = {
    attach: function (context) {
      var me = Drupal.slickColorbox;

      $(context).on('cbox_open', function () {
        me.set('slickPause');
      });

      $(context).on('cbox_load', function () {
        me.set('cbox_load');
      });

      $(context).on('cbox_complete', function () {
        me.set('cbox_complete');
      });

      $(context).on('cbox_closed', function () {
        me.set('cbox_closed');
      });

      var $slick = $('.slick--colorbox', context);
      if ($slick && $slick.length) {
        $slick.once('slick-colorbox').each(doSlickColorbox);
      }
    }
  };

}(jQuery, Drupal));
