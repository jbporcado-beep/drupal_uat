(function ($, Drupal, once) {
  Drupal.behaviors.numericOnly = {
    attach: function (context, settings) {
      once("numericOnly", ".js-numeric-only", context).forEach(function (el) {
        $(el).on("keypress", function (e) {
          if ([8, 46, 37, 39, 9].includes(e.which)) return;
          if (e.which < 48 || e.which > 57) e.preventDefault();
        });
      });
    },
  };
})(jQuery, Drupal, once);
