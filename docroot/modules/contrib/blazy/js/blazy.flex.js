/**
 * @file
 * Provides CSS3 flex based on Flexbox layout.
 *
 * Credit: https://fjolt.com/article/css-grid-masonry
 */

(function (Drupal, _db, _win) {

  'use strict';

  /**
   * Applies height adjustments to each item.
   *
   * @param {HTMLElement} elm
   *   The container HTML element.
   */
  function doFlex(elm) {
    var _box = '.grid';
    var heights = {};
    var box = elm.querySelector(_box);

    if (box === null) {
      return;
    }

    var parentWith = elm.getBoundingClientRect().width;
    var boxWith = box.getBoundingClientRect().width;
    var style = _win.getComputedStyle(box);
    var itemWith = boxWith + parseFloat(style.marginLeft) + parseFloat(style.marginRight);
    var columnWidth = Math.round((1 / (itemWith / parentWith)));
    var items = elm.querySelectorAll(_box);

    function doFlexItem(item, id) {
      var cn = item.querySelector(_box + '__content');
      var cr = cn.getBoundingClientRect();
      var ch = cr.height;
      var curColumn = id % columnWidth;
      var style = _win.getComputedStyle(item);

      if (typeof heights[curColumn] === 'undefined') {
        heights[curColumn] = 0;
      }

      item.style.height = ch + 'px';
      heights[curColumn] += ch + parseFloat(style.marginBottom);

      // If the item has an item above it, then move it to fill the gap.
      if (id - columnWidth >= 0) {
        var nh = id - columnWidth + 1;
        var itemAbove = elm.querySelector(_box + ':nth-of-type(' + nh + ')');
        var prevBottom = itemAbove.getBoundingClientRect().bottom;
        var currentTop = cr.top - parseFloat(style.marginBottom);

        item.style.top = '-' + (currentTop - prevBottom) + 'px';
      }
    }

    function init() {
      _db.forEach(items, doFlexItem);

      var max = Math.max.apply(null, Object.values(heights));
      elm.style.height = max + 'px';
    }

    _db.bindEvent(_win, 'load resize', Drupal.debounce(init, 200, true));

    elm.classList.add('is-b-loading');
    _win.setTimeout(function () {
      elm.classList.remove('is-b-loading');
    }, 600);
  }

  /**
   * Attaches Blazy behavior to HTML element identified by .block-flex.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyFlex = {
    attach: function (context) {
      if ('length' in context) {
        context = context[0];
      }

      var elms = context.querySelectorAll('.block-flex');
      if (elms.length) {
        _db.once(_db.forEach(elms, doFlex, context));
      }
    }
  };

}(Drupal, dBlazy, this));
