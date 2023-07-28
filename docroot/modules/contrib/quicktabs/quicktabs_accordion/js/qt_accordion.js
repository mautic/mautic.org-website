(function ($, Drupal, drupalSettings) {
  
'use strict';

Drupal.behaviors.qt_accordion = {
  attach: function (context, settings) {
    $(context).find('div.quicktabs-accordion').once('quicktabs-accordion').each(function() {
      var id = $(this).attr('id');
      var qtKey = 'qt_' + this.id.substring(this.id.indexOf('-') +1);
      var options = drupalSettings.quicktabs[qtKey].options;
      $(this).accordion(options);
    });
  }
}

})(jQuery, Drupal, drupalSettings);
