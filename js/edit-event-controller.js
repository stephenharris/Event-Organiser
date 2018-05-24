var eventorganiser = eventorganiser || {};
/**
 * Simply compares two string version values.
 *
 * Example:
 * versionCompare('1.1', '1.2') => -1
 * versionCompare('1.1', '1.1') =>  0
 * versionCompare('1.2', '1.1') =>  1
 * versionCompare('2.23.3', '2.22.3') => 1
 *
 * Returns:
 * -1 = left is LOWER than right
 *  0 = they are equal
 *  1 = left is GREATER = right is LOWER
 *  And FALSE if one of input versions are not valid
 *
 * @function
 * @param {String} left  Version #1
 * @param {String} right Version #2
 * @return {Integer|Boolean}
 * @author Alexey Bass (albass)
 * @since 2011-07-14
 */
eventorganiser.versionCompare = function(left, right) {
	if (typeof left + typeof right != 'stringstring')
		return false;

	var a = left.split('.'), b = right.split('.'), len = Math.max(a.length, b.length);

	for ( var i = 0; i < len; i++) {
		if ((a[i] && !b[i] && parseInt(a[i],10) > 0) || (parseInt(a[i],10) > parseInt(b[i],10))) {
			return 1;
		} else if ((b[i] && !a[i] && parseInt(b[i],10) > 0) || (parseInt(a[i],10) < parseInt(b[i],10))) {
			return -1;
		}
	}

	return 0;
};


