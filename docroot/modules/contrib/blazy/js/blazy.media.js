/**
 * @file
 * Provides Media module integration.
 *
 * @todo use classList anytime.
 */

(function (Drupal, _db) {

  'use strict';

  /**
   * Blazy media utility functions.
   *
   * @param {HTMLElement} media
   *   The media player HTML element.
   */
  function blazyMedia(media) {
    var t = media;
    var iframe = t.querySelector('iframe');
    var btn = t.querySelector('.media__icon--play');

    // Media player toggler is disabled, just display iframe.
    if (btn === null) {
      return;
    }

    var url = btn.getAttribute('data-url');
    var title = btn.getAttribute('data-iframe-title');
    var newIframe;

    /**
     * Play the media.
     *
     * @param {Event} event
     *   The event triggered by a `click` event.
     *
     * @return {bool}|{mixed}
     *   Return false if url is not available.
     */
    function play(event) {
      event.preventDefault();

      // oEmbed/ Soundcloud needs internet, fails on disconnected local.
      if (url === '') {
        return false;
      }

      var target = this;
      var player = target.parentNode;
      var playing = document.querySelector('.is-playing');
      var iframe = player.querySelector('iframe');

      url = target.getAttribute('data-url');
      title = target.getAttribute('data-iframe-title');

      // First, reset any video to avoid multiple videos from playing.
      if (playing !== null) {
        var played = document.querySelector('.is-playing iframe');
        // Remove the previous iframe.
        if (played !== null) {
          playing.removeChild(played);
        }
        playing.className = playing.className.replace(/(\S+)playing/, '');
      }

      // Appends the iframe.
      player.className += ' is-playing';

      // Remove the existing iframe on the current clicked iframe.
      if (iframe !== null) {
        player.removeChild(iframe);
      }

      // Cache iframe for the potential repeating clicks.
      if (!newIframe) {
        newIframe = document.createElement('iframe');
        newIframe.className = 'media__iframe media__element';
        newIframe.setAttribute('src', url);
        newIframe.setAttribute('allowfullscreen', true);
        newIframe.setAttribute('title', title);
      }

      player.appendChild(newIframe);
    }

    /**
     * Close the media.
     *
     * @param {Event} event
     *   The event triggered by a `click` event.
     */
    function stop(event) {
      event.preventDefault();

      var target = this;
      var player = target.parentNode;
      var iframe = player.querySelector('iframe');

      if (player.className.match('is-playing')) {
        player.className = player.className.replace(/(\S+)playing/, '');
      }

      if (iframe !== null) {
        player.removeChild(iframe);
      }
    }

    // Remove iframe to avoid browser requesting them till clicked.
    // The iframe is there as Blazy supports non-lazyloaded/ non-JS iframes.
    if (iframe !== null && iframe.parentNode != null) {
      iframe.parentNode.removeChild(iframe);
    }

    // Plays the media player.
    _db.on(t, 'click', '.media__icon--play', play);

    // Closes the video.
    _db.on(t, 'click', '.media__icon--close', stop);

    t.className += ' media--player--on';
  }

  /**
   * Theme function for a dynamic inline video.
   *
   * @param {Object} settings
   *   An object containing the link element which triggers the lightbox.
   *   This link must have [data-media] attribute containing video metadata.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   */
  Drupal.theme.blazyMedia = function (settings) {
    // PhotoSwipe5 has element, PhotoSwipe4 el, etc.
    var elm = settings.el || settings.element;
    var data = _db.attr(elm, 'data-media');
    data = data ? _db.parse(data) : {};
    var alt = _db.attr(elm, 'alt', 'Video preview');
    var width = data.width ? parseInt(data.width) : 0;
    var height = data.height ? parseInt(data.height) : 0;
    var pad = data ? ((height / width) * 100).toFixed(2) : 100;
    var imgUrl = _db.attr(elm, 'data-box-url');
    var href = _db.attr(elm, 'href');
    var oembedUrl = _db.attr(elm, 'data-oembed-url', href);
    var imgClass = settings.imgClass ? ' ' + settings.imgClass : '';
    var idClass = data.id ? ' media--' + data.id : '';
    var player = data.type === 'video' ? ' media--player' : '';
    var html;

    html = '<div class="media' + idClass + ' media--switch' + player + ' media--ratio media--ratio--fluid" style="padding-bottom: ' + pad + '%">';

    html += '<img src="' + imgUrl + '" class="media__image media__element' + imgClass + '" alt="' + Drupal.t(alt) + '" loading="lazy" decoding="async"/>';

    if (player) {
      html += '<span class="media__icon media__icon--close"></span>';
      html += '<span class="media__icon media__icon--play" data-url="' + oembedUrl + '"></span>';
    }

    html += '</div>';

    if (!settings.unwrap) {
      html = '<div class="media-wrapper media-wrapper--inline" style="width:' + width + 'px">' + html + '</div>';
    }

    return html;
  };

  /**
   * Attaches Blazy media behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyMedia = {
    attach: function (context) {

      // Originally identified at D7, yet might happen at D8 with AJAX.
      // Prevents jQuery AJAX messes up where context might be an array.
      if ('length' in context) {
        context = context[0];
      }

      var _player = '.media--player';
      var check = context.querySelector(_player);
      var items = check === null ? [] : context.querySelectorAll(_player + ':not(.media--player--on)');
      if (items.length) {
        _db.once(_db.forEach(items, blazyMedia));
      }

    }
  };

})(Drupal, dBlazy);
