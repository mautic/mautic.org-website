/**
 * @file
 */

(function ($, Drupal, drupalSettings, window) {

  'use strict';

  var cboxTimer;
  var $body = $('body');

  /**
   * Blazy Colorbox utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} box
   *   The colorbox HTML element.
   */
  function blazyColorbox(i, box) {
    var $box = $(box);
    var media = $box.data('media') || {};
    var isMedia = media.type === 'video';
    var isHtml = media.type === 'rich' && 'html' in media;
    var runtimeOptions = {
      html: isHtml ? media.html : null,
      rel: media.rel || null,
      iframe: isMedia,
      title: function () {
        var $caption = $box.next('.litebox-caption');
        return $caption.length ? $caption.html() : '';
      },
      onComplete: function () {
        removeClasses();
        $body.addClass('colorbox-on colorbox-on--' + media.type);

        if (isMedia || isHtml) {
          resizeBox();
          $body.addClass(isMedia ? 'colorbox-on--media' : 'colorbox-on--html');
        }
      },
      onClosed: function () {
        var $media = $('#cboxContent').find('.media');
        if ($media.length) {
          Drupal.detachBehaviors($media[0]);
        }
        removeClasses();
      }
    };

    /**
     * Remove the custom colorbox classes.
     */
    function removeClasses() {
      $body.removeClass(function (index, css) {
        return (css.match(/(^|\s)colorbox-\S+/g) || []).join(' ');
      });
    }

    /**
     * Resize the responsive image.
     */
    function resizeImage() {
      var t = $(this);
      var w = t.width();
      var h = t.height();
      var p = t.closest('#cboxLoadedContent');
      var pw = p.width();
      var ph = p.height();

      if (h > ph) {
        t.css('top', -(h - ph) / 2);
      }
      else if (h < ph) {
        t.css({height: ph, width: 'auto'});
        t.css('left', -(t.width() - pw) / 2);
      }
      else if (pw > w) {
        $.colorbox.resize({
          innerWidth: w,
          innerHeight: h
        });
      }
    }

    /**
     * Resize the colorbox.
     */
    function resizeBox() {
      window.clearTimeout(cboxTimer);

      var mw = drupalSettings.colorbox.maxWidth;
      var mh = drupalSettings.colorbox.maxHeight;

      var o = {
        width: media.width || mw,
        height: media.height || mh
      };

      cboxTimer = window.setTimeout(function () {
        if ($('#cboxOverlay').is(':visible')) {
          var $container = $('#cboxLoadedContent');
          var $iframe = $('.cboxIframe', $container);
          var $media = $('.media--ratio', $container);
          var $picture = $container.find('picture img');
          var $resimage = $container.find('img[srcset]');
          var isResimage = $resimage.length || $picture.length;

          if (isResimage) {
            var $img = $picture.length ? $picture : $resimage;
            window.setTimeout(function () {
              $img.each(function () {
                if (this.complete) {
                  resizeImage.call(this);
                }
                else {
                  $(this).one('load', resizeImage);
                }
              });
            }, 101);

            o = {
              width: mw || media.width,
              height: mh || media.height
            };
          }

          if (!$iframe.length && $media.length) {
            Drupal.attachBehaviors($media[0]);
          }

          if ($iframe.length || $media.length) {
            // @todo consider to not use colorbox iframe for consistent .media.
            if ($iframe.length) {
              $container.addClass('media media--ratio');
              $iframe.attr('width', o.width).attr('height', o.height).addClass('media__element');
              $container.css({paddingBottom: (o.height / o.width) * 100 + '%', height: 0});
            }
          }
          else {
            $container.removeClass('media media--ratio');
            $container.css({paddingBottom: '', height: o.height}).removeClass('media__element');
          }

          $.colorbox.resize({
            innerWidth: o.width,
            innerHeight: o.height
          });
        }
      }, 10);
    }

    $box.colorbox($.extend({}, drupalSettings.colorbox, runtimeOptions));
  }

  /**
   * Attaches blazy colorbox behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyColorbox = {
    attach: function (context) {
      if (typeof drupalSettings.colorbox === 'undefined') {
        return;
      }

      if (drupalSettings.colorbox.mobiledetect && window.matchMedia) {
        // Disable Colorbox for small screens.
        var mq = window.matchMedia('(max-device-width: ' + drupalSettings.colorbox.mobiledevicewidth + ')');
        if (mq.matches) {
          return;
        }
      }

      $('[data-colorbox-trigger]', context).once('blazy-colorbox').each(blazyColorbox);
    }
  };

})(jQuery, Drupal, drupalSettings, this);
