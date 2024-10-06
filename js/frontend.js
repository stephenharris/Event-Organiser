/*jshint -W054 */
if ( typeof EO_SCRIPT_DEBUG === 'undefined') { EO_SCRIPT_DEBUG = true;}

var eventorganiser = eventorganiser || {};

(function ($) {
jQuery(document).ready(function () {

	function _eventorganiser_get_where( collection, field, value ) {
		var object;
		for (  object in collection ){
			if ( collection[field] == value ) {
				return object;
			}
		}
		return false;
	}

	/* Fullcalendar */
	function eventorganiser_filter_markup( options ){

		//Are we whitelisting categories
		var whitelist = ( typeof options.whitelist !== "undefined" && options.whitelist ? options.whitelist.trim().split(/\s*,\s*/) : false );

		var html="<select class='eo-fc-filter eo-fc-filter-"+options.type+"' data-filter-type='"+options.type+"'>";
		html+="<option value=''>"+options.select_none+"</option>";

		var term;
		for ( var term_id in options.terms ){

			term = options.terms[term_id];

			//If whitelist check term (or ancestor of) belongs to white list.
			if( whitelist ){
				var include_in_dropdown = false;

				if( $.inArray( term.slug, whitelist ) !== -1 ){
					include_in_dropdown = true;
				}

				//Check ancestors
				var parent = term;
				while( !include_in_dropdown && parent.parent > 0 ){
					parent = _eventorganiser_get_where( options.terms, 'term_id', parent.parent );
					if( $.inArray( parent.slug, whitelist ) !== -1 ){
						include_in_dropdown = true;
					}
				}

				if( !include_in_dropdown ){
					continue;
				}
			}

			html+= "<option value='"+term.slug+"'>"+term.name+"</option>";
		}
		html+="</select>";

		var element = $("<span class='fc-header-dropdown filter-"+options.type+"'></span>");
		element.append(html);
		return element;
	}

	function eventorganiser_organiser_filter_markup( options ){
		var whitelist = ( typeof options.whitelist !== "undefined" && options.whitelist.length > 0 ? options.whitelist : false );
		var html="<select class='eo-fc-filter eo-fc-filter-organiser' data-filter-type='organiser'>";
		html+="<option value=''>"+options.select_none+"</option>";

		var display_name;
		for ( var user_id in options.users ){
			display_name = options.users[user_id];
			if ( whitelist && $.inArray( parseInt(user_id,10), whitelist ) == -1 ) {
				continue;
			}
			html+= "<option value='"+user_id+"'>"+display_name+"</option>";
		}
		html+="</select>";

		var element = $("<span class='fc-header-dropdown filter-organiser'></span>");
		element.append(html);
		return element;
	}


	$(".eo-fullcalendar").on( 'change', '.eo-fc-filter', function () {
		$(".eo-fullcalendar").fullCalendar( 'rerenderEvents' );
	});

	function eventorganiser_mini_calendar(){
		return $("<span class='fc-header-goto'><input type='hidden' class='eo-mini-calendar'/></span>");
	}

	if ($(".eo-fullcalendar").length > 0) {
		var loadingTimeOut;
		var calendars = eventorganiser.calendars;
		var _eoResponsiveViewMap;
		_eoResponsiveViewMap = {
			'agendaDay': 'listDay',
			'basicDay': 'listDay',
			'listDay': 'listDay',
			'agendaWeek': 'listWeek',
			'basicWeek': 'listWeek',
			'listWeek' : 'listWeek',
			'month': 'listMonth',
			'listMonth': 'listMonth',
		};
		for (var i = 0; i < calendars.length; i++) {
			var calendar = "#eo_fullcalendar_" + (i + 1);
			if (typeof calendars[i].category === "undefined") {
				calendars[i].category ='';
			}
			if (typeof calendars[i].venue === "undefined") {
				calendars[i].venue ='';
			}

			var args = {
				id: calendar,

				defaultDate: calendars[i].defaultdate ? calendars[i].defaultdate : undefined,

				category: calendars[i].event_category,
				venue: calendars[i].event_venue,
				tag: calendars[i].event_tag,
				organiser: calendars[i].event_organiser,
				event_series: calendars[i].event_series,

				customButtons:{
					category: function(){
						return eventorganiser_filter_markup( {
							terms: eventorganiser.fullcal.categories,
							select_none: EOAjaxFront.locale.cat,
							whitelist: calendars[i].event_category,
							type: 'category'
						});
					},
					venue: function(){
						return eventorganiser_filter_markup( {
							terms: eventorganiser.fullcal.venues,
							select_none: EOAjaxFront.locale.venue,
							whitelist: calendars[i].event_venue,
							type: 'venue'
						});
					},
					tag: function(){
						return eventorganiser_filter_markup( {
							terms: eventorganiser.fullcal.tags,
							select_none: EOAjaxFront.locale.tag,
							whitelist: '',
							type: 'tag'
						});
					},
					organiser: function(){
						return eventorganiser_organiser_filter_markup( {
							users: eventorganiser.fullcal.users,
							select_none: EOAjaxFront.locale.view_all_organisers,
							whitelist: calendars[i].event_organiser,
						});
					},
					'goto': 	eventorganiser_mini_calendar
				},

				theme: calendars[i].theme,
				isRTL: calendars[i].isrtl,
				editable: false,
				selectable: false,
            	weekMode: "variable",
				tooltip: calendars[i].tooltip,
				firstDay: parseInt( eventorganiser.fullcal.firstDay, 10 ),
				weekends: calendars[i].weekends,
				hiddenDays: calendars[i].hiddendays,
				slotDuration: calendars[i].slotduration,
				allDaySlot: calendars[i].alldayslot,
				allDayText: calendars[i].alldaytext,
				axisFormat: calendars[i].axisformat,
				minTime: calendars[i].mintime,
				maxTime:calendars[i].maxtime,
				weekNumbers: calendars[i].weeknumbers,
				weekNumbersWithinDays: calendars[i].weeknumberswithindays,
				eventColor: "#1e8cbe",

				timeFormatphp: calendars[i].timeformatphp,
				timeFormat: calendars[i].timeformat,
				columnFormat: {
					month: calendars[i].columnformatmonth,
					week: calendars[i].columnformatweek,
					day: calendars[i].columnformatday
				},
				titleFormat: {
					month: calendars[i].titleformatmonth,
					week: calendars[i].titleformatweek,
					day: calendars[i].titleformatday
				},

				header: {
					left: calendars[i].headerleft,
					center: calendars[i].headercenter,
					right: calendars[i].headerright
				},

				eventRender: function ( event, element, view ) {

					var category = $(view.calendar.options.id).find(".eo-fc-filter-category").val();
					var venue    = $(view.calendar.options.id).find(".eo-fc-filter-venue").val();
					var tag      = $(view.calendar.options.id).find(".eo-fc-filter-tag").val();
					var organiser= $(view.calendar.options.id).find(".eo-fc-filter-organiser").val();
					var render   = true;

					if (typeof category !== "undefined" && category !== "" && $.inArray( category, event.category) < 0 ) {
						render = false;
					}

					if (typeof venue != "undefined" && venue !== "" && venue !== event.venue_slug) {
						render = false;
					}

					if (typeof tag !== "undefined" && tag !== "" && $.inArray(tag, event.tags) < 0 ) {
						render = false;
					}
					if (typeof organiser !== "undefined" && organiser !== "" && parseInt(organiser,10) !== event.organiser) {
						render = false;
					}

					render = wp.hooks.applyFilters( 'eventorganiser.fullcalendar_render_event', render, event, element, view );

					if ( ! render ) {
						return false;
					}

					if ( ! view.calendar.options.tooltip ) {
						return;
					}

					$(element).qtip({
						content: {
							text:  event.description,
                        	button: false,
                        	title: event.title
                        },
                        position: {
                        	my: "top center",
                        	at: "bottom center",
                        	viewport: $(window),
                        	adjust: {
                        		method: 'shift none'
                        	}
                        },
                        hide: {
                        	fixed: true,
                        	delay: 500,
                        	effect: function (a) {$(this).fadeOut("50");}
                        },
                        border: {
                        	radius: 4,
                        	width: 3
                        },
                        style: {
                        	classes: "eo-event-toolip qtip-eo",
                        	///widget: true,
                        	tip: "topMiddle"
                        }
					});
				},

				buttonText: {
                    today: 	EOAjaxFront.locale.today,
                    month: 	EOAjaxFront.locale.month,
                	week: 	EOAjaxFront.locale.week,
                	day: 	EOAjaxFront.locale.day
				},
				monthNames: EOAjaxFront.locale.monthNames,
				monthNamesShort: EOAjaxFront.locale.monthAbbrev,
				dayNames: EOAjaxFront.locale.dayNames,
				dayNamesShort: EOAjaxFront.locale.dayAbbrev,
                height: calendars[i].aspectratio ? false : 'auto',
				aspectRatio: calendars[i].aspectratio ? calendars[i].aspectratio : false,
                responsive: calendars[i].responsive,
                responsiveBreakpoint: calendars[i].responsivebreakpoint,
                defaultView: ( $(window).width() < calendars[i].responsivebreakpoint && calendars[i].responsive )  ? _eoResponsiveViewMap[calendars[i].defaultview] : calendars[i].defaultview,
                previousView: calendars[i].defaultview,
                nextDayThreshold: calendars[i].nextdaythreshold,
                windowResize: function(view) {
                	if( view.calendar.options.responsive && $(window).width() < view.calendar.options.responsiveBreakpoint ){
                		$(this).fullCalendar( 'changeView', _eoResponsiveViewMap[view.calendar.options.previousView] );
                	} else {
                		$(this).fullCalendar( 'changeView', view.calendar.options.previousView );
                	}
                },

                lazyFetching: "true",
                events:
                	function (start, end, timezone, callback) {
                		var options = this.options;
                		var request = {
                				start: start.format( "YYYY-MM-DD" ),
                				end: end.format( "YYYY-MM-DD" ),
                				timeformat: options.timeFormatphp,
                				users_events: options.users_events,
                		};

                		if (typeof options.category !== "undefined" && options.category !== "") {
                			request.category = options.category;
                		}
                		if (typeof options.venue !== "undefined" && options.venue !== "") {
                			request.venue = options.venue;
                		}
                		if (typeof options.tag !== "undefined" && options.tag !== "") {
                			request.tag = options.tag;
                		}
                		if (typeof options.organiser !== "undefined" && options.organiser !== 0) {
                			request.organiser = options.organiser;
                		}
                		if (options.event_series) {
                			request.event_series = options.event_series;
                		}

                		request = wp.hooks.applyFilters( 'eventorganiser.fullcalendar_request', request, start, end, timezone, options );

                		$.ajax({
                			url: eventorganiser.ajaxurl + "?action=eventorganiser-fullcal",
                			dataType: "JSON",
                			data: request,
                			complete: function( r, status ){
                				if ( EO_SCRIPT_DEBUG ) {
                					if( status == "error" ){

                					}else if( status == "parsererror" ){
                						if( window.console ){
                							console.log( "Response is not valid JSON. This is usually caused by error notices from WordPress or other plug-ins" );
                							console.log( "Response reads: " + r.responseText );
                						}
                  						alert( "An error has occurred in parsing the response. Please inspect console log for details" );
                					}
                				}
                			},
                			success: callback,
                		});
                	},

                	loading: function ( is_loading ) {
                		var loading = $("#" + $(this).attr("id") + "_loading");
                		if ( is_loading ) {
                			window.clearTimeout(loadingTimeOut);
                			loadingTimeOut = window.setTimeout(function () {loading.show();}, 1e3);
                		} else {
                			window.clearTimeout(loadingTimeOut);
                			loading.hide();
                		}
                	},
            	};
            	args = wp.hooks.applyFilters( 'eventorganiser.fullcalendar_options', args, calendars[i] );

            	$(calendar).fullCalendar(args);
			}
	}

	if ( typeof eventorganiser.fullcal !== 'undefined' ) {
		$('.eo-mini-calendar').datepicker({
			dateFormat: 'DD, d MM, yy',
			changeMonth: true,
			changeYear: true,
			firstDay: parseInt( eventorganiser.fullcal.firstDay, 10 ),
			buttonText: EOAjaxFront.locale.gotodate,
			monthNamesShort: EOAjaxFront.locale.monthAbbrev,
			dayNamesMin: EOAjaxFront.locale.dayAbbrev,
			nextText: EOAjaxFront.locale.nextText,
			prevText: EOAjaxFront.locale.prevText,
			showOn: 'button',
			beforeShow: function(input, inst) {
				if( inst.hasOwnProperty( 'dpDiv' ) ){
					inst.dpDiv.addClass('eo-datepicker eo-fc-mini-calendar eo-fc-datepicker');
				}else{
					$('#ui-datepicker-div').addClass('eo-datepicker eo-fc-mini-calendar eo-fc-datepicker');
				}
			},
			onSelect: function (dateText, dp) {
				var cal_id = $(this).parents('div.eo-fullcalendar').attr('id');
				$('#'+cal_id).fullCalendar('gotoDate', new Date(Date.parse(dateText)));
			}
		});
	}

	/* Upcoming dates */
	$('#eo-upcoming-dates, .eo-upcoming-dates').each(function(index, value){
		var list = {el: $(this)};
		if (list.el.find('li:gt(4)').length > 0 ){
			var eobloc = 5,
			locale = { more : EOAjaxFront.locale.ShowMore, less : EOAjaxFront.locale.ShowLess};
			list.less = $('<a class="eo-upcoming-dates-show-less" href="#"></a>').text( locale.less );
			list.pipe = $('<span class="eo-upcoming-dates-pipe">|</span>');
			list.more = $('<a class="eo-upcoming-dates-show-more" href="#"></a>').text( locale.more );
			list.el.find('li:gt('+(eobloc-1)+')').hide().end().after( list.less, list.pipe, list.more);
			list.pipe.hide();

			list.less.hide().on('click',function(e){
				e.preventDefault();
				var index = Math.floor( (list.el.find('li:visible').length -1) / eobloc)*eobloc -1;
				list.el.find('li:gt('+index+')').hide();
				list.more.show();
				list.pipe.show();
				if( list.el.find('li:visible').length <= eobloc ){
					list.less.hide();
					list.pipe.hide();
				}
			});
			list.more.on('click', function(e){
				e.preventDefault();
				list.less.show();
				list.pipe.show();
				list.el.find('li:hidden:lt('+eobloc+')').show();
				var offset = list.pipe.offset();
				$('html, body').animate({
					scrollTop: Math.max( offset.top + 40 - $(window).height(),$(window).scrollTop())
				});
				if( list.el.find('li:hidden').length === 0 ){
					list.more.hide();
					list.pipe.hide();
				}
			});
		}
	});

	if ($(".eo-widget-cal-wrap").length > 0 ) {

        	$(".eo-widget-cal-wrap").on("click", 'tfoot a', function (a) {
        		a.preventDefault();

        		if ( $(this).data('eo-widget-cal-disabled' ) ) {
        			return;
        		}

        		var $calEl = $(this).closest(".eo-widget-cal-wrap");
        		var calID = $calEl.data("eo-widget-cal-id");

        		$calEl.find( 'tfoot a').data('eo-widget-cal-disabled',1);

        		//Defaults
        		var cal = {showpastevents: 1, 'show-long': 0, 'link-to-single': 0 };

        		//Shortcode widget calendar
        		if( typeof eventorganiser.widget_calendars !== "undefined" && typeof eventorganiser.widget_calendars[calID] !== "undefined" ){
        			cal = eventorganiser.widget_calendars[calID];
        		}
        		//Widget calendar
        		if( typeof eo_widget_cal !== "undefined" && typeof eo_widget_cal[calID] !== "undefined" ){
        			cal = eo_widget_cal[calID];
                }

                //Set month
                cal.eo_month = eveorg_getParameterByName("eo_month", $(this).attr("href"));

                $calEl.addClass( 'eo-widget-cal-loading' );
                $("#" + calID + "_overlay").remove();
                $("#" + calID + "_content").prepend( '<div class="eo-widget-cal-overlay" id="' + calID + '_overlay"><div class="eo-widget-cal-spinner"/></div>' );

                $.getJSON(
									eventorganiser.add_query_arg( 'action', 'eo_widget_cal', EOAjaxFront.adminajax ),
									cal,
									function (a) {
										$("#" + calID + "_content").html(a);
										$calEl.removeClass( 'eo-widget-cal-loading' );
                	});
        	});
        }

});

eventorganiser.add_query_arg = function( key, value, uri ){
	var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
	var separator = uri.indexOf('?') !== -1 ? "&" : "?";
	if (uri.match(re)) {
		return uri.replace(re, '$1' + key + "=" + value + '$2');
	}else {
		return uri + separator + key + "=" + value;
	}
};

		/**
		 * Lifted from underscore.js
		 * @see http://underscorejs.org/
		 * @license MIT
		 */
    	eventorganiser.template = function( text, data, settings ){

    		var escaper = /\\|'|\r|\n|\t|\u2028|\u2029/g;

    		settings = typeof settings !== 'undefined' ? settings : {};

    		settings = $.extend( true, {
				evaluate:    /<#([\s\S]+?)#>/g,
				interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
				escape      :/\{\{([^\}]+?)\}\}(?!\})/g
    		}, settings );

    		var escapes = {
    				"'":      "'",
    				'\\':     '\\',
    				'\r':     'r',
    				'\n':     'n',
    				'\t':     't',
    				'\u2028': 'u2028',
    				'\u2029': 'u2029'
    		};

    		var render;

    		//Combine delimiters into one regular expression via alternation.
    		var matcher = new RegExp([
                  (settings.escape ).source,
                  (settings.interpolate ).source,
                  (settings.evaluate ).source
                  ].join('|') + '|$', 'g');

    		//Compile the template source, escaping string literals appropriately.
    		var index = 0;
    		var source = "__p+='";
    		text.replace(matcher, function(match, escape, interpolate, evaluate, offset) {
    			source += text.slice(index, offset).replace(escaper, function(match) { return '\\' + escapes[match]; });

    			if (escape) {
    				source += "'+\n((__t=(" + escape + "))==null?'':_.escape(__t))+\n'";
    			}
    			if (interpolate) {
    				source += "'+\n((__t=(" + interpolate + "))==null?'':__t)+\n'";
    			}
    			if (evaluate) {
    				source += "';\n" + evaluate + "\n__p+='";
    			}
    			index = offset + match.length;
    			return match;
    		});
    		source += "';\n";

    		//If a variable is not specified, place data values in local scope.
    		if (!settings.variable) source = 'with(obj||{}){\n' + source + '}\n';

    		source = "var __t,__p='',__j=Array.prototype.join," +
    			"print=function(){__p+=__j.call(arguments,'');};\n" +
    			source + "return __p;\n";

    		try {
    			render = new Function(settings.variable || 'obj', '_', source);
    		} catch (e) {
    			e.source = source;
    			throw e;
    		}

    		if ( data ) return render( data );

    		var template = function( data ) {
    			return render.call( this, data );
    		};

    		return template;
    	};

    	eventorganiser.agenda_widget = function( param ){
    		this.param = param;
    		moment.locale( EOAjaxFront.locale.locale, {
    			months: EOAjaxFront.locale.monthNames,
    			monthsShort: EOAjaxFront.locale.monthAbbrev,
    			weekdays: EOAjaxFront.locale.dayNames,
				weekdaysShort: EOAjaxFront.locale.dayAbbrev,
				weekdaysMin: EOAjaxFront.locale.dayInitial
    		});

    		//use yesterday as starting point as when we first fetch events will be looking for the
    		//first day with events after that point (and we want to include 'today' in that scope)
    		this.start = moment().add(-1, 'days');
    		this.end   = moment().add(-1, 'days');
    		this.$el   = $( '#' + this.param.id + '_container' );
    		this.direction = 1;

    		this.eventTemplate = eventorganiser.template( $('#eo-tmpl-agenda-widget-item').html(), null, {variable: 'event'} );
    		this.groupTemplate = eventorganiser.template( $('#eo-tmpl-agenda-widget-group').html(), null, {variable: 'group'} );
    	};

    	eventorganiser.agenda_widget.prototype.group_change = function( previous, current ){

    		if( previous === false ){
    			return true;
    		}

    		if( this.param.mode !== 'day' ){
    			return false;
    		}

    		return previous.format( 'YYYY-MM-DD' ) !== current.format( 'YYYY-MM-DD' );
    	};

    	eventorganiser.agenda_widget.prototype.init = function(){

    		this.$el.html( eventorganiser.template( $('#eo-tmpl-agenda-widget').html(), {}, {variable: 'data'} ) );
    		this.$datesEl = this.$el.find( '.dates' );
    		this.load_events();

    		//Actions
    		if( this.param.add_to_google ){
    			this.$el.on( "click", '.event', function(){
    				$(this).find(".meta").toggle("400");
    			});
    		}

    		var self = this;
			this.$el.on( "click", '.eo-agenda-widget-nav-prev,.eo-agenda-widget-nav-next', function(){
				if( $(this).hasClass( 'eo-agenda-widget-nav-prev' ) ){
					self.direction = -1;
				}else{
					self.direction = 1;
				}
				self.load_events();
			});

    	};

    	eventorganiser.agenda_widget.prototype.load_events = function(){
    		var self = this;
            $.ajax({
                url: EOAjaxFront.adminajax,
                dataType: "JSON",
                data: {
                    action: "eo_widget_agenda",
                    instance_number: this.param.number,
                    direction: this.direction,
                    start: this.start.format("YYYY-MM-DD"),
                    end: this.end.format("YYYY-MM-DD")
                },
                success: function( events ) {
                	var numberEvents = events.length;

                	if ( numberEvents === 0 ) {
                		return;
                	}

            		for( var i=0; i< numberEvents; i++ ){
            			events[i].start = moment( events[i].start );
            			events[i].end = moment( events[i].end );
            		}
            		self.start = events[0].start;
            		self.end = events[numberEvents-1].start;
            		self.insert_events( events );
                }
            });
    	};

    	eventorganiser.agenda_widget.prototype.insert_events = function( events ){
    		this.$datesEl.html( "" );

    		var numberEvents = events.length;
    		var previous = false, $group = false, $events = false;

    		for( var i=0; i< numberEvents; i++ ){

    			if( this.group_change( previous, events[i].start ) ){
    				this.$datesEl.append( $group );
    				var group = {
    					start: events[i].start,
    				};
    				$group = $( this.groupTemplate( group ) );
    				$events = $group.find( '.a-date' );
    			}

    			$events.append( this.eventTemplate( events[i] ) );

    			previous = events[i].start;
    		}

    		this.$datesEl.append( $group );
    	};

    	jQuery(document).ready(function($){
    		if( $('.eo-agenda-widget').length > 0 ) {
    			for (var agenda in eo_widget_agenda) {
    				agendaWidget = new eventorganiser.agenda_widget( eo_widget_agenda[agenda] );
    				agendaWidget.init();
    			}
    		}
    	});

})(jQuery);


