if ( typeof eo_script_debug === 'undefined') { eo_script_debug = true;}

var eventorganiser = eventorganiser || {};

(function ($) {
	jquery(document).ready(function () {

		function eventorganiser_cat_dropdown(options){

			var terms = options.categories;

			//are we whitelisting categories 
			var included_cats = ( typeof options.category !== "undefined" && options.category ? options.category.split(',') : false );   

			var html="<select class='eo-cal-filter' id='eo-event-cat'>";
			html+="<option value=''>"+options.buttontext.cat+"</option>";
			var term;
			for ( var term_id in terms ){

				term = terms[term_id];

				//if whitelist check term (or ancestor of) belongs to white list.
				if( included_cats ){
					var include_in_dropdown = false;

					if( $.inarray( term.slug, included_cats ) !== -1 ){
						include_in_dropdown = true;
					}

					//check ancestors
					var parent = term;
					while( !include_in_dropdown && parent.parent > 0 ){
						parent = terms[parent.parent];
						if( $.inarray( parent.slug, included_cats ) !== -1 ){
							include_in_dropdown = true;
						}
					}

					if( !include_in_dropdown ){
						continue;
					}
				}

				html+= "<option class='cat-colour-"+term.colour+" cat' value='"+term.slug+"'>"+term.name+"</option>";
			}
			html+="</select>";

			var element = $("<span class='fc-header-dropdown filter-category'></span>");
			element.append(html);
			return element;
		}

		function eventorganiser_mini_calendar(){
			var element = $("<span class='fc-header-goto'><input type='hidden' class='eo-mini-calendar'/></span>");
			return element;
		}

		function eventorganiser_tag_dropdown(options){


			var terms = options.tags;

			var html="<select class='eo-cal-filter' data-filter-type='event-tag'>";
			html+="<option value=''>"+options.buttontext.tag+"</option>";
			for (var i=0; i < terms.length; i++){
				html+= "<option value='"+terms[i].slug+"'>"+terms[i].name+"</option>";	
			}
			html+="</select>";

			var element = $("<span class='fc-header-dropdown filter-tag'></span>");
			element.append(html);
			return element;
		}

		function eventorganiser_venue_dropdown(options){

			var venues = options.venues;

			var html="<select class='eo-cal-filter' id='eo-event-venue'>";
			html+="<option value=''>"+options.buttontext.venue+"</option>";

			//are we whitelisting venues 
			var included_venues = ( typeof options.venue !== "undefined" && options.venue ? options.venue.split(',') : false );

			for (var i=0; i<venues.length; i++){

				//if whitelist check term (or ancestor of) belongs to white list.
				if( included_venues && $.inarray( venues[i].slug, included_venues ) === -1 ){
					continue;
				}

				html+= "<option value='"+venues[i].term_id+"'>"+venues[i].name+"</option>";	
			}

			html+="</select>";
			var element = $("<span class='fc-header-dropdown filter-venue'></span>");
			element.append(html);
			return element;
		}

		if( $('#eo-upcoming-dates').length>0 && $('#eo-upcoming-dates').find('li:gt(4)').length > 0 ){
			var eobloc = 5;
			var locale = { more : eoajaxfront.locale.showmore, less : eoajaxfront.locale.showless};
			$('#eo-upcoming-dates').find('li:gt('+(eobloc-1)+')').hide().end().after(
			$('<a href="#" id="eo-upcoming-dates-less">'+locale.less+'</a> <span id="eo-upcoming-dates-pipe">|</span> <a href="#" id="eo-upcoming-dates-more">'+locale.more+'</a>')
			);
			$('#eo-upcoming-dates-pipe').hide();
			$('#eo-upcoming-dates-less').hide().click(function(e){
				e.preventdefault();
				var index = math.floor( ($('#eo-upcoming-dates li:visible').length -1) / eobloc)*eobloc -1;
				$('#eo-upcoming-dates li:gt('+index+')').hide();
				$('#eo-upcoming-dates-more,#eo-upcoming-dates-pipe').show();
				if( $('#eo-upcoming-dates li:visible').length <= eobloc ){
					$('#eo-upcoming-dates-less,#eo-upcoming-dates-pipe').hide();
				}
			});
			$('#eo-upcoming-dates-more').click(function(e){
				e.preventdefault();
				$('#eo-upcoming-dates-less,#eo-upcoming-dates-pipe, #eo-upcoming-dates li:hidden:lt('+eobloc+')').show();
				var offset = $('#eo-upcoming-dates-pipe').offset();
				$('html, body').animate({
					scrolltop: math.max( offset.top + 40 - $(window).height(),$(window).scrolltop())
				});
				if( $('#eo-upcoming-dates li:hidden').length === 0 ){
					$('#eo-upcoming-dates-more,#eo-upcoming-dates-pipe').hide();
				}
			});
		}

		if ($(".eo-fullcalendar").length > 0) {
			var calendars = eventorganiser.calendars;
			var loadingtimeout;
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
					year: calendars[i].year ? calendars[i].year : undefined,
					month: calendars[i].month ? calendars[i].month : undefined,
					date: calendars[i].date ? calendars[i].date : undefined,
					category: calendars[i].event_category,
					venue: calendars[i].event_venue,
					custombuttons:{
						tag:		eventorganiser_tag_dropdown,
						category:  	eventorganiser_cat_dropdown,
						venue:  	eventorganiser_venue_dropdown,
						'goto': 	eventorganiser_mini_calendar
					},
					theme: calendars[i].theme,
					categories: eventorganiser.fullcal.categories,
					venues: eventorganiser.fullcal.venues,
					tags: eventorganiser.fullcal.tags,
					timeformatphp: calendars[i].timeformatphp,
					timeformat: calendars[i].timeformat,
					isrtl: calendars[i].isrtl,
					editable: false,
					tooltip: calendars[i].tooltip,
					firstday: parseint( eventorganiser.fullcal.firstday, 10 ),
					weekends: calendars[i].weekends,
					alldayslot: calendars[i].alldayslot,
					alldaytext: calendars[i].alldaytext,
					axisformat: calendars[i].axisformat,
					mintime: calendars[i].mintime,
					maxtime:calendars[i].maxtime,
					columnformat: {
						month: calendars[i].columnformatmonth,
						week: calendars[i].columnformatweek,
						day: calendars[i].columnformatday
					},
					titleformat: {
						month: calendars[i].titleformatmonth,
						week: calendars[i].titleformatweek,
						day: calendars[i].titleformatday
					},
					header: {
						left: calendars[i].headerleft,
						center: calendars[i].headercenter,
						right: calendars[i].headerright
					},
					eventrender: 
					function (a, b, v) {
						var c = $(v.calendar.options.id).find(".filter-category .eo-cal-filter").val();
						var d = $(v.calendar.options.id).find(".filter-venue .eo-cal-filter").val();
						var tag = $(v.calendar.options.id).find(".filter-tag .eo-cal-filter").val();

						if (typeof c !== "undefined" && c !== "" && $.inarray(c, a.category) < 0 ) {
							return "<div></div>";
						}
						if (typeof d !== "undefined" && d !== "" && d != a.venue) {
							return "<div></div>";
						}

						if (typeof tag !== "undefined" && tag !== "" && $.inarray(tag, a.tags) < 0 ) {
							return "<div></div>";
						}

						if( !wp.hooks.applyfilters( 'eventorganiser.fullcalendar_render_event', true, a, b, v ) )
						return "<div></div>";

						if (! v.calendar.options.tooltip ) {
							return;
						}

						$(b).qtip({
							content: {
								text:  a.description,
								button: false,
								title: a.title
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
								effect: function (a) {$(this).fadeout("50");}
							},
							border: {
								radius: 4,
								width: 3
							},
							style: {
								classes: "eo-event-toolip ui-tooltip-shadow",
								widget: true,
								tip: "topmiddle"
							}
						});
					},
					buttontext: {
						today: 	eoajaxfront.locale.today,
						month: 	eoajaxfront.locale.month,
						week: 	eoajaxfront.locale.week,
						day: 	eoajaxfront.locale.day,
						cat: 	eoajaxfront.locale.cat,
						venue: 	eoajaxfront.locale.venue,
						tag: 	eoajaxfront.locale.tag
					},
					monthnames: eoajaxfront.locale.monthnames,
					monthnamesshort: eoajaxfront.locale.monthabbrev,
					daynames: eoajaxfront.locale.daynames,
					daynamesshort: eoajaxfront.locale.dayabbrev,
					eventcolor: "#21759b",
					defaultview: calendars[i].defaultview,
					lazyfetching: "true",
					events: 
					function (a, b, c, d) {
						var request = {
							start: jquery.fullcalendar.formatdate(a, "yyyy-mm-dd"),
							end: jquery.fullcalendar.formatdate(b, "yyyy-mm-dd"),
							timeformat:d.timeformatphp,
							users_events: d.users_events
						};

						if (typeof d.category !== "undefined" &&d.category !== "") {
							request.category = d.category;
						}
						if (typeof d.venue !== "undefined" &&d.venue !== "") {
							request.venue = d.venue;
						}

						request = wp.hooks.applyfilters( 'eventorganiser.fullcalendar_request', request, a, b, c, d );

						$.ajax({
							url: eventorganiser.ajaxurl + "?action=eventorganiser-fullcal",
							datatype: "json",
							data: request,
							success: c,
							complete: function( r, status ){
								if ( eo_script_debug ) {
									if( status == "error" ){

									}else if( status == "parsererror" ){
										if( window.console ){
											console.log( "response is not valid json. this is usually caused by error notices from wordpress or other plug-ins" ); 
											console.log( "response reads: " + r.responsetext );
										}
										alert( "an error has occurred in parsing the response. please inspect console log for details" );
									} 
								}
							}
						});
					},
					selectable: false,
					weekmode: "variable",
					aspectratio: 1.5,
					loading: 
					function (a) {
						var loading = $("#" + $(this).attr("id") + "_loading");
						if (a) {
							window.cleartimeout(loadingtimeout);
							loadingtimeout = window.settimeout(function () {loading.show();}, 1e3);
						} else {
							window.cleartimeout(loadingtimeout);
							loading.hide();
						}
					}
				};
				args = wp.hooks.applyfilters( 'eventorganiser.fullcalendar_options', args, calendars[i] );

				$(calendar).fullcalendar(args);
			}

			$(".eo-cal-filter").change(function () {
				$(".eo-fullcalendar").fullcalendar("rerenderevents");
			});

			$('.eo-mini-calendar').datepicker({
				dateformat: 'dd, d mm, yy',
				changemonth: true,
				changeyear: true,
				firstday: parseint( eventorganiser.fullcal.firstday, 10 ),
				buttontext: eoajaxfront.locale.gotodate,
				monthnamesshort: eoajaxfront.locale.monthabbrev,
				daynamesmin: eoajaxfront.locale.dayabbrev,
				nexttext: eoajaxfront.locale.nexttext,
				prevtext: eoajaxfront.locale.prevtext,
				showon: 'button',
				beforeshow: function(input, inst) {
					if( inst.hasownproperty( 'dpdiv' ) ){
						inst.dpdiv.addclass('eo-datepicker');
					}else{
						$('#ui-datepicker-div').addclass('eo-datepicker');
					}
				},
				onselect: function (datetext, dp) {
					var cal_id = $(this).parents('div.eo-fullcalendar').attr('id');
					$('#'+cal_id).fullcalendar('gotodate', new date(date.parse(datetext)));
				}
			});
		}

		if ($(".eo_widget_calendar").length > 0 ) {

			$(".eo_widget_calendar tfoot").unbind("click");
			$(".eo_widget_calendar").off("click").on("click", 'tfoot a', function (a) {
				a.preventdefault();
				var b = $(this).closest(".eo_widget_calendar").attr("id");

					//defaults
					var cal = {showpastevents: 1, 'show-long': 0, 'link-to-single': 0 };

				//shortcode widget calendar
				if( typeof eventorganiser.widget_calendars !== "undefined" && typeof eventorganiser.widget_calendars[b] !== "undefined" ){
					cal = eventorganiser.widget_calendars[b];	
				}
				//widget calendar
				if( typeof eo_widget_cal !== "undefined" && typeof eo_widget_cal[b] !== "undefined" ){
					cal = eo_widget_cal[b];
				}

				//set month
				cal.eo_month = eveorg_getparameterbyname("eo_month", $(this).attr("href"));

				$.getjson(eoajaxfront.adminajax + "?action=eo_widget_cal", cal,function (a) {$("#" + b + "_content").html(a);});
			});
		}

		if ($('.eo-agenda-widget').length > 0) {
			function eventorganisergetevents(a, b) {
				$.ajax({
					url: eoajaxfront.adminajax,
					datatype: "json",
					data: {
						action: "eo_widget_agenda",
						instance_number: b.number,
						direction: a,
						start: b.startdate,
						end: b.enddate
					},
					success: function (a) {
						if (!jquery.isarray(a) || !a[0]) {
							return false;
						} else {
							b.startdate = a[0].startdate;
							b.enddate = a[a.length - 1].startdate;
							populateagenda(a, b);
						}
					}
				});
			}
			function populateagenda(a, b) {
				var agendawidget = $("#" + b.id + "_container");
				var datelist = agendawidget.find("ul.dates");
				var dates = datelist.find("li");
				$(dates).remove();
				var current = false;
				for (i = 0; i < a.length; i++) {
					var d = new date(a[i].startdate),currentlist,c;

					if ( current === false || current != a[i].startdate && b.mode == "day" ) {
						current = a[i].startdate;
						currentlist = $('<li class="date" >' + a[i].display + '<ul class="a-date"></ul></li>');
						datelist.append(currentlist);
					}
					if( b.add_to_google ){
						c = $('<li class="event"></li>').append('<span class="cat"></span><span><strong>' + a[i].time + ": </strong></span>" + a[i]
						.post_title)
						.append('<div class="meta" style="display:none;"><span>' + a[i].link + "</span><span>   </span><span>" + a[i]
						.glink + "</span></div>");
					}else{
						c = $('<li class="event"></li>').append("<a class='eo-agenda-event-permalink' href='"+a[i].event_url+"'><span class='cat'></span><span><strong>" + a[i].time + ": </strong></span>" + a[i]
						.post_title+"</a>");
					}

					c.find("span.cat")
					.css({
						background: a[i].color
					});
					currentlist.append(c);
				}
				dates = datelist.find("li");
				var events_el = agendawidget.find("ul li.event");
				events_el.on("click", function () {
					$(this).find(".meta").toggle("400");
				});
			}
			for (var agenda in eo_widget_agenda) {
				agenda = eo_widget_agenda[agenda];
				var d = new date();
				agenda.startdate = $.fullcalendar.formatdate(d, "yyyy-mm-dd");
				agenda.enddate = agenda.startdate;
				eventorganisergetevents( 1, agenda );
			}
			$(".eo-agenda-widget .agenda-nav span.button").click(function (a) {
				var id = $(this).parents(".eo-agenda-widget").attr("id");
				agenda = eo_widget_agenda[id];
				a.preventdefault();
				var dir = false;
				if ($(this).hasclass("next")) {
					dir = "+1";
				} else if ($(this).hasclass("prev")) {
					dir = "-1";
				} else {
					var par = $(this).parent();
					if (par.hasclass("prev")) {
						dir = "-1";
					} else {
						dir = "+1";
					}
				}
				eventorganisergetevents( dir, agenda );
			});
		}
	});
})(jquery);

