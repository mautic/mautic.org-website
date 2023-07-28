(function ($, Drupal, drupalSettings) {

'use strict';

Drupal.behaviors.qt_ui_tabs = {
  attach: function (context) {
    $(context).find('div.quicktabs-ui-wrapper').once('quicktabs-ui-wrapper').each(function() {
      var id = $(this).attr('id');
      var qtKey = 'qt_' + this.id.substring(this.id.indexOf('-') +1);
      $(this).tabs({ active: drupalSettings.quicktabs[qtKey].default_tab });
    });
  }
}

})(jQuery, Drupal, drupalSettings);
