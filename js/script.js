(function($, Drupal, drupalSettings) {

  Drupal.behaviors.leaflet = {
    attach: function(context, settings) {

    var queryString = window.location.search;

    //Inverser l'affichage de la carte et la video  (fiche entreprise)
    jQuery('.video-content-fiche').insertBefore('.testation2 .views-element-container');


    //Page location filter by materiel location alter link
    jQuery('ul li a[name*="materiel_location_new"]').on('mouseover', function() {
      let currHref = jQuery(this).attr('href');
      let matchedUrl = currHref.match(/&materiel_location=[0-9]+/);
      if (matchedUrl) {
         let newHref = currHref.replace(matchedUrl[0], '');
          jQuery(this).attr('href', newHref);
      }
    })
    //Page geographique Set default value filter by name

    let matched = queryString.match(/organization_name=[a-z0-9\-]+/ig);
    let filterByCompanyNameDefaultValue = '';
    if (matched) {
      filterByCompanyNameDefaultValue = matched[0].split('organization_name=')[1];
    }

    //page geographique
    jQuery('[value="Rechercher"]').once('leaflet').on('click', function() {
      let dep = $('[name="filter_by_deprtmt"]').val()
      if (dep == 'none') {
        $('[name="filter_by_deprtmt"]').removeAttr('name');
      }
      if($('[name="organization_name"]').val() == '') {
        $('[name="organization_name"]').removeAttr('name')
      }
      if($('[name="state_province_id"]').val() == 'All') {
        $('[name="state_province_id"]').removeAttr('name')
      }
      if($('[name="postal_code"]').val() == '') {
        $('[name="postal_code"]').removeAttr('name')
      }

    })

    $(document).on('ajaxSuccess',function( event, xhr, settings){

    });

    //By default selected field select (hidden) filter by marque
    let matchBrandFilter = queryString.match(/marque_nom=[0-9]+/ig);
    if(matchBrandFilter) {
      let brandLabel = matchBrandFilter[0].split('marque_nom_copy=')[1];
      $('[name="marque_nom"] option').filter(function() {
        //may want to use $.trim in here
        return $(this).text() == brandLabel;
      }).prop('selected', true);
    }

    $('.filter-by-brand').once('leaflet').on('click', function(){
      let brandValue = $('.marque-nom-copy').val();
      if (brandValue) {

        $.ajax({
          url: '/annuaire/occasion/setdefaultvalue',
          type: "get",
          data: {idMarque: brandValue, id:$('.marque-nom-copy').val()},
          success: (successResult, val, ee) => {
          if (successResult != 'none') {
            //check if url already filter by occas materiel
            if (!queryString) {
              location.href = '?marque_nom=' + successResult['id'];
            }else {
              if (queryString.includes('marque_nom')) {
                let matchedUrl = queryString.match(/marque_nom=[0-9]+/);
                if (matchedUrl) {
                  let newHref = queryString.replace(matchedUrl[0], 'marque_nom=' + successResult['id']);
                  location.href = newHref;
                }
              }else {
                location.href = queryString + '&marque_nom=' + successResult['id'];
              }
            }
          }

        },
        error: function(error) {
          console.log(error, 'ERROR')
        }
      });
    }
    });

    //Filter by department (ajout default value by variable get)
    var depId = 'none';
    if (queryString.indexOf('filter_by_deprtmt')) {
        let matched =  queryString.match(/filter_by_deprtmt=[0-9]+/g);
        if(matched) {
          depId = matched[0].split('filter_by_deprtmt=')[1];
        }
    }

    $(window).on('load',function() {

      jQuery('.filter_by_dprtmt').val(depId);
      let brandValue = $('.marque-nom-copy').val();
      $.ajax({
        url: '/annuaire/occasion/setdefaultvalue',
        type: "get",
        data: {get_id: jQuery('[name="marque_nom"]').val()},
        success: (successResult, val, ee) => {
          if (successResult > 1) {
            $('.marque-nom-copy').val('');
          }else {
            $('.marque-nom-copy').val(successResult);
          }

        },
        error: function(error) {
          console.log(error, 'ERROR')
        }
      });
    });


    $('.toggle-sub-materiel').hide();

    //todo location here
    var getLocation = 'materiel_location_new';
    if(queryString.indexOf(getLocation) > 0) {
      let matchedFilterLocation = queryString.match(/materiel_location_new=[0-9]+/g);
      if(matchedFilterLocation) {
        let locationId = matchedFilterLocation[0].split('materiel_location_new=')[1];
        jQuery('[name="materiel_location_new['+locationId+']"]').next('ul').show();
      }
    }

    jQuery('[name="materiel_location_new[All]"]').attr('href', '?materiel_location_new=All'); // activité recherche filtre


    if (jQuery('.ul-parent-materiel-location').attr('data-all-ul')) {

      var allDatas = JSON.parse(jQuery('.ul-parent-materiel-location').attr('data-all-ul'));
      jQuery('.ul-parent-materiel-location ul li').each(function (idx, elm) {
        if (jQuery(elm).find('.toggle-sub-materiel').length < 1) {
          jQuery(elm).append(allDatas[jQuery(elm).find('a').attr('name')]);
        }
      });

      if (window.location.search.indexOf('materiel_location') > 0) {

        //show default selected materiel
        //todo here jQuery('ul').has('li[data-val=' + window.location.search.split('materiel_location=')[1] + ']').show();
        //stylized selected filter
        jQuery('li a[href="' + window.location.search +'"]').css({'textDecoration' : 'underline', 'fontWeight': 'bold'});
      }
    }

    const regexRemoveLastUpsilon = /\&$/;
    var removeLastUps = queryString.replace(regexRemoveLastUpsilon, '');
    const regexOrgName = /organization_name=([a-z0-9éèê ]+&|&|[a-zA-Z0-9]+)/;
    const regexWordWithourUsp = /[a-zA-Z]+/;


    var filterAdress = '';
    $('.btn-fitler-address').once('leaflet').on('click', function(e) {
      var value = $('.filter_by_address').val();
      var dpt = $('.filter_by_dprtmt').val();
      if (value) {
        location.href = '?address='+ value;
      }
      if (dpt) {
        if (dpt != 'none') {
          location.href = '?departement='+ dpt;
        }else {
          let currenturl = window.location.search;
          //location.href = currenturl.replace(/\?departement=[0-9]+/, "");
        }
      }
    });

     if (queryString.match('organization_name')) {
      let matches  = queryString.match(regexOrgName);
      if (matches) {
        let lastValue =  matches[1].match(regexWordWithourUsp);
        $('.filter_by_name').val(lastValue)
      }
    }

    function add_default_value_to_field_filter_by_company (matches) {
      let organizationNameVal =  matches[0].split('=');
          let lastValue =  organizationNameVal[1].substring(0, organizationNameVal[1].length - 1);
          $('.filter_by_name').val(lastValue)
    }

      //Filter by company name or acronym
    $('.filter_by_name').once('leaflet').on('change', function(e) {
       let organisationName = $(this).val();
       //add default value to the field search by company

      let matchFilterByLetter = removeLastUps.match(/letter=[a-zA-Z]/);

      if (matchFilterByLetter) {
        if(organisationName) {
          if (queryString.match('organization_name')) {
            if (removeLastUps.match('letter=')) {
              let newurlWithLetter = removeLastUps.replace(regexOrgName, 'organization_name=' + organisationName + '&' );
              let newQueryStringWithLetter = newurlWithLetter.replace(/letter=[a-zA-Z]/, matchFilterByLetter[0]+ '&');
               location.href = newurlWithLetter;
            }else {
              let newurlWithLetter = removeLastUps.replace(regexOrgName, 'organization_name=' + organisationName+ '&' +matchFilterByLetter[0] );
               location.href = newurlWithLetter;
            }
          }else {
            let newQueryString = removeLastUps.replace(/letter=[a-zA-Z]/, matchFilterByLetter[0]);
            location.href = newQueryString + '&organization_name=' + organisationName
          }
        }else {
          let queryStringWithoutOrgName = removeLastUps.replace(regexOrgName, '');
          if (queryStringWithoutOrgName.match('letter=')) {
            location.href = queryStringWithoutOrgName
          }else {
            location.href = queryStringWithoutOrgName + '&' + matchFilterByLetter[0]
          }
        }
      }else {
        if (!removeLastUps) {
          if (organisationName) {
            location.href = '?&organization_name=' + organisationName
          }
        }else {

          if (organisationName) {
            let newurl = removeLastUps.replace(regexOrgName, 'organization_name=' + organisationName + '&');
            console.log(newurl);
            if (queryString.includes('organization_name')) {
              location.href = newurl;
            }else {
              location.href = queryString + '&organization_name=' + organisationName
            }
          }else{
            let newurl = removeLastUps.replace(regexOrgName, '');
            newurl = newurl.replace(regexRemoveLastUpsilon, '');
            location.href = newurl;
          }
        }
      }

    });

      $.each(settings.leaflet, function(m, data) {
        $('#' + data.mapid, context).each(function() {
          let $container = $(this);
          let mapid = data.mapid;

          // If the attached context contains any leaflet maps, make sure we have a Drupal.leaflet_widget object.
          if ($container.data('leaflet') === undefined) {
            $container.data('leaflet', new Drupal.Leaflet(L.DomUtil.get(mapid), mapid, data.map));
            if (data.features.length > 0) {

              // Initialize the Drupal.Leaflet.[data.mapid] object,
              // for possible external interaction.
              Drupal.Leaflet[mapid].markers = {};
              Drupal.Leaflet[mapid].features = {};

              // Add Leaflet Map Features.
              $container.data('leaflet').add_features(mapid, data.features, true);
            }

            // Add the leaflet map to our settings object to make it accessible.
            // @NOTE: This is used by the Leaflet Widget module.
            data.lMap = $container.data('leaflet').lMap;

            // Set map position features.
            $container.data('leaflet').fitbounds(mapid);
          }

          else {
            // If we already had a map instance, add new features.
            // @TODO Does this work? Needs testing.
            if (data.features !== undefined) {
              $container.data('leaflet').add_features(mapid, data.features);
            }
          }
          // After having initialized the Leaflet Map and added features,
          // allow other modules to get access to it via trigger.
          // NOTE: don't change this trigger arguments print, for back porting
          // compatibility.
          $(document).trigger('leafletMapInit', [data.map, data.lMap, mapid]);
          // (Keep also the pre-existing event for back port compatibility)
         // $(document).trigger('leaflet.map', [data.map, data.lMap, mapid]);
        });
      });


      //Put the total of company on the top
      jQuery('.custom-bloc-a-to-z').appendTo(jQuery('.custom-bloc-a-to-z').closest('header')[0]);
    }
  };

  // Once the Leaflet Map is loaded with its features.
  jQuery(document).on('leaflet.map', function(e, settings, lMap, mapid) {
    // Executes once per mapid.
  //  once('leaflet_map_event_' + mapid, 'html').forEach(function() {
      // Set the start center and the start zoom, and initialize the reset_map control.
      // Attach leaflet ajax popup listeners.
      Drupal.Leaflet[mapid].lMap.on('tooltipopen', function(e) {
        let element = e.popup._contentNode;
        let content = $('[data-leaflet-ajax-popup]', element);
        //if (content.length) {
          let elemHtml = $(element).find('article').attr('data-quickedit-entity-id');
          let id = elemHtml.split('civicrm_address/');
          id = id[1];
          let url = '/annuaire/geographique/details/' + id;
          Drupal.ajax({url: url}).execute().done(function () {

            // Copy the html we received via AJAX to the popup, so we won't
            // have to make another AJAX call (#see 3258780).
            //Call update() so Leaflet refreshes the map, panning it if
            // necessary to bring the full popup into view (#see 3258780).
            e.popup.update();

            // Attach drupal behaviors on new content.
            Drupal.attachBehaviors(element, drupalSettings);
          });
        //}
      });
    //});
  });



  Drupal.Leaflet.prototype.create_feature_group = function() {
    return new L.LayerGroup();
  };


/*   Drupal.Leaflet.prototype.create_divicon = function (options) {
    let html_class = options.html_class || '';
    let icon = new L.DivIcon({html: options.html, className: html_class});

    // override applicable marker defaults
    if (options.iconSize) {
      icon.options.iconSize = new L.Point(parseInt(options.iconSize.x, 10), parseInt(options.iconSize.y, 10));
    }
    if (options.iconAnchor && options.iconAnchor.x && options.iconAnchor.y) {
      icon.options.iconAnchor = new L.Point(parseInt(options.iconAnchor.x), parseInt(options.iconAnchor.y));
    }
    if (options.popupAnchor && !isNaN(options.popupAnchor.x) && !isNaN(options.popupAnchor.y)) {
      icon.options.popupAnchor = new L.Point(parseInt(options.popupAnchor.x), parseInt(options.popupAnchor.y));
    }

    return icon;
  }; */

  Drupal.Leaflet.prototype.create_point = function(marker) {
    let self = this;
    let latLng = new L.LatLng(marker.lat, marker.lon);
    self.bounds.push(latLng);
    let lMarker;
    let marker_title = marker.label ? marker.label.replace(/<[^>]*>/g, '').trim() : '';

    //filter marker display here by contactId if in array whitelist
   // if ((whiteListCompany && (jQuery.inArray(marker.entity_id, whiteListCompany) !== -1)) ) {

      let markerId = marker['entity_id'];

          let options = {
            title: markerId,
            className: marker.className || '',
            alt: 'marker-title-guide',
            data_guide_detail_id: marker['entity_id'],
            dataGuideDetailSelector: 'selector',
          };

      lMarker = new L.Marker(latLng, options);

      lMarker.on('preclick', function(e){

        var hrefUrl = window.location.href
        let matchId = hrefUrl.match(/details\/[0-9]+/);
        if (matchId) {
          matchId = matchId[0].split('/')[1];
        }

        lMarker.bindPopup('').openPopup();
        let id = this.options.title;
        let url = '/annuaire/geographique/details/' + matchId //todo

        $.ajax({
          url: url,
          type: "POST",
          data: {idAddress: id},
          success: (successResult, val, ee) => {
            //this._popup.setContent('')
            lMarker.bindPopup(successResult).openPopup();
          },
          error: function(error) {
            console.log(error, 'ERROR')
          }
        });
      });

      //marker on click event
      lMarker.on('click', function(e){
        //do more stuff here
      });

    if (marker.icon) {
      if (marker.icon.iconType && marker.icon.iconType === 'html' && marker.icon.html) {
        let icon = self.create_divicon(marker.icon);
        lMarker.setIcon(icon);
      }
      else if (marker.icon.iconType && marker.icon.iconType === 'circle_marker') {
        try {
          options = marker.icon.options ? JSON.parse(marker.icon.options) : {};
          options.radius = options.radius ? parseInt(options['radius']) : 10;
        }
        catch (e) {
          options = {};
        }
        lMarker = new L.CircleMarker(latLng, options);
      }
      else if (marker.icon.iconUrl) {
        marker.icon.iconSize = marker.icon.iconSize || {};
        marker.icon.iconSize.x = marker.icon.iconSize.x || this.naturalWidth;
        marker.icon.iconSize.y = marker.icon.iconSize.y || this.naturalHeight;
        if (marker.icon.shadowUrl) {
          marker.icon.shadowSize = marker.icon.shadowSize || {};
          marker.icon.shadowSize.x = marker.icon.shadowSize.x || this.naturalWidth;
          marker.icon.shadowSize.y = marker.icon.shadowSize.y || this.naturalHeight;
        }
        let icon = self.create_icon(marker.icon);
        lMarker.setIcon(icon);
      }
    }

    return lMarker;
 // }//closing bracket filter
  };

/*   Drupal.Leaflet.prototype.create_linestring = function(polyline) {
    let self = this;
    let latlngs = [];
    for (let i = 0; i < polyline.points.length; i++) {
      let latlng = new L.LatLng(polyline.points[i].lat, polyline.points[i].lon);
      latlngs.push(latlng);
      self.bounds.push(latlng);
    }
    return new L.Polyline(latlngs);
  };

  Drupal.Leaflet.prototype.create_collection = function(collection) {
    let self = this;
    let layers = new L.featureGroup();
    for (let x = 0; x < collection.component.length; x++) {
      layers.addLayer(self.create_feature(collection.component[x]));
    }
    return layers;
  };

  Drupal.Leaflet.prototype.create_polygon = function(polygon) {
    let self = this;
    let latlngs = [];
    for (let i = 0; i < polygon.points.length; i++) {
      let latlng = new L.LatLng(polygon.points[i].lat, polygon.points[i].lon);
      latlngs.push(latlng);
      self.bounds.push(latlng);
    }
    return new L.Polygon(latlngs);
  };

  Drupal.Leaflet.prototype.create_multipolygon = function(multipolygon) {
    let self = this;
    let polygons = [];
    for (let x = 0; x < multipolygon.component.length; x++) {
      let latlngs = [];
      let polygon = multipolygon.component[x];
      for (let i = 0; i < polygon.points.length; i++) {
        let latlng = new L.LatLng(polygon.points[i].lat, polygon.points[i].lon);
        latlngs.push(latlng);
        self.bounds.push(latlng);
      }
      polygons.push(latlngs);
    }
    return new L.Polygon(polygons);
  };

  Drupal.Leaflet.prototype.create_multipoly = function(multipoly) {
    let self = this;
    let polygons = [];
    for (let x = 0; x < multipoly.component.length; x++) {
      let latlngs = [];
      let polygon = multipoly.component[x];
      for (let i = 0; i < polygon.points.length; i++) {
        let latlng = new L.LatLng(polygon.points[i].lat, polygon.points[i].lon);
        latlngs.push(latlng);
        self.bounds.push(latlng);
      }
      polygons.push(latlngs);
    }
    if (multipoly.multipolyline) {
      return new L.polyline(polygons);
    }
    else {
      return new L.polygon(polygons);
    }
  };

  Drupal.Leaflet.prototype.create_json = function(json, events) {
    let lJSON = new L.GeoJSON();

    lJSON.options.onEachFeature = function(feature, layer) {
      for (let layer_id in layer._layers) {
        for (let i in layer._layers[layer_id]._latlngs) {
          Drupal.Leaflet.bounds.push(layer._layers[layer_id]._latlngs[i]);
        }
      }
      if (feature.properties.style) {
        layer.setStyle(feature.properties.style);
      }
      if (feature.properties.leaflet_id) {
        layer._leaflet_id = feature.properties.leaflet_id;
      }
      if (feature.properties.popup) {
        console.log(feature.properties.popup)
        layer.bindPopup(feature.properties.popup);
      }
      for (e in events) {
        layerParam = {};
        layerParam[e] = eval(events[e]);
        layer.on(layerParam);
      }
    };

    lJSON.addData(json);
    return lJSON;
  };
*/
  // Set Map initial map position and Zoom.  Different scenarios:
  //  1)  Force the initial map center and zoom to values provided by input settings
  //  2)  Fit multiple features onto map using Leaflet's fitBounds method
  //  3)  Fit a single polygon onto map using Leaflet's fitBounds method
  //  4)  Display a single marker using the specified zoom
  //  5)  Adjust the initial zoom using zoomFiner, if specified
  //  6)  Cater for a map with no features (use input settings for Zoom and Center, if supplied)
  //
  // @NOTE: This method used by Leaflet Markecluster module (don't remove/rename)
  Drupal.Leaflet.prototype.fitbounds = function(mapid) {
    let self = this;;

    let start_zoom = self.settings.zoom ? self.settings.zoom : 12;
    // Note: self.settings.center might not be defined in case of Leaflet widget and Automatically locate user current position.
    let start_center = self.settings.center ? new L.LatLng(self.settings.center.lat, self.settings.center.lon) : new L.LatLng(0,0);

    //  Check whether the Zoom and Center are to be forced to use the input settings
    if (self.settings.map_position_force) {
      //  Set the Zoom and Center to values provided by the input settings
      Drupal.Leaflet[mapid].lMap.setView(start_center, start_zoom);
    } else {
      if (self.bounds.length === 0) {
        //  No features - set the Zoom and Center to values provided by the input settings, if specified
        Drupal.Leaflet[mapid].lMap.setView(start_center, start_zoom);
      } else {
        //  Set the Zoom and Center by using the Leaflet fitBounds function
        let bounds = new L.LatLngBounds(self.bounds);
        Drupal.Leaflet[mapid].lMap.fitBounds(bounds);
        start_center = bounds.getCenter();
        start_zoom = Drupal.Leaflet[mapid].lMap.getBoundsZoom(bounds);

        if (self.bounds.length === 1) {
          //  Single marker - set zoom to input settings
          Drupal.Leaflet[mapid].lMap.setZoom(self.settings.zoom);
          start_zoom = self.settings.zoom;
        }
      }

      if (window.location.href.indexOf('annuaire/details') > 1) {
       // Drupal.Leaflet[mapid].lMap.setZoom(8);
      }
      //Page fiche entreprise
     if ($('.custom-view-embeded-map').length > 0 /*&& ($('.this-country-is-french').length > 0)*/) {

      let ficheEntreprise = new L.LatLng(self.settings.center.lat, self.settings.center.lon);
      //with min and max
      ficheEntreprise.lat = $('.views-field-url').attr('data-latitude')
      ficheEntreprise.lng = $('.views-field-url').attr('data-longitude')
      // Set the map start zoom and center.
      if ($('.views-field-url').attr('data-total-result') > 2) {
        Drupal.Leaflet[mapid].lMap.setView(ficheEntreprise, 5,5);  //todo center on france
      }else {
        Drupal.Leaflet[mapid].lMap.setView(ficheEntreprise, 7.6);  //todo center on france
      }
     }

      // In case of map initial position not forced, and zooFiner not null/neutral,
      // adapt the Map Zoom and the Start Zoom accordingly.
      if (self.settings.hasOwnProperty('zoomFiner') && parseInt(self.settings.zoomFiner)) {
        start_zoom += parseFloat(self.settings.zoomFiner);
        Drupal.Leaflet[mapid].lMap.setView(start_center, start_zoom);
      }
      // start_center.lat = 48.864716;
      // start_center.lng = 2.349014;
      if (window.location.href.indexOf('annuaire/details') < 1) {
        Drupal.Leaflet[mapid].lMap.setZoom(1);
      }


       Drupal.Leaflet[mapid].start_zoom = start_zoom;
      //  Drupal.Leaflet[mapid].lMap.setZoom(10);
       Drupal.Leaflet[mapid].start_center = start_center;
     }
    if ((jQuery('.page-annuaire-table-liste-géographique').length > 0) && (window.location.search.indexOf('organization_name') > 0)) {
      start_center.lat = 47,76620099445003;
      start_center.lng = 1,5858760671640175;
      // Set the map start zoom and center.
       Drupal.Leaflet[mapid].lMap.setView(start_center, 6);
    }

  };

  Drupal.Leaflet.prototype.map_reset = function(mapid) {
    Drupal.Leaflet[mapid].lMap.setView(Drupal.Leaflet[mapid].start_center, Drupal.Leaflet[mapid].start_zoom);
  };

  Drupal.Leaflet.prototype.map_reset_control = function(controlDiv, mapid) {
    let self = this;
    let reset_map_control_settings = drupalSettings.leaflet[mapid].map.settings.reset_map;
    let control = new L.Control({position: reset_map_control_settings.position});
    control.onAdd = function() {
      // Set CSS for the control border.
      let controlUI = L.DomUtil.create('div','resetzoom');
      controlUI.style.backgroundColor = '#D92026';
      controlUI.style.border = '2px solid #fff';
      controlUI.style.borderRadius = '3px';
      controlUI.style.boxShadow = '0 2px 6px rgba(0,0,0,.3)';
      controlUI.style.cursor = 'pointer';
      controlUI.style.margin = '6px';
      controlUI.style.textAlign = 'center';
      controlUI.title = Drupal.t('Click to reset the map to its initial state');
      controlUI.id = 'leaflet-map--' + mapid + '--reset-control';
      controlUI.disabled = true;
      controlDiv.appendChild(controlUI);

      // Set CSS for the control interior.
      let controlText = document.createElement('div');
      controlText.style.color = 'rgb(25,25,25)';
      controlText.style.fontSize = '1.1em';
      controlText.style.lineHeight = '28px';
      controlText.style.paddingLeft = '5px';
      controlText.style.paddingRight = '5px';
      controlText.innerHTML = Drupal.t('Reset Map');
      controlUI.appendChild(controlText);

      L.DomEvent
        .disableClickPropagation(controlUI)
        .addListener(controlUI, 'click', function() {
          self.map_reset(mapid);
        },controlUI);
      return controlUI;
    };
    return control;
  };

})(jQuery, Drupal, drupalSettings);


