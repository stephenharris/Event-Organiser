var eventorganiserMaps = eventorganiserMaps || {};
/**
 * Google Maps Adapter class
 * Dynamic Prototype Pattern, Adapter
 */
eventorganiserMaps.EOGoogleMapAdapter = function ( elementID, args) {

    this._proxiedCallbacks = {};
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
        locations: args.locations
    };

    this._markers = {};

    //mapArgs = wp.hooks.applyFilters( 'eventorganiserMaps.google_map_options', mapArgs, this.args );
    this._map = new google.maps.Map( document.getElementById( this.elementID ), mapArgs );

    var mapAdapter = this;

    // constructor prototype to share properties and methods
    if ( typeof this.setCenter !== "function" ) {

        eventorganiserMaps.EOGoogleMapAdapter.prototype.setCenter = function( location ) {
            var latlng = new google.maps.LatLng(location.lat, location.lng );
            this._map.setCenter( latlng );
        };

        eventorganiserMaps.EOGoogleMapAdapter.prototype.setZoom = function( zoom ) {
            this._map.setZoom( zoom );
        };

        eventorganiserMaps.EOGoogleMapAdapter.prototype.fitLocations = function( locations ) {
            var bounds = new google.maps.LatLngBounds();
            for( var j = 0; j < locations.length; j++ ) {

                var lat = locations[j].lat;
                var lng = locations[j].lng;

                if (lat === undefined || lng === undefined) {
                    continue;
                }

                var latlng = new google.maps.LatLng(lat, lng);
                bounds.extend(latlng);
            }
            this._map.fitBounds( bounds );
        };

        eventorganiserMaps.EOGoogleMapAdapter.prototype.addMarker = function( location ) {
            location.map = this;
            var marker = new eventorganiserMaps.EOGoogleMarkerAdapter( location );
            this._markers[location.venue_id] = marker;
            return marker;
        };

    }

    if ( this.args.locations.length > 1 ) {
        this.fitLocations( this.args.locations );
    } else {
        this.setCenter( this.args.locations[0] );
    }

    //wp.hooks.doAction( 'eventorganiserMaps.google_map_loaded', this );

};

eventorganiserMaps.EOGoogleMarkerAdapter = function ( args ) {

    this.map = args.map;
    args.map = this.map._map;

    var latlng =  new google.maps.LatLng( args.position.lat, args.position.lng );
    var marker_options = jQuery.extend({}, args, {
        venue_id: args.venue_id,
        position: latlng,
        content: args.tooltipContent,
        position: latlng,
        icon: args.icon
    });

    this._marker = new google.maps.Marker(marker_options);

    var infowindow = new google.maps.InfoWindow({
        content: marker_options.content
    });

    if( args.tooltip ){
        google.maps.event.addListener(marker, 'click', function() {
            infowindow.open(args.map,marker);
        });
    }

    var proxiedCallbacks = {};

    var mapAdapter =  this.map;

    // constructor prototype to share properties and methods
    if ( typeof this.setPosition !== "function" ) {
        eventorganiserMaps.EOGoogleMarkerAdapter.prototype.setPosition = function( latLng ) {
            //TODO
            this._marker.setPosition( {lat: latLng.lat, lng: latLng.lng } );
        };

        eventorganiserMaps.EOGoogleMarkerAdapter.prototype.on = function( eventName, callback ) {
            var eventNameMapped;

            //TODO store callbacks to we can support removing them
            switch(eventName) {
                case 'move':
                    eventNameMapped = 'position_changed';
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

eventorganiserMaps.EOGoogleGeocoderAdapter = function( ) {
    this._geocoder = new google.maps.Geocoder();
    // constructor prototype to share properties and methods
    if ( typeof this.geocode !== "function" ) {
        eventorganiserMaps.EOGoogleGeocoderAdapter.prototype.geocode = function ( address, callback ) {
            console.log( 'google geocoder' );
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



