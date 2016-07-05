var eventorganiserMaps = eventorganiserMaps || {};

/**
 * OSM Adapter class
 * Dynamic Prototype Pattern, Adapter
 */
eventorganiserMaps.EOOpenStreetMapAdapter = function( elementID, args) {

    this.elementID = elementID;
    this.args = args;
    var mapArgs = {
        zoom: 12,//args.zoom,
        minZoom: 0,//args.minzoom,
        maxZoom: 20,//args.maxzoom,
        locations: args.locations,
        draggable: args.draggable,
        zoomControl: args.zoomcontrol,//whether to display +/- for zooming;
        scrollWheelZoom: args.scrollwheel,//zoom using scroll wheel
        tiles: {
            url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
        },
        worldCopyJump: true
    };
    this._markers = {};

    //mapArgs = wp.hooks.applyFilters( 'eventorganiserMaps.leaflet_map_options', mapArgs, this.args );
    this._map = L.map( document.getElementById( this.elementID ), mapArgs );
    L.tileLayer( mapArgs.tiles.url, mapArgs.tiles).addTo(this._map);

    var mapAdapter = this;

    // constructor prototype to share properties and methods
    if ( typeof this.setCenter !== "function" ) {

        eventorganiserMaps.EOOpenStreetMapAdapter.prototype.setCenter = function( location ) {
            var latlng = L.latLng(location.lat, location.lng);
            this._map.setView( latlng, args.zoom );
        };

        eventorganiserMaps.EOOpenStreetMapAdapter.prototype.setZoom = function( zoom ) {
            this._map.setZoom( zoom );
        };

        eventorganiserMaps.EOOpenStreetMapAdapter.prototype.fitLocations = function( locations ) {
            var bounds = new L.LatLngBounds();
            for( var j = 0; j < locations.length; j++ ) {

                var lat = locations[j].lat;
                var lng = locations[j].lng;

                if (lat === undefined || lng === undefined) {
                    continue;
                }
                var latlng = L.latLng(lat, lng);
                bounds.extend(latlng);
            }
            this._map.fitBounds( bounds );
        };

        eventorganiserMaps.EOOpenStreetMapAdapter.prototype.addMarker = function( location ) {
            location.map = this;
            var marker = new eventorganiserMaps.EOOpenStreetMarkerAdapter( location );
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

eventorganiserMaps.EOOpenStreetMarkerAdapter = function ( args ) {

    this.map = args.map;

    var marker_options = jQuery.extend({}, args, {});
    delete marker_options.icon;

    this._marker = L.marker([args.position.lat, args.position.lng], marker_options);
    this._marker.addTo(this.map._map);

    if( args.tooltip ){
        this._marker.bindPopup( args.tooltipContent )
    }

    var mapAdapter =  this.map;

    // constructor prototype to share properties and methods
    if ( typeof this.setCenter !== "function" ) {
        eventorganiserMaps.EOOpenStreetMarkerAdapter.prototype.setPosition = function( latLng ) {
            this._marker.setLatLng( [latLng.lat, latLng.lng ]  );
        };
        eventorganiserMaps.EOOpenStreetMarkerAdapter.prototype.on = function( eventName, callback ) {
            //TODO store callbacks to we can support removing them
            this._marker.on( eventName, function( evt ){
                var proxyEvt = {
                    _evt: evt,
                    type: eventName,
                    target: {
                        map: mapAdapter,
                        latlng: evt.target._latlng
                    }
                }
                callback.call( mapAdapter, proxyEvt );
            } );
        }
    }

}

eventorganiserMaps.EOOpenStreetGeocoderAdapter = function( ) {
    if ( typeof this.geocode !== "function" ) {
        eventorganiserMaps.EOOpenStreetGeocoderAdapter.prototype.geocode = function ( address, callback ) {
            jQuery.ajax({
                url: 'https://nominatim.openstreetmap.org/search',
                data: {
                    street: address.address,
                    city: address.city,
                    state: address.state,
                    postalcode: address.postcode,
                    format: 'json'
                },
                success: function( data ) {
                    var latlng = false;
                    if ( data.length > 0 ) {
                        latlng = {
                            lat: parseFloat( data[0].lat ),
                            lng: parseFloat( data[0].lon ),
                        };
                    }
                    return callback.call( this, latlng );
                },
                dataType: 'json'
            });
        };
    }
}