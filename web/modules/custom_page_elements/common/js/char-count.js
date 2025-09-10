(function ($, Drupal, once) {
  Drupal.behaviors.charCount = {
    attach: function (context, settings) {
      once("char-count", ".js-char-count", context).forEach(function (el) {
        var $input = $(el);
        var max = parseInt($input.data("maxlength"), 10);
        var $counter = $input.parent().find(".char-counter");

        function updateCounter() {
          var len = $input.val().length;
          if (len >= max) {
            $counter.text("Max!");
          } else {
            $counter.text(len + "/" + max);
          }
        }

        $input.on("input", updateCounter);
        updateCounter();
      });
    },
  };
})(jQuery, Drupal, once);
