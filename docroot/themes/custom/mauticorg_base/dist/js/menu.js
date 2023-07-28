(function ($) {
    $(".mobile--hamburger .fa-bars").click(function (event) {
        event.preventDefault();
        $(".header--menu").toggleClass("show--menu");
    });
    $(".page__header .menu-item--expanded > a").click(function (event) {
        event.preventDefault();
        $(this)
            .closest(".menu-item--expanded")
            .siblings()
            .removeClass("show");
        $(this)
            .closest(".menu-item--expanded")
            .toggleClass("show");
    });
    function checkOffset() {
        if ($(".header--container").offset().top + $(".header--container").height() >=
            106) {
            $(".page__header").addClass("page__header-opacity");
        }
        if ($(document).scrollTop() < 106) {
            $(".page__header").removeClass("page__header-opacity"); // restore on scroll down
        }
    }
    $(document).scroll(function () {
        checkOffset();
    });
    $(".block-views-exposed-filter-blockacquia-search-page-1 h2").click(function () {
        $(".block-views-exposed-filter-blockacquia-search-page-1").toggleClass("search--visible");
        $(".block-views-exposed-filter-blockacquia-search-page-1 .form-autocomplete").focus();
    });
})(jQuery);