function eveorg_getparameterbyname(a, b) {
	a = a.replace(/[\[]/, "\\[")
	.replace(/[\]]/, "\\]");
	var c = "[\\?&]" + a + "=([^&#]*)";
	var d = new regexp(c);
	var e = d.exec(b);
	if (e === null) return "";
	else return decodeuricomponent(e[1].replace(/\+/g, " "));
}

function eo_load_map() {
	var maps = eventorganiser.map;

	for (var i = 0; i < maps.length; i++) {

		if ( null === document.getelementbyid( "eo_venue_map-" + (i + 1) ) )
		continue;

		//store markers
		eventorganiser.map[i].markers = {};
		var locations = maps[i].locations;
		var b = {
			zoom: maps[i].zoom,
			scrollwheel: maps[i].scrollwheel,
			zoomcontrol: maps[i].zoomcontrol,
			rotatecontrol: maps[i].rotatecontrol,
			pancontrol: maps[i].pancontrol,
			overviewmapcontrol: maps[i].overviewmapcontrol,
			streetviewcontrol: maps[i].streetviewcontrol,
			draggable: maps[i].draggable,
			maptypecontrol: maps[i].maptypecontrol,
			maptypeid: google.maps.maptypeid[maps[i].maptypeid]
		};
		b = wp.hooks.applyfilters( 'eventorganiser.google_map_options', b );
		var map = new google.maps.map(document.getelementbyid("eo_venue_map-" + (i + 1)), b);

		//  create a new viewpoint bound
		var bounds = new google.maps.latlngbounds();

		var latlnglist = [];
		for( var j=0; j<locations.length; j++){
			var lat = locations[j].lat;
			var lng = locations[j].lng;
			if (lat !== undefined && lng !== undefined) {
				latlnglist.push(new google.maps.latlng(lat, lng));
				bounds.extend (latlnglist[j]);

				var marker_options = {
					venue_id: locations[j].venue_id,
					position: latlnglist[j],
					map: map,
					content:locations[j].tooltipcontent,
					icon: locations[j].icon
				};

				marker_options = wp.hooks.applyfilters( 'eventorganiser.venue_marker_options', marker_options );
				var c = new google.maps.marker(marker_options);				
				eventorganiser.map[i].markers[locations[j].venue_id] = c;

				if( maps[i].tooltip ){
					google.maps.event.addlistener(c, 'click',eventorganiser_venue_tooltip);
				}
			}
		}

		if( locations.length > 1 ){	
			//  fit these bounds to the map
			map.fitbounds (bounds);
			//google.maps.event.addlisteneronce(map, 'zoom_changed', function() {map.setzoom(zoom);});
		}else{
			map.setcenter ( latlnglist[0]);
		}

	}//foreach map
}
/**
* @constructor
*/
function eventorganiser_venue_tooltip() {

	// grab marker position: convert world point into pixel point
	var map = this.getmap();
	var pixel = this.getmap().getprojection().fromlatlngtopoint(this.position);
	var topright=map.getprojection().fromlatlngtopoint(map.getbounds().getnortheast()); 
	var bottomleft=map.getprojection().fromlatlngtopoint(map.getbounds().getsouthwest()); 
	var scale=math.pow(2,map.getzoom()); 
	pixel=  new google.maps.point((pixel.x- bottomleft.x)*scale,(pixel.y-topright.y)*scale);

	wp.hooks.doaction( 'eventorganiser.venue_marker_clicked', this );

	//var pixel = latlngtopixel.fromlatlngtocontainerpixel(this.position);
	var pos = [ pixel.x, pixel.y ];

	if(this.tooltip){
		this.tooltip.qtip('api').set('position.target', pos);
		this.tooltip.qtip('show');
		return;
	}
	jquery(this.getmap().getdiv()).css({overflow: 'visible'});

	// create the tooltip on a dummy div and store it on the marker
	this.tooltip =jquery('<div />').qtip({
		content: {
			text: this.content
		},
		border: {
			radius: 4,
			width: 3
		},
		style: {
			classes: "ui-tooltip-shadow",
			widget: true
		},
		position: {
			at: "right center",
			my: "top center",
			target: pos,
			container: jquery(this.getmap().getdiv())
		},
		show: {
			ready: true,
			event: false,
			solo: true
		},
		hide: {
			event: 'mouseleave unfocus'
		}
	});
}
