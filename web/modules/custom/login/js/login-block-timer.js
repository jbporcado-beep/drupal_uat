(function ($, Drupal) {
    Drupal.behaviors.loginBlockTimer = {
        attach: function (context) {
            var el = document.getElementById('login-block-timer');
            if (!el) return;
            var remaining = parseInt(el.getAttribute('data-remaining'), 10);
            if (isNaN(remaining) || remaining <= 0) {
                if (el.parentNode) el.parentNode.removeChild(el);
                return;
            }
            var span = document.getElementById('login-block-seconds');
            var submit = document.querySelector('form#user-login-form button[type="submit"], form#user-login-form input[type="submit"]');
            var interval = setInterval(function () {
                if (remaining <= 0) {
                    clearInterval(interval);
                    if (el.parentNode) el.parentNode.removeChild(el);
                    if (submit) submit.removeAttribute('disabled');
                } else {
                    remaining--;
                    if (span) span.textContent = remaining;
                }
            }, 1000);
        }
    };
})(jQuery, Drupal);
