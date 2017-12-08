<?php

// Add Shortcode
function jakdojade() {


ob_start();
 ?>

<!DOCTYPE HTML>
<html>
  <head>
    <meta charset="utf-8"/>

    <title>placepicker</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <script src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
    <script src="http://netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <link href="http://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet"></link>
    <link href="http://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css" rel="stylesheet"></link>

    <script type="text/javascript"
      src="https://maps.googleapis.com/maps/api/js?sensor=true&libraries=places">
    </script>

   
    <script>
      ( function( $ ) {

  var pluginName = "placepicker";

  var defaults = {
    map: "",
    mapIconClass: "glyphicon glyphicon-globe",
    mapOptions: {
      zoom: 15
    },
    places: {
      icons: false
    },
    autoCompleteOptions: {
    },
    // callbacks
    placeChanged: null,
    location: null,
    preventSubmit: true
  };

  function PlacePicker(element, options) {

    var instance = this;

    var geocoder = null;

    var mapElement, map, marker;

    var service = null;

    var autocomplete;

    // stores the current place
    var _place = null;
    var _latLng = null;

    /**
     * Generates a piece of HTML that is used to replace the input element
     * with one that also contains a globe button.
     */
    function template() {
      var templateString =
        "<div class=\"input-group\">" +
          "<span class=\"input-group-btn\">" +
            "<button type=\"button\" data-toggle=\"collapse\" href=\"#" + options.mapContainerId + "\" class=\"btn btn-default\">" +
              "<span class=\"" + options.mapIconClass + "\"></span>" +
            "</button>" +
          "</span>" +
        "</div>";

      return templateString;
    }

    /**
     * Modifies the DOM to add a globe button that hides and shows a map
     * element.
     */
    function initDomElements() {
      if (!options.mapContainerId) {
        return;
      }

      // Find the index of our element under its parent
      var $element = $(element);
      var $parent = $element.parent();
      var index = $parent.children().index(element);

      // Replace the element with our template code
      $element.replaceWith(template());

      // Then inject back in the existing element. This retains all
      // attributes on the element.
      $parent.children().eq(index).append(element);
    }

    function codePlace(query) {

      if (!query) {
        return;
      }

      var request = {
        query: query
      };

      if (service) {
        service.textSearch(request, function (results, status) {
          if (status === google.maps.places.PlacesServiceStatus.OK) {
            for (var i = 0; i < results.length; i++) {
              setPlace(results[i]);
              return;
            }
          }
        });
      }
    }

    function codeLatLng(latlng) {
      geocoder.geocode({"latLng": latlng}, function(results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
          if (results[0]) {
            var place = results[0];
            setPlace(place, false);
          } else {
            // alert("No results found");
          }
        } else {
          // alert("Geocoder failed due to: " + status);
        }
      });
    }

    /**
     * Find and store the DOM element that holds the Google Map.
     * @return a boolean indicating whether an element was successfully
     * set.
     */
    function setMapElement() {

      mapElement = $(options.map).get(0);

      if (!mapElement) {
        if (options.mapContainerId) {
          mapElement = $("#" + options.mapContainerId + " .placepicker-map").get(0);
        }
      }

      return mapElement ? true : false;
    }

    function initMap() {

      if (!setMapElement()) {
        return;
      }

      map = new google.maps.Map(mapElement, options.mapOptions);

      autocomplete.bindTo("bounds", map);

      google.maps.event.addListener(map, "click", function(e) {
        var pos = e.latLng;
        marker.setPosition(pos);
        map.panTo(pos);
        element.blur();
        codeLatLng(pos);
      });

      marker = new google.maps.Marker({
        map: map
      });

      service = new google.maps.places.PlacesService(map);

      // When the map is made visible, if we have no location set then
      // attempt geolocation. The css() calls ensure that the map is
      // refreshed.
      $(mapElement).parent().on("show.bs.collapse", function(e) {
        $(e.target)
          .css("display", "block")
          // Fix map icons
          .find("img[src*='gstatic.com/'], img[src*='googleapis.com/']").css("max-width", "none");

        if (!element.value) {
          instance.geoLocation();
        } else {
          instance.resize();
        }

        $(e.target).css("display", "");
      });
    }

    function initAutoComplete() {
      autocomplete = new google.maps.places.Autocomplete(element, options.autoCompleteOptions);
      google.maps.event.addListener(autocomplete, "place_changed", function() {
        var place = autocomplete.getPlace();
        if (place.geometry) {
          setPlace(place);
        }
      });
    }

    function resizeHandler() {
      instance.resize.call(instance);
    }

    /**
     * We want to be able to select the first suggestion from Google when
     * the user presses return. This function applies a solution from Stack
     * Overflow, shimming the addEventListener function on the input element
     * so that when the Google code adds event listeners, they are actually
     * proxied through a custom handler. If a return keypress is detected
     * (keyCode 13), then a down arrow is inserted first, which has the
     * effect of selecting the first item in the suggestions, if any.
     */
    function overrideAddEventListener() {
      // http://stackoverflow.com/a/11703018/1220963
      var _addEventListener = (element.addEventListener) ? element.addEventListener : element.attachEvent;

      function addEventListenerWrapper(type, listener) {
        // Simulate a 'down arrow' keypress on hitting 'return' when no pac suggestion is selected,
        // and then trigger the original listener.
        if (type === "keydown") {
          var orig_listener = listener;
          listener = function(event) {
            var suggestion_selected = $(".pac-item-selected").length > 0;
            if ((event.which === 13 || event.keyCode === 13) && !suggestion_selected) {
              var simulated_downarrow = $.Event("keydown", {
                keyCode: 40,
                which: 40
              });
              orig_listener.apply(element, [simulated_downarrow]);
            }

            orig_listener.apply(element, [event]);
          };
        }

        _addEventListener.apply(element, [type, listener]);
      }

      element.addEventListener = addEventListenerWrapper;
      element.attachEvent = addEventListenerWrapper;
    }

    function init() {

      geocoder = new google.maps.Geocoder();

      overrideAddEventListener();

      initDomElements();
      initAutoComplete();
      initMap();

      if (!element.value) {
        var lat = options.latitude || $(options.latitudeInput).prop("value");
        var lng = options.longitude || $(options.longitudeInput).prop("value");
        if (lat && lng) {
          instance.setLocation(lat, lng);
        }
      } else {
        codePlace(element.value);
      }

      $(window).on("resize", resizeHandler);
      $(element).on("keypress", function(e) {
        if (options.preventSubmit && e.keyCode === 13) {
          e.preventDefault();
          e.stopImmediatePropagation();
        }
      });

    }

    function setPlace(place, updateMap) {
      updateMap = typeof updateMap === "undefined" ? true : false;
      _place = place;

      instance.resize();

      var pos = place.geometry.location;

      if (updateMap) {
        updatePosition(pos);
      }

      $(options.latitudeInput).prop("value", pos.lat());
      $(options.longitudeInput).prop("value", pos.lng());

      // update inputs
      if (!updateMap) {
        element.value = place.formatted_address;
      }

      if (typeof options.placeChanged === "function") {
        options.placeChanged.call(instance, place);
      }
    }

    function updatePosition(pos) {

      if (!map) {
        return;
      }

      map.setCenter(pos);

      var icon = options.icon || options.placesIcon && place.icon ? place.icon : null;

      if (icon) {
        var iconOptions = {
          url: icon,
          size: new google.maps.Size(71, 71),
          origin: new google.maps.Point(0, 0),
          anchor: new google.maps.Point(17, 34),
          scaledSize: new google.maps.Size(35, 35)
        };
        marker.setIcon(iconOptions);
      }

      marker.setPosition(pos);
      marker.setVisible(true);


    }

    this.setValue = function(value) {
      element.value = value;
      codePlace(value);
    };

    this.getValue = function() {
      return element.value;
    };

    this.setLocation = function(latitude, longitude) {
      var latLng = new google.maps.LatLng(latitude, longitude);
      this.setLatLng(latLng);
    };

    this.getLocation = function() {
      var latLng = this.getLatLng();
      return {
        latitude: latLng && latLng.lat() || options.latitude,
        longitude: latLng && latLng.lng() || options.longitude
      };
    };

    this.setLatLng = function(latLng) {
      _latLng = latLng;
      codeLatLng(_latLng);
    };

    this.getLatLng = function() {
      if (_place && _place.geometry) {
        return _place.geometry.location;
      }
      return _latLng;
    };

    this.getMap = function() {
      return map;
    };

    this.reload = function() {
      if (map) {
        codePlace(element.value);
      }
    };

    this.resize = function() {
      if (map) {
        var center = map.getCenter();
        google.maps.event.trigger(map, "resize");
        map.setCenter(center);
      }
    };

    this.geoLocation = function(callback) {
      // Try HTML5 geolocation
      if(navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
          var pos = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
          updatePosition(pos);
          codeLatLng(pos);
          if (callback) {
            callback(pos);
          }

        }, function() {
          // error
          if (callback) {
            callback(null);
          }
        });
      } else {
        // Browser doesn't support Geolocation
        if (callback) {
          callback(null);
        }
      }
    };


    init.call(this);
  }

  var pluginClass = PlacePicker;

  // register plugin
  $.fn[pluginName] = function(options) {

    return this.each(function() {
      if (!$(this).data(pluginName)) {
          $(this).data(pluginName, new pluginClass(this, $.extend({}, defaults, options, $(this).data())));
      }
      return $(this);
    });
  };



} )( jQuery );


      $(document).ready(function() {

        // Basic usage
       /// jQuery(".placepicker").placepicker();

        // Advanced usage
        $("#advanced-placepicker").each(function() {
          var target = this;
          var $collapse = $(this).parents('.form-group').next('.collapse');
          var $map = $collapse.find('.another-map-class');

          var placepicker = $(this).placepicker({
            map: $map.get(0),
            placeChanged: function(place) {
              console.log("place changed: ", place.formatted_address, this.getLocation());

               console.log($(".placepicker").placepicker('getLatLng'));

               console.log("long and lang: ", this.getLocation( ["latitude"]));



               // dot notation
console.log("latitude: ", latitude.value);
console.log("longitude: ", longitude.value);
            
            }
          }).data('placepicker');
        });

      }); // END document.ready

    </script>

    <style>

      .placepicker-map {
        width: 100%;
        height: 300px;
      }

      .another-map-class {
        width: 100%;
        height: 300px;
      }

      .pac-container {
        border-radius: 5px;
      }

    </style>

  </head>

  <body>

    <header class="subhead" id="overview">
      <div class="container">
        <h1>jquery-placepicker</h1>
        <p class="lead">A simple placepicker component for the google-maps api.
        </p>
      </div>
    </header>

    <div class="container">

      <h1>Basic usage</h1>
      <div class="row" data-example>
        <div class="col-md-6">
          <div class="form-group">
            <input class="placepicker form-control" placeholder="Enter a location"/>
          </div>
        </div>
      </div>

      <h3>Set value by location coordinates</h3>
      <div class="row" data-example>
        <div class="col-md-6">
          <div class="form-group">
            <input class="placepicker form-control"
             data-latitude="53.538764"
             data-longitude="10.028240"/>
          </div>
        </div>
     </div>

       <h3>Use hidden inputs for submitting location coordinates</h3>
       <div class="row" data-example>
         <div class="col-md-6">
           <form>
             <div class="form-group">

               <input class="placepicker form-control"
                placeholder="Enter a location"
                data-latitude-input="#latitude"
                data-longitude-input="#longitude"/>

               <input type="hidden" id="latitude" name="latitude" value="53.538764"/>
               <input type="hidden" id="longitude" name="longitude" value="10.028240"/>

             </div>
             <button type="submit" class="btn btn-default">Submit</button>
           </form>


         </div>
       </div>

      <h3>Integrating a map view</h3>

      <div class="row" data-example>
         <div class="col-md-6">
           <form>
             <div class="form-group">
               <input class="placepicker form-control" data-map-container-id="collapseOne" />
             </div>

             <div id="collapseOne" class="collapse">
               <div class="placepicker-map thumbnail"></div>
             </div>

             <button type="submit" class="btn btn-default">Submit</button>
           </form>
         </div>
       </div>


      <h1>Advanced usage</h1>

      <h3>Custom map CSS class and JavaScript callback</h3>

      <div class="row" data-example>
         <div class="col-md-6">
           <form>
             <div class="form-group">
               <input id="advanced-placepicker" class="form-control" data-map-container-id="collapseTwo" />
             </div>

             <div id="collapseTwo" class="collapse">
               <div class="another-map-class thumbnail"></div>
             </div>

             <button type="submit" class="btn btn-default">Submit</button>
           </form>
         </div>
       </div>

    </div>




    <form>
             <div class="form-group">
               <div class="input-group"><span class="input-group-btn"><button type="button" data-toggle="collapse" href="#collapseTwo" class="btn btn-default"><span class="glyphicon glyphicon-globe"></span></button></span>
               	<input id="advanced-placepicker" class="form-control" data-map-container-id="collapseTwo" placeholder="Enter a location" autocomplete="on"></div>
             </div>
         </form>


  </body>

</html>




<?php
return ob_get_clean();
}
add_shortcode( 'jak-dojade', 'jakdojade' );