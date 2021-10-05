var eventorganiserMapsAdapter = eventorganiserMapsAdapter || {};
eventorganiserMapsAdapter.googlemaps = eventorganiserMapsAdapter.googlemaps || {};
eventorganiserMapsAdapter.provider = eventorganiserMapsAdapter.googlemaps;
/**
 * Google Maps Adapter class
 * Dynamic Prototype Pattern, Adapter
 *
 * @param string elementID A DOM ID of the container for the map
 * @param object args Properties of the map
 */
eventorganiserMapsAdapter.googlemaps.map = function ( elementID, args) {

    this.elementID = elementID;
    this.args = args;
    var mapArgs = {
        zoom: args.zoom,
        scrollwheel: args.scrollwheel,
        zoomControl: args.zoomcontrol,
        rotateControl: args.rotatecontrol,
        panControl: args.pancontrol,
        overviewMapControl: args.overviewmapcontrol,
        streetViewControl: args.streetviewcontrol,
        draggable: args.draggable,
        mapTypeControl: args.maptypecontrol,
        //mapTypeId: google.maps.MapTypeId.ROADMAP
        mapTypeId: google.maps.MapTypeId[args.maptypeid],
        styles: args.styles,
        minZoom: args.minzoom,
        maxZoom: args.maxzoom,
    };

    mapArgs = wp.hooks.applyFilters( 'eventorganiser.google_map_options', mapArgs, this.args )

    this._markers = {};
    this._map     = new google.maps.Map( document.getElementById( this.elementID ), mapArgs );

    // constructor prototype to share properties and methods
    if ( typeof this.setCenter !== "function" ) {
        /**
         * Set the center of the map to the specified location
         * @param object location With properties 'lat' and 'lng'
         */
        eventorganiserMapsAdapter.googlemaps.map.prototype.setCenter = function( location ) {
            this._map.setCenter( { lat: parseFloat( location.lat ), lng: parseFloat( location.lng ) } );
        };

        /**
         * Set the zoom level of the map
         * @param int zoom
         */
        eventorganiserMapsAdapter.googlemaps.map.prototype.setZoom = function( zoom ) {
            this._map.setZoom( zoom );
        };

        /**
         * Set the zoom level of the map to fit the array of locations
         * @param array locations Array of objects with properties 'lat' and 'lng'
         */
        eventorganiserMapsAdapter.googlemaps.map.prototype.fitLocations = function( locations ) {
            var bounds = new google.maps.LatLngBounds();
            for ( var j = 0; j < locations.length; j++ ) {
                var latlng = { lat: parseFloat( locations[j].lat ), lng: parseFloat( locations[j].lng ) };
                bounds.extend(latlng);
            }
            this._map.fitBounds( bounds );
        };

        /**
         * Add a marker to the location
         * A location has an ID (venue_id), position ({lat:<lat>, lng:<lng>}) and,
				 * optional, tooltip content (tooltipContent), icon URL
         * @param object location, with position property (with lat, lng properties)
         */
        eventorganiserMapsAdapter.googlemaps.map.prototype.addMarker = function( location ) {
            location.map = this;
            var marker = new eventorganiserMapsAdapter.googlemaps.marker( location );
            this._markers[location.venue_id] = marker;
            return marker;
        };
    }

    //Add the locations
    if ( this.args.locations.length > 1 ) {
        this.fitLocations( this.args.locations );
    } else if( this.args.locations.length > 0 ) {
        this.setCenter( this.args.locations[0] );
    }

    wp.hooks.doAction( 'eventorganiser.google_map_loaded', this );
};

/**
 * A marker instance tied to a specific location
 * Argument must include the properties: map (a map adapter instance), position (lat/lng object),
 * venue_id (location ID), tooltipContent (optional, content for tooltip), icon (optional icon image URL)
 * @param object args
 */
