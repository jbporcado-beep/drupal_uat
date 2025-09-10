(function ($, Drupal, once) {
  Drupal.behaviors.userDropdown = {
    attach: function (context, settings) {
      once("user-dropdown", ".user-dropdown-toggle", context).forEach(function (
        el
      ) {
        $(el).on("click", function (e) {
          e.preventDefault();
          const $dropdown = $(this).closest(".user-dropdown");
          $dropdown.toggleClass("open");
        });
      });

      $(document).on("click", function (e) {
        if (!$(e.target).closest(".user-dropdown").length) {
          $(".user-dropdown").removeClass("open");
        }
      });
    },
  };
})(jQuery, Drupal, once);
