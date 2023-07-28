(function ($, Drupal, drupalSettings) {

'use strict';

Drupal.quicktabs = Drupal.quicktabs || {};

Drupal.quicktabs.getQTName = function (el) {
  return el.attr('id').substring(el.attr('id').indexOf('-') + 1);
}

Drupal.behaviors.quicktabs = {
  attach: function (context, settings) {
    $(context).find('div.quicktabs-wrapper').once('quicktabs-wrapper').each(function() {
      var el = $(this);
      Drupal.quicktabs.prepare(el);
    });
  }
}

// Setting up the inital behaviours
Drupal.quicktabs.prepare = function(el) {
  // el.id format: "quicktabs-$name"
  var qt_name = Drupal.quicktabs.getQTName(el);
  var $ul = $(el).find('ul.quicktabs-tabs:first');
  $ul.find('li a').each(function(i, element){
    element.myTabIndex = i;
    element.qt_name = qt_name;
    var tab = new Drupal.quicktabs.tab(element);
    var parent_li = $(element).parents('li').get(0);

    $(element).bind('click', {tab: tab}, Drupal.quicktabs.clickHandler);
    $(element).bind('keydown', {myTabIndex: i}, Drupal.quicktabs.keyDownHandler);
  });
}

Drupal.quicktabs.clickHandler = function(event) {
  var tab = event.data.tab;
  var element = this;
  // Set clicked tab to active.
  // Flip the aria-selected attribute.
  // The tabindex takes inactive tabs out of the tab pool,
  // they can be accessed by keyboard navigation.
  // This is a recommendation of the WAI-ARIA example:
  // https://www.w3.org/TR/wai-aria-practices-1.1/examples/tabs/tabs-2/tabs.html
  $(this).parents('li').siblings().removeClass('active');
  $(this).parents('li').siblings().attr('aria-selected', 'false');
  $(this).parents('li').siblings().attr('tabindex', '-1');
  $(this).parents('li').siblings().find('a').attr('tabindex', '-1');
  $(this).parents('li').addClass('active');
  $(this).parents('li').attr('aria-selected', 'true');
  $(this).attr('tabindex', '0');

  if ($(this).hasClass('use-ajax')) {
    $(this).addClass('quicktabs-loaded');
  }

  // Hide all tabpages.
  tab.container.children().addClass('quicktabs-hide');

  if (!tab.tabpage.hasClass("quicktabs-tabpage")) {
    tab = new Drupal.quicktabs.tab(element);
  }

  tab.tabpage.removeClass('quicktabs-hide');
  return false;
}

Drupal.quicktabs.keyDownHandler = function(event) {
  var tabIndex = event.data.myTabIndex;

  // This element should be a link element inside an
  // unordered list of tabs. Get all links in the list.
  var tabs = $(this).parent('li').parent("ul").find("li a");

  // Trigger the click and focus events for the individual tabs.
    switch (event.key) {
        case 'ArrowLeft':
        case 'ArrowUp':
            event.preventDefault();
            if (tabIndex <= 0) {
                tabs[tabs.length - 1].click();
                tabs[tabs.length - 1].focus();
            } else {
                tabs[tabIndex - 1].click();
                tabs[tabIndex - 1].focus();
            }
            break;
        case'ArrowRight':
        case'ArrowDown':
            event.preventDefault();
            if (tabIndex >= tabs.length - 1) {
                tabs[0].click();
                tabs[0].focus();
            } else {
                tabs[tabIndex + 1].click();
                tabs[tabIndex + 1].focus();
            }
    }
}

// Constructor for an individual tab
Drupal.quicktabs.tab = function (el) {
  this.element = el;
  this.tabIndex = el.myTabIndex;
  var qtKey = 'qt_' + el.qt_name;
  var i = 0;
  for (var i = 0; i < drupalSettings.quicktabs[qtKey].tabs.length; i++) {
    if (i == this.tabIndex) {
      this.tabObj = drupalSettings.quicktabs[qtKey].tabs[i];
      this.tabKey = typeof el.dataset.quicktabsTabIndex !== 'undefined' ? el.dataset.quicktabsTabIndex : i;
    }
  }
  this.tabpage_id = 'quicktabs-tabpage-' + el.qt_name + '-' + this.tabKey;
  this.container = $('#quicktabs-container-' + el.qt_name);
  this.tabpage = this.container.find('#' + this.tabpage_id);
}

// Enable tab memory.
// Relies on the jQuery Cookie plugin.
// @see http://plugins.jquery.com/cookie
  Drupal.behaviors.quicktabsmemory = {
    attach: function (context, settings) {
      // The .each() is in case there is more than one quicktab on a page.
      $(context).find('div.quicktabs-wrapper').once('form-group').each(function () {
        var el = $(this);

        // el.id format: "quicktabs-$name"
        var qt_name = Drupal.quicktabs.getQTName(el);
        var $ul = $(el).find('ul.quicktabs-tabs:first');

        // Default cookie options.
        var cookieOptions = {path: '/'};
        var cookieName = 'Drupal-quicktabs-active-tab-id-' + qt_name;

        $ul.find('li a').each(function (i, element) {
          var $link = $(element);
          $link.data('myTabIndex', i);

          // Click the tab ID if a cookie exists.
          var $cookieValue = $.cookie(cookieName);
          if ($cookieValue !== '' && $link.data('myTabIndex') == $cookieValue) {
            $(element).click();
          }

          // Set the click handler for all tabs, this updates the cookie on
          // every tab click.
          $link.on('click', function () {
            var $linkdata = $(this);
            var tabIndex = $linkdata.data('myTabIndex');
            $.cookie(cookieName, tabIndex, cookieOptions);
          });
        });
      });
    }
  };


if (Drupal.Ajax) {

  /**
   * Handle an event that triggers an AJAX response.
   *
   * We unfortunately need to override this function, which originally comes
   * from misc/ajax.js, in order to be able to cache loaded tabs, i.e., once a
   * tab content has loaded it should not need to be loaded again.
   *
   * I have removed all comments that were in the original core function, so
   * that the only comments inside this function relate to the Quicktabs
   * modification of it.
   */
  Drupal.Ajax.prototype.eventResponse = function (element, event) {
    event.preventDefault();
    event.stopPropagation();

    // Create a synonym for this to reduce code confusion.
    var ajax = this;

    // Do not perform another Ajax command if one is already in progress.
    if (ajax.ajaxing) {
      return;
    }

    try {
      if (ajax.$form) {
        if (ajax.setClick) {
          element.form.clk = element;
        }

        ajax.$form.ajaxSubmit(ajax.options);
      }
      else {
        if (!$(element).hasClass('quicktabs-loaded')) {
          ajax.beforeSerialize(ajax.element, ajax.options);
          $.ajax(ajax.options);
        }
      }
    }
    catch (e) {
      ajax.ajaxing = false;
      window.alert('An error occurred while attempting to process ' + ajax.options.url + ': ' + e.message);
    }
  };
}

})(jQuery, Drupal, drupalSettings);
