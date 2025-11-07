(function ($, Drupal, once) {
  Drupal.behaviors.uploadWithoutValidationConfirmationModal = {
    attach: function (context, settings) {
      // Save button handler
      once('proceed-without-validation-modal-button', '#proceed-without-validation-modal-button', context).forEach(function (el) {
        $(el).on('click', function (e) {
          e.preventDefault();
          $('#upload-without-validation-submit').trigger('click');
        });
      });
      // Cancel button handler
      once('cancel-without-validation-modal-button', '#cancel-without-validation-modal-button', context).forEach(function (el) {
        $(el).on('click', function (e) {
          e.preventDefault();
          $(el).closest('.ui-dialog-content').dialog('close');
        });
      });
    }
  };
})(jQuery, Drupal, once);