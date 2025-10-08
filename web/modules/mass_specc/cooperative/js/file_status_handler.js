/**
 * @file
 * Handles AJAX requests for updating file status (Approve/Reject).
 */
((Drupal, once) => {
  Drupal.behaviors.cooperativeFileStatusHandler = {
    attach: (context, settings) => {

      once('file-status-ajax', '.file-action-link', context).forEach((el) => {
        el.addEventListener('click', (e) => {
          e.preventDefault();

          const link = el;
          const actionUrl = link.dataset.actionUrl;
          const nodeId = link.dataset.nodeId;

          if (!actionUrl || !nodeId) {
            console.error('Missing data attributes for AJAX action.');
            return;
          }

          link.disabled = true;

          fetch(actionUrl, {
            method: 'POST',
            headers: {
              'X-Drupal-Csrf-Token': settings.csrfToken,
              'Content-Type': 'application/json',
            },
          })
            .then((res) => res.json())
            .then((data) => {
              if (data.status === 'success') {
                const td = link.closest('.file-actions');
                td.innerHTML = `<span class="file-upload-status">${data.new_status}</span>`;
              } else {
                // Drupal.Message.add(`Error: ${data.message}`, 'error');
                console.error(data.message);
                link.disabled = false;
              }
            })
            .catch((err) => {
              console.error(err);
              // Drupal.Message.add('An AJAX error occurred: ' + err, 'error');
              link.disabled = false;
            });
        });
      });
    },
  };
})(Drupal, once);
