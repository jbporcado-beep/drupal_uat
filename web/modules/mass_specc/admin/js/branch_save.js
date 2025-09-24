(function ($, Drupal, once) {
  Drupal.behaviors.branchSave = {
    attach: function (context) {
      const $form = $(
        once("branchSaveForm", "#mass-specc-cooperative-edit-form", context)
      );
      const $btn = $(once("branchSaveBtn", "#save-branches-btn", context));

      if (!$form.length || !$btn.length) {
        return;
      }

      // Initially keep button disabled
      $btn.prop("disabled", true);

      // Enable button if form changes
      $form.on("input change", ":input", function () {
        $btn.prop("disabled", false);
      });

      // Optional: re-disable after submit
      $form.on("submit", function () {
        $btn.prop("disabled", true);
      });
    },
  };
})(jQuery, Drupal, once);
