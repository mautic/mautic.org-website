($ => {
  $(".sidebar--toggle").click(function() {
    $(".layout-sidebar").toggleClass("sidebar--visible");
    $(".layout-content").toggleClass("sidebar--shown");
  });
})(jQuery);
