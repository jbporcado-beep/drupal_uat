(function (Drupal, once) {
  Drupal.behaviors.reportBuilder = {
    attach: function (context, settings) {
      once(
        "report-builder-select-all",
        ".content-type-select-all",
        context
      ).forEach(function (selectAll) {
        selectAll.addEventListener("change", function () {
          const type = this.getAttribute("data-type");
          const fieldCheckboxes = document.querySelectorAll(
            ".field-checkbox-" + type
          );

          fieldCheckboxes.forEach((cb) => {
            cb.checked = this.checked;
          });
        });
      });

      once(
        "report-builder-field-checkboxes",
        ".field-checkbox",
        context
      ).forEach(function (fieldCb) {
        fieldCb.addEventListener("change", function () {
          const classList = this.classList;
          const typeClass = Array.from(classList).find((cls) =>
            cls.startsWith("field-checkbox-")
          );
          if (!typeClass) return;

          const type = typeClass.replace("field-checkbox-", "");
          const all = document.querySelectorAll(".field-checkbox-" + type);
          const allChecked = Array.from(all).every((cb) => cb.checked);

          const selectAll = document.querySelector(
            ".content-type-select-all[data-type='" + type + "']"
          );
          if (selectAll) {
            selectAll.checked = allChecked;
          }
        });
      });
    },
  };
})(Drupal, once);
