($ => {
  $(".back__to-top--button").click(function() {
    $("html, body").animate({ scrollTop: "0px" }, 1000);
  });

  $(".citation-button").click(function() {
    $(this)
      .siblings(".citation-modal")
      .addClass("active");
    $("body").addClass("modal-open");
  });

  $(".citation-modal__close").click(function() {
    $(this)
      .parent(".citation-modal__header")
      .parent(".modal-content")
      .parent(".citation-modal")
      .removeClass("active");
    $("body").removeClass("modal-open");
  });
})(jQuery);
