((Drupal, once, $) => {
  'use strict';

  Drupal.behaviors.dashboardAutosubmit = {
    attach: (context) => {
      once('dashboard-autosubmit', 'input[name="search"]', context).forEach((el) => {
        
        const $searchField = $(el);

        const delayedSubmit = Drupal.debounce(() => {
          const searchValue = $searchField.val().trim();
          
          if (searchValue.length === 0 || searchValue.length >= 3) {
            $searchField.closest('form').submit();
          }
        }, 300);

        $searchField.on('keyup', delayedSubmit);
      });
    }
  };

})(Drupal, once, jQuery);
