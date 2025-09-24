(function ($, Drupal) {
  Drupal.behaviors.coopTabs = {
    attach: function (context, settings) {
      $(".coop-tab", context).on("click", function (e) {
        e.preventDefault();
        var target = $(this).data("target");

        $(".coop-tab").removeClass("active");
        $(this).addClass("active");

        $(".coop-section").removeClass("active");
        $(target).addClass("active");

        var tabValue = target === "#coop-general" ? "general" : "branches";
        $("#coop-active-tab").val(tabValue);

        if (tabValue === "branches") {
          $(".btn-deactivate-coop").hide();
        } else {
          $(".btn-deactivate-coop").show();
        }
      });

      var initialTab = $("#coop-active-tab").val();
      if (initialTab === "branches") {
        $(".btn-deactivate-coop").hide();
      } else {
        $(".btn-deactivate-coop").show();
      }
    },
  };
})(jQuery, Drupal);
