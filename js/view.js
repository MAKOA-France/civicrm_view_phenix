(function($, Drupal, drupalSettings) {

  const RED_MARKER_PATH = 'https://dlr-guide.dev.makoa.net/sites/dlr-guide.dev.makoa.net/modules/contrib_0/civicrm_view_phenix/img/red_marker.webp';
  Drupal.behaviors.customView = {
    attach: function(context, settings) {

      var queryString = window.location.search;
      if (queryString.includes('organization_name')) {
        let matches = queryString.match(/organization_name=[0-9]+/);
        let defaultValueFilterByName = matches[0].split('=')[1];
        $('.filter_by_name').val(defaultValueFilterByName);
      }

      $('.exposed-filter-location-btn').once('customView').on('click', function () {
        let organizationNameVal = $('.filter_by_name').val();
        let filterBySubFamilyVal = $('.filter-by-subfamily').val();
        if (organizationNameVal == '') {
          $('.filter_by_name').removeAttr('name');
        }
      });

    }
  }


})(jQuery, Drupal, drupalSettings);