eventorganiserMapsAdapter.googlemaps.marker = function ( args ) {

    this.map = args.map;

    var marker_options = jQuery.extend({}, args, {
        venue_id: args.venue_id,
        position: { lat: parseFloat( args.position.lat ), lng: parseFloat( args.position.lng ) },
        icon: args.icon,
        map: this.map._map
    });

    //Store the google instance of the marker
    this._marker = new google.maps.Marker( marker_options );

    if ( args.tooltipContent ) {
        var infowindow = new google.maps.InfoWindow({
            content: marker_options.tooltipContent
        });
        var _marker = this._marker;
        google.maps.event.addListener(this._marker, 'click', function() {

            if (this.map.openWindow) {
                this.map.openWindow.close();
            }
            this.map.openWindow = infowindow;
            infowindow.open( marker_options.map, _marker );
        });
    }

    var proxiedCallbacks = {};

    var mapAdapter =  this.map;

    // constructor prototype to share properties and methods
    if ( typeof this.setPosition !== "function" ) {
        /**
         * Set the location of the marker
         * @param object latLng with properties lat and lng
         */
        eventorganiserMapsAdapter.googlemaps.marker.prototype.setPosition = function( latLng ) {
            this._marker.setPosition( latLng );
        };

        eventorganiserMapsAdapter.googlemaps.marker.prototype.getPosition = function( latLng ) {
            let position = this._marker.getPosition();
            return {lat: position.lat(), lng: position.lng()}
        };

        //getLatLng
        eventorganiserMapsAdapter.googlemaps.marker.prototype.remove = function( ) {
            this._marker.setMap(null);
        };

        eventorganiserMapsAdapter.googlemaps.marker.prototype.setIcon = function( url ) {
        	this._marker.setIcon( url );
        };

        /**
         * Event handler for the marker
         * Only explicitly supported events: drag, dragEnd, move
         * @param eventName Event to listen to
         * @param callback Callback to be triggered when event occurs
         */
        eventorganiserMapsAdapter.googlemaps.marker.prototype.on = function( eventName, callback ) {
            var eventNameMapped;
            switch( eventName ) {
                case 'move':
                    eventNameMapped = 'position_changed';//event name according to Google
                    break;
                default:
                    eventNameMapped = eventName;
            }

            proxiedCallbacks[eventNameMapped] = callback;

            google.maps.event.addListener(this._marker, eventNameMapped, function( evt ){
                var callback = proxiedCallbacks[eventNameMapped];

                var proxyEvt = {
                    _evt: evt,
                    type: eventName,
                    target: {
                        map: mapAdapter,
                        latlng: {}
                    }
                }
                if ( ! evt || typeof evt.latLng == undefined ) {
                    var latLng = this.getPosition();
                } else {
                    var latLng = evt.latLng;
                }

                proxyEvt.target.latlng = {
                    lat: latLng.lat().toFixed(6),
                    lng: latLng.lng().toFixed(6)
                }
                callback.call( mapAdapter, proxyEvt );
            } );
        }
    }
}

/**
 * A geocoder
 * Accepts an address and passes latitude/longitude co-ordinates to  the callback
 */
eventorganiserMapsAdapter.googlemaps.geocoder = function( ) {
    this._geocoder = new google.maps.Geocoder();
    if ( typeof this.geocode !== "function" ) {
        /**
         * Look up address and pass latitude/longitude co-ordinates to callback
         * @param object address - with keys such as 'address' (street address), 'city', 'state', 'postcode' etc
         * @param callable callback
         */
        eventorganiserMapsAdapter.googlemaps.geocoder.prototype.geocode = function ( address, callback ) {

            //Comma delimitate the address
            var addressString = "";
            for (var i in address) {
                addressString += address[i] + ", ";
            }

            this._geocoder.geocode(
                { 'address': addressString},
                function (results, status) {
                    if ( status == google.maps.GeocoderStatus.OK ){
                        callback.call( this, {
                            lat: results[0].geometry.location.lat(),
                            lng: results[0].geometry.location.lng()
                        } );
                    }else{
                        return callback.call( this, false );
                    }
                }
            );
        };
    }
}
