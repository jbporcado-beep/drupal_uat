(function ($, Drupal) {
  Drupal.behaviors.coopSaveButton = {
    attach: function (context, settings) {
      let saveBtn = $(".coop-save-btn", context);
      let formChanged = false;

      $("form#mass_specc_cooperative_edit_form :input", context).on(
        "change keyup",
        function () {
          formChanged = true;
          saveBtn.prop("disabled", false);
        }
      );

      if (!formChanged) {
        saveBtn.prop("disabled", true);
      }
    },
  };
})(jQuery, Drupal);
