(function ($, Drupal) {
  Drupal.behaviors.deactivateConfirm = {
    attach: function (context, settings) {
      $(".btn-deactivate-coop", context)
        .once("deactivate-confirm")
        .on("click", function (e) {
          if (
            !confirm(
              "Are you sure you want to deactivate this cooperative? This action cannot be undone."
            )
          ) {
            e.preventDefault();
          }
        });
    },
  };
})(jQuery, Drupal);
