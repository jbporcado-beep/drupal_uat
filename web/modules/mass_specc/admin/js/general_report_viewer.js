(function (Drupal, once) {
    Drupal.behaviors.reportBuilder = {
        attach(context) {
            const selectAll = once('select-all', '.content-type-select-all', context);
            const fieldCheckboxes = once('fields', '.field-checkbox-selection', context);
            const downloadButtons = once('download-btn', '.form-submit', context);

            function updateButtonState() {
                const anyChecked = fieldCheckboxes.some(f => f.checked) || selectAll.some(f => f.checked);
                downloadButtons.forEach(btn => {
                    btn.disabled = !anyChecked;
                });
            }

            selectAll.forEach(cb => {
                cb.addEventListener('change', () => {
                    fieldCheckboxes.forEach(f => (f.checked = cb.checked));
                    updateButtonState();
                });
            });

            fieldCheckboxes.forEach(f => {
                f.addEventListener('change', () => {
                    const allChecked = fieldCheckboxes.every(f => f.checked);
                    selectAll.forEach(cb => (cb.checked = allChecked));
                    updateButtonState();
                });
            });
        },
    };
})(Drupal, once);