var eventorganiserMapsAdapter = eventorganiserMapsAdapter || {};
eventorganiserMapsAdapter.openstreetmap = eventorganiserMapsAdapter.openstreetmap || {};
eventorganiserMapsAdapter.provider = eventorganiserMapsAdapter.openstreetmap;

/**
 * OSM Adapter class
 * Dynamic Prototype Pattern, Adapter
 *
 * @param string elementID A DOM ID of the container for the map
 * @param object args Properties of the map
 */
eventorganiserMapsAdapter.openstreetmap.map = function( elementID, args) {

    this.elementID = elementID;
    this.args = jQuery.extend({
        zoom: 12,
        minZoom: 0,
        maxZoom: 18,
    }, args );
    var mapArgs = {
        zoom: this.args.zoom,
        minZoom: this.args.minZoom,
        maxZoom: this.args.maxZoom,
        draggable: this.args.draggable,
        zoomControl: this.args.zoomcontrol,//whether to display +/- for zooming;
        scrollWheelZoom: this.args.scrollwheel,//zoom using scroll wheel
        tiles: {
            url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
        },
        worldCopyJump: true
    };
    this._markers = {};

    this._map = L.map( document.getElementById( this.elementID ), mapArgs );
    L.tileLayer( mapArgs.tiles.url, mapArgs.tiles).addTo(this._map);

    // constructor prototype to share properties and methods
    if ( typeof this.setCenter !== "function" ) {
        /**
         * Set the center of the map to the specified location
         * @param object location With properties 'lat' and 'lng'
         */
        eventorganiserMapsAdapter.openstreetmap.map.prototype.setCenter = function( location ) {
            var latlng = L.latLng(location.lat, location.lng);
            this._map.setView( latlng, args.zoom );
        };

        /**
         * Set the zoom level of the map
         * @param int zoom
         */
        eventorganiserMapsAdapter.openstreetmap.map.prototype.setZoom = function( zoom ) {
            this._map.setZoom( zoom );
        };

        /**
         * Set the zoom level of the map to fit the array of locations
         * @param array locations Array of objects with properties 'lat' and 'lng'
         */
        eventorganiserMapsAdapter.openstreetmap.map.prototype.fitLocations = function( locations ) {
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

        /**
         * Add a marker to the location
         * A location has an ID (venue_id), location (lat, lng) and, optional, tooltip content (tooltipContent)
         * @param object location
         */
        eventorganiserMapsAdapter.openstreetmap.map.prototype.addMarker = function( location ) {
            location.map = this;
            var marker = new eventorganiserMapsAdapter.openstreetmap.marker( location );
            this._markers[location.venue_id] = marker;
            return marker;
        };
    }

    if ( this.args.locations.length > 1 ) {
        this.fitLocations( this.args.locations );
    } else if( this.args.locations.length > 0) {
        this.setCenter( this.args.locations[0] );
    }

};

/**
 * A marker instance tied to a specific location
 * Argument must include the properties: map (a map adapter instance), position (lat/lng object),
 * venue_id (location ID), tooltipContent (optional, content for tooltip), icon (optional icon image URL)
 * @param object args
 */
eventorganiserMapsAdapter.openstreetmap.marker = function ( args ) {

    this.map = args.map;

    var marker_options = jQuery.extend({}, args, {});
    delete marker_options.icon;

    this._marker = L.marker([args.position.lat, args.position.lng], marker_options);
    this._marker.addTo(this.map._map);

    if( args.tooltipContent ){
        this._marker.bindPopup( args.tooltipContent )
    }

    var mapAdapter =  this.map;

    // constructor prototype to share properties and methods
    if ( typeof this.setCenter !== "function" ) {
        /**
         * Set the location of the marker
         * @param object latLng with properties lat and lng
         */
        eventorganiserMapsAdapter.openstreetmap.marker.prototype.setPosition = function( latLng ) {
            this._marker.setLatLng( [latLng.lat, latLng.lng ]  );
        };

        eventorganiserMapsAdapter.openstreetmap.marker.prototype.getPosition = function( latLng ) {
            let position = this._marker.getLatLng();
            return {lat: position.lat, lng: position.lng}
        };

        eventorganiserMapsAdapter.openstreetmap.marker.prototype.remove = function( ) {
            this._marker.remove();
        };

        eventorganiserMapsAdapter.openstreetmap.marker.prototype.setIcon = function( url ) {
            var markerInst = this._marker;
            jQuery("<img/>",{
                load : function(){
                    markerInst.setIcon(L.icon({
                        iconUrl: url,
                        iconSize: [this.width, this.height],
                        iconAnchor: [this.width/2, this.height]
                    }));
                },
                src  : url
            });
        };

        /**
         * Event handler for the marker
         * Only explicitly supported events: drag, dragEnd, move
         * @param eventName Event to listen to
         * @param callback Callback to be triggered when event occurs
         */
        eventorganiserMapsAdapter.openstreetmap.marker.prototype.on = function( eventName, callback ) {
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


				if(args.icon){
					this.setIcon(args.icon);
				}

    }

}

/**
 * A geocoder
 * Accepts an address and passes latitude/longitude co-ordinates to  the callback
 */
eventorganiserMapsAdapter.openstreetmap.geocoder = function( ) {
    if ( typeof this.geocode !== "function" ) {
        /**
         * Look up address and pass latitude/longitude co-ordinates to callback
         * @param object address - with keys such as 'address' (street address), 'city', 'state', 'postcode' etc
         * @param callable callback
         */
        eventorganiserMapsAdapter.openstreetmap.geocoder.prototype.geocode = function ( address, callback ) {
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
