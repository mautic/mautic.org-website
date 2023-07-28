/**
 * @file
 * Quicktabs form behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Shows/hides options forms for various QT plugins.
   *
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the show/hide functionality to each table row in the QT edit form.
   */
  Drupal.quicktabsShowHide = function() {
    $(this).parents('tr').find('div.qt-tab-' + this.value + '-options-form').show().siblings('div.qt-tab-options-form').hide();
  };

  Drupal.behaviors.quicktabsform = {
    attach: function (context, settings) {
      $('#quicktab-instance-edit tr').each(function(index) {
        var currentRow = $(this),
          select = currentRow.find('div.form-item :input[name*="type"]');

        select.bind('change', Drupal.quicktabsShowHide);
        select.trigger('change');
      });
    }
  };

})(jQuery, Drupal);
