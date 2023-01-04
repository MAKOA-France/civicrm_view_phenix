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

      //Page geographique
      $('.pager__item').once('leaflet').on('click', function() {
        let cityValue = $('[name="city"]').val();
        if (cityValue == '') {
          $('[name="city"]').val('none')
        }
      });

      //Page location
      if (queryString && !queryString.includes('materiel_location_new')) {
        let matches = queryString.match(/materiel_location=[0-9]+/);
            let defaultValueFilterSubFamily = matches[0].split('=')[1];

            let allValuesJson = $('.all-data-subfamily').attr('data-subfamily');
            let allValues = JSON.parse(allValuesJson);
            let valueLabel = allValues[defaultValueFilterSubFamily];
            jQuery('.filter-by-subfamily').val(valueLabel + '(' + defaultValueFilterSubFamily + ')' );
      }

    }
  }


})(jQuery, Drupal, drupalSettings);