function eveorg_getParameterByName(a, b) {
    a = a.replace(/[\[]/, "\\[")
        .replace(/[\]]/, "\\]");
    var c = "[\\?&]" + a + "=([^&#]*)";
    var d = new RegExp(c);
    var e = d.exec(b);
    if (e === null) return "";
    else return decodeURIComponent(e[1].replace(/\+/g, " "));
}

function eventorganiser_load_maps() {
	if ( ! ( 'map' in eventorganiser ) ) {
		return;
	}

	if (typeof eventorganiserMapsAdapter === "undefined"){
		// Maps adaptor may not have loaded yet, retry after 1s
		setTimeout(eventorganiser_load_maps, 1000);
		return;
	}

	var maps = eventorganiser.map;
	for (var i = 0; i < maps.length; i++) {

		if ( null === document.getElementById( "eo_venue_map-" + (i + 1) ) ) {
		    continue;
		}

		var param = maps[i];
		var map   = new eventorganiserMapsAdapter.provider.map( "eo_venue_map-" + (i + 1), param );
		for (var j=0; j < param.locations.length; j++ ) {
			param.locations[j].position = {
				lat: param.locations[j].lat,
				lng: param.locations[j].lng
			};
			map.addMarker(param.locations[j]);
		}

	}
}

jQuery(document).ready(function(){
	eventorganiser_load_maps();
});