jQuery(document).ready(function($) {
var eo_venue_obj;
//Date fields must be wrapped inside event-date
eventOrganiserSchedulePicker.init({
	views: {
		start_date: '#eo-start-date',
		start_time: '#eo-start-time',
		end_date: '#eo-end-date',
		end_time: '#eo-end-time',
		occurrence_picker: '#eo-occurrence-datepicker',
		occurrence_picker_toggle: '.eo_occurrence_toggle',
		schedule_last_date: '#eo-schedule-last-date',
    	schedule: "#eo-event-recurrence",
    	is_all_day: "#eo-all-day",
		frequency: '#eo-recurrence-frequency',
		week_repeat: '#eo-day-of-week-repeat',
		month_repeat: '#eo-day-of-month-repeat',
		recurrence_section: '.reocurrence_row',
    	include: '#eo-occurrence-includes',
    	exclude: '#eo-occurrence-excludes',
    	schedule_span: '#eo-recurrence-schedule-label',//reads day(s)|week(s)|month(s)|year(s) - depending on schedule selection
    	summary: "#eo-event-summary"
	},
	format: EO_Ajax_Event.format,
	is24hour: Boolean(EO_Ajax_Event.is24hour),
	startday: EO_Ajax_Event.startday,
	schedule: window.eventOrganiserSchedule,
	locale: EO_Ajax_Event.locale,
	editable: ( $("#eo-event-recurrence").val() == 'once' )//if recurring set to false
});

//Edit recurrinng dates
$("#eo-event-recurrring-notice").click(function() {
	//eventOrganiserSchedulePicker.update_schedule();
	window.eventOrganiserSchedulePicker.options.editable = $("#eo-event-recurrring-notice").is(':checked');
	window.eventOrganiserSchedulePicker.update_form();
});

//Map
eovenue.init_map( 'venuemap', {
	lat: $("#eo_venue_Lat").val(),
	lng: $("#eo_venue_Lng").val(),
	draggable: false,
	onPositionchanged: function (evt){
		var latlngStr = evt.target.latlng.lat + ', ' + evt.target.latlng.lng;

		jQuery("#eo_venue_Lat").val( evt.target.latlng.lat);
		jQuery("#eo_venue_Lng").val( evt.target.latlng.lng );

		evt.target.map.setCenter( evt.target.latlng );
		//google.maps.event.trigger(eovenue.get_map( 'venuemap' ).map,'resize');
		evt.target.map.setZoom( 15 );
	},
});

//The venue combobox
$.widget("ui.combobox", {
	_create: function () {
	var c = this.element.hide(), id = c.attr( 'id' ),d = c.children(":selected"),e = d.val() ? d.text() : "";
	var wrapper  = $("<span>").addClass("ui-combobox eo-venue-input").insertAfter(c);
	var $hiddenEl = $('<input type="hidden" name="'+c.attr('name')+'" value="'+d.val()+'"/>');
	var input = $("<input>").attr('id',id).appendTo(wrapper).val(e).addClass("ui-combobox-input");
	var options = {
		delay: 0,
		minLength: 0,
		source: function (a, callback) {
			input.addClass( 'eo-waiting' );
			$.getJSON(EO_Ajax_Event.ajaxurl + "?action=eo-search-venue", a, function (a) {
				var venues = $.map(a, function (a) {a.label = a.name;return a;});
				callback(venues);
				input.removeClass( 'eo-waiting' );
			});
		},
		select: function (a, b) {
			if ($(".venue_row").length > 0) {
				if ( parseInt( b.item.term_id, 10 ) === 0) {
					$(".venue_row").hide();
					$("#eventorganiser_detail .eo-add-new-venue").hide();
				} else {
					$(".venue_row").show();
					$("#eventorganiser_detail .eo-add-new-venue").hide();
				}

				eovenue.get_map( 'venuemap' ).marker[0].setPosition( {
					'lat': b.item.venue_lat, 'lng': b.item.venue_lng
				} );
			}
			$hiddenEl.val( b.item.term_id );
		}
	};
	input.autocomplete(options).addClass("ui-widget-content ui-corner-left");
    this.element.replaceWith( $hiddenEl );

	/* Backwards compat with WP 3.3-3.5 (UI 1.8.16-1.9.2)*/
	var jquery_ui_version = $.ui ? $.ui.version || 0 : -1;
	var ac_namespace = ( eventorganiser.versionCompare( jquery_ui_version, '1.10' ) >= 0 ? 'ui-autocomplete' : 'autocomplete' );

	//Apend venue address to drop-down
	input.data( ac_namespace )._renderItem = function (a, venue ) {
		if ( parseInt( venue.term_id, 10 ) === 0 ) {
			return $("<li></li>").data( ac_namespace + "-item", venue ).append("<a>" + venue.label + "</a>").appendTo(a);
		}
		//Clean address
		var address_array = [venue.venue_address, venue.venue_city, venue.venue_state,venue.venue_postcode,venue.venue_country];
		var address = $.grep(address_array,function(n){return(n);}).join(', ');

		/* Backwards compat with WP 3.3-3.5 (UI 1.8.16-1.9.2)*/
		var li_ac_namespace = ( eventorganiser.versionCompare( jquery_ui_version, '1.10' ) >= 0 ? 'ui-autocomplete-item' : 'item.autocomplete' );

		return $("<li></li>").data( li_ac_namespace, venue)
			.append("<a>" + venue.label + "</br> <span style='font-size: 0.8em'><em>" +address+ "</span></em></a>").appendTo(a);
	};

	//Add new / select buttons
	var button_wrappers = $("<span>").addClass("eo-venue-combobox-buttons").appendTo(wrapper);
	$("<a href='#' style='vertical-align: top;margin: 0px -1px;padding: 0px;height:27px;'>")
		.attr("title", "Show All Venues")
		.appendTo(button_wrappers)
		.button({
			icons: { primary: "ui-icon-triangle-1-s"},
			text: false
		})
		.removeClass("ui-corner-all")
		.addClass("eo-ui-button ui-corner-right ui-combobox-toggle ui-combobox-button")
		.mousedown(function() {
			wasOpen = input.autocomplete( "widget" ).is( ":visible" );
		})
		.click(function (ev) {
			ev.preventDefault();
            if ( wasOpen ) {
                return;
            }
			$(this).blur();
			input.autocomplete("search", "").focus();
		});

	if( EO_Ajax_Event.current_user_can.manage_venues ){
		//Only add this on event edit page - i.e. not on calendar page.
		$("<a href='#' style='vertical-align: top;margin: 0px -1px;padding: 0px;height:27px;'>").attr("title", "Create New Venue").appendTo(button_wrappers).button({
			icons: {primary: "ui-icon-plus"},
			text: false
		}).removeClass("ui-corner-all").addClass("eo-ui-button ui-corner-right add-new-venue ui-combobox-button").click(function (ev) {
			ev.preventDefault();
			$("#eventorganiser_detail .eo-add-new-venue").show();
			$(".venue_row").show();

			//Store existing venue details in case the user cancels creating a new one
			eo_venue_obj={
				id: $("[name='eo_input[event-venue]']").val(),
				label: $(".eo-venue-input input").val(),
				lat: $("#eo_venue_Lat").val(),
				lng: $("#eo_venue_Lng").val()
			};
			$("[name='eo_input[event-venue]']").val(0);
			$('.eo-venue-combobox-select').hide();
			$('.eo-venue-input input').val('');

			//Use selected timezone to 'guess' a new address, so we don't get a blank map instead.
			var address = EO_Ajax_Event.location;
			if( address ){
				address = address.split("/");
				eovenue.geocode( {'city': address[address.length-1]}, function( latlng ){
					if( latlng ){
						eovenue.get_map( 'venuemap' ).marker[0].setPosition( latlng );
					}
				});
			}else{
				eovenue.get_map( 'venuemap' ).marker[0].setPosition( {'lat':0,'lng':0} );
				eovenue.get_map( 'venuemap' ).map.setZoom( 1 );
			}
			$("#eventorganiser_detail .eo-add-new-venue input").first().focus();
		});
	}
}
});

$("#venue_select").combobox();

$(".eo_addressInput").change(function () {
    var address = {};
    $(".eo_addressInput").each(function () {
			var key = $(this).attr('id').replace('eo_venue_add-','');
			address[key] = $(this).val();
    });

    eovenue.geocode( address, function( latlng ){
    	if( latlng ){
        	eovenue.get_map( 'venuemap' ).marker[0].setPosition( latlng );
        }
    });
});

//When cancelling venue input, restore defaults
$('.eo-add-new-venue-cancel').click(function(e){
	e.preventDefault();
	$('.eo-venue-combobox-select').show().find('input:visible').first().focus();
	$('.eo-add-new-venue input').val('');

	//Restore old venue details
	eovenue.get_map( 'venuemap' ).marker[0].setPosition( {'lat': eo_venue_obj.lat, 'lng': eo_venue_obj.lng} );
	$("[name='eo_input[event-venue]']").val( eo_venue_obj.id );
	$(".eo-venue-input input").val( eo_venue_obj.label );
	$("#eventorganiser_detail .eo-add-new-venue").hide();
});
});
