(function ($) {
    $(".back__to-top--button").click(function () {
        $("html, body").animate({ scrollTop: "0px" }, 1000);
    });
})(jQuery);
