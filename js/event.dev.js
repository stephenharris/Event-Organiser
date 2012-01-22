(function( $ ) {
$(document).ready(function() {
//Venue picker - combobox
		$.widget( "ui.combobox", {
			_create: function() {
				var self = this,
					select = this.element.hide(),
					selected = select.children( ":selected" ),
					value = selected.val() ? selected.text() : "";
				var input = this.input = $( "<input>" )
					.insertAfter( select )
					.val( value )
					.autocomplete({
						delay: 0,
						minLength: 0,
						source: function(req, response){  
						$.getJSON(EO_Ajax_Event.ajaxurl+"?callback=?&action=eo-search-venue", req, function(data) {  
							response( $.map( data, function( item ) {
								item.label = item.venue_name;			
								return item;
							}));
                				});  
					},
					select: function(event, ui) {
						if($("tr.venue_row.novenue").length >0){
							$("tr.venue_row.novenue").show();
							$("#hws_vm_name").html(ui.item.venue_name);
							$("#hws_vm_addr").html(ui.item.venue_address);
							$("#hws_vm_postal").html(ui.item.venue_postCode);
							$("#hws_vm_country").html(ui.item.venue_country);
							initialize(ui.item.venue_lat,ui.item.venue_lng);
						}
							$("#venue_select").removeAttr("selected");
							$("#venue_select").val(ui.item.venue_id);
					}
					})
					.addClass( "ui-widget-content ui-corner-left" );


				input.data( "autocomplete" )._renderItem = function( ul, item ) {
					return $( "<li></li>" )
						.data( "item.autocomplete", item )
						.append( "<a>" + item.label +"</br> <span style='font-size: 0.8em'><em>"+item.venue_address+", "+item.venue_postal+", "+item.venue_country+"</span></em></a>" )
						.appendTo( ul );
				};

				this.button = $( "<button type='button'>&nbsp;</button>" )
					.attr( "tabIndex", -1 )
					.attr( "title", "Show All Items" )
					.insertAfter( input )
					.button({
						icons: {primary: "ui-icon-triangle-1-s"},
						text: false
					})
					.removeClass( "ui-corner-all" )
					.addClass( "ui-corner-right ui-button-icon" )
					.click(function() {
						// close if already visible
						if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
							input.autocomplete( "close" );
							return;
						}

						// work around a bug (likely same cause as #5265)
						$( this ).blur();

						// pass empty string as value to search for, displaying all results
						input.autocomplete( "search", "" );
						input.focus();
					});
			},
		});


	//Venue selection
	$( "#venue_select" ).combobox();


	 //Date and time selection
	if( $("#eventorganiser_detail #from_date, #eventorganiser_detail #to_date" ).length>0){
	var dates = $("#eventorganiser_detail #from_date, #eventorganiser_detail #to_date" ).datepicker({
			dateFormat: EO_Ajax_Event.format,
			changeMonth: true,
			changeYear: true,
			firstDay:  parseInt(EO_Ajax_Event.startday),
			buttonImage: 'images/ui-icon-calendar.png',
			buttonImageOnly: true,
			onSelect: function( selectedDate ) {
				var option = this.id == "from_date" ? "minDate" : "maxDate",
					instance = $( this ).data( "datepicker" ),
					date = $.datepicker.parseDate(
						instance.settings.dateFormat ||
						$.datepicker._defaults.dateFormat,
						selectedDate, instance.settings );
				dates.not( this ).datepicker( "option", option, date );
				if( this.id == "from_date"){
					$( "#recend").datepicker( "option", "minDate", date );
				}
				eo_update_event_form()
			}
		});

	$( "#recend").datepicker({
		dateFormat: EO_Ajax_Event.format,
		changeMonth: true,
		changeYear: true,
		firstDay:  parseInt(EO_Ajax_Event.startday),
	});

	$('#HWSEvent_time, #HWSEvent_time2').timepicker({
		showPeriodLabels: false,
	});

	//Produce summary of event reoccurrence
	eo_produce_summary();

	//Hide the options for week / month unless it is needed.
	if($("#HWSEventInput_Req").val()!='weekly'){
		$("#dayofweekrepeat").hide();
	}	
	if($("#HWSEventInput_Req").val()!='monthly'){
		$("#dayofmonthrepeat").hide();
	}
	$("tr.venue_row.novenue").hide();
	$(".onetime .reocurrence_row").hide();;


	//When checked, a user wants to edit a reoccurring event.
	$("#HWSEvent_rec").click(function(){
		var bool = !$(this).prop("checked");
		$(".reoccurence .event-date :input").attr('disabled', bool);
		$(".reoccurence .event-date :input").toggleClass('ui-state-disabled', bool);
	});

	//When any input is altered. Update the form.
	$(".reoccurence .event-date :input, .onetime .event-date :input").change(function(){
		eo_update_event_form();
	});
	
	}
});

function eo_produce_summary(){
	
		//If single occurrence
		if($("#HWSEventInput_Req").val()=='once'){
			$("#event_summary").html('This event will be a one-time event');
			return;
		}
	
		var fromdate = $("#from_date").datepicker("getDate");
		var weekdays=new Array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
		var ical_weekdays=new Array("SU","MO","TU","WE","TH","FR","SA");

		//Get reoccurrence and frequency
		reoccurrence =$("#HWSEventInput_Req :selected").text();
		frequency =$("#HWSEvent_freq").val();
		
		if(frequency>1){
			s="s";
			summary = "This event will repeat every "+frequency+" ";
		}else{
			s="";
			summary = "This event will repeat every ";
		}


		switch($("#HWSEventInput_Req").val()){
			case 'daily':
				summary=summary+'day'+s;
				break;
			case 'weekly':
				summary=summary+'week'+s;
				selected = $("#dayofweekrepeat :checkbox:checked");
	
				if(selected.length==0){
					day =fromdate.getDay();
					$("#dayofweekrepeat :checkbox[value='"+ical_weekdays[day]+"']").attr('checked',true);
				}
				selected = $("#dayofweekrepeat :checkbox:checked");
		
				selected.each(function(index){
					if(index==0)summary = summary+" on "+weekdays[ical_weekdays.indexOf($(this).val())];
					if(index>0)summary = summary+", "+weekdays[ical_weekdays.indexOf($(this).val())];
				});
				break;

			case 'monthly':
				summary=summary+'month'+s;
				//Show & enable reoccurrence forms and month meta. Disable & hide week meta 
				if($("#dayofmonthrepeat :radio:checked").val()=='BYMONTHDAY='){
					summary = summary+" on the "+fromdate.getDate()+eo_date_suffix(fromdate);
				}else{
					day =fromdate.getDay()%7;
					n = parseInt(Math.floor((fromdate.getDate()-1)/7));
					occurrence = new Array("first","second","third","fourth","last")
					summary = summary+" on the "+occurrence[n]+" "+weekdays[day];
				}
				break;

			case 'yearly':
				summary=summary+'year'+s;
				//Show & enable reoccurrence forms. Disable & hide week & month meta 
				summary = summary+" on "+$.datepicker.formatDate('MM d', fromdate)+eo_date_suffix(fromdate);
				break;
		}

		//Add 'until' to summary if the schedule's end is entered
		var schedule_end = $("#recend").datepicker("getDate");
		if(schedule_end!= null){			
			summary = summary+" until "+ $.datepicker.formatDate("MM d'"+eo_date_suffix(schedule_end)+"' yy", schedule_end);
		}

		//Display summary
		$("#event_summary").html(summary);		
	};



function eo_update_event_form(){
		speed = 700;
	
		//If all day, disable times		
		var bool = !$("#eo_allday:checkbox").attr('checked');
		$(".eo_time").attr('disabled', !bool);
		$(".eo_time").toggleClass('ui-state-disabled', !bool);

		/*
		* Decide what forms to show depending on selected schedule
		*/
		switch($("#HWSEventInput_Req").val()){
			case 'once':
				//Hide & disable everything (except daysofweek & dayofmonth - this sit inside a hidden row)
				$('#HWSEvent_freq').val('1');
				$(".reocurrence_row").hide();
				$("#dayofweekrepeat").show();
				$("#dayofmonthrepeat").show();
				$(".reocurrence_row").attr('disabled', true);
				break;

			case 'weekly':
				//Show & enable reoccurrence forms and week metaa. Disable & hide month meta 
				$(".reocurrence_row :input").attr('disabled', false);
				$("#recpan").text('week');
				$(".reocurrence_row").fadeIn(speed);
				$("#dayofweekrepeat").fadeIn(speed);
				$("#dayofweekrepeat :input").attr('disabled', false);
				$( "#dayofweekrepeat" ).buttonset('enable');
				$("#dayofmonthrepeat").hide();
				$("#dayofmonthrepeat :radio").attr('disabled', true);
				break;

			case 'monthly':
				//Show & enable reoccurrence forms and month meta. Disable & hide week meta 
				$(".reocurrence_row :input").attr('disabled', false);
				$("#recpan").text('month');
				$(".reocurrence_row").fadeIn(speed);
				$("#dayofmonthrepeat").fadeIn(speed);
				$("#dayofmonthrepeat :input").attr('disabled', false);
				$("#dayofweekrepeat").hide();
				$("#dayofweekrepeat :input").attr('disabled', true);
				break;

			case 'daily':
				//Show & enable reoccurrence forms. Disable & hide week & month meta 
				$(".reocurrence_row :input").attr('disabled', false);
				$(".reocurrence_row").fadeIn(speed);
				$("#recpan").text('day');
				$("#dayofweekrepeat").hide();
				$("#dayofweekrepeat :input").attr('disabled', true);
				$("#dayofmonthrepeat").hide();
				$("#dayofmonthrepeat :radio").attr('disabled', true);
				break;

			case 'yearly':
				//Show & enable reoccurrence forms. Disable & hide week & month meta 
				$(".reocurrence_row :input").attr('disabled', false);
				$(".reocurrence_row").fadeIn(speed);
				$("#recpan").text('year');
				$("#dayofweekrepeat").hide();
				$("#dayofweekrepeat :input").attr('disabled', true);
				$("#dayofmonthrepeat").hide();
				$("#dayofmonthrepeat :radio").attr('disabled', true);
				break;
		}

		//This adds and 's' to the schedule summary (to make it plural)
		if($("#HWSEvent_freq").val() >1){
			$("#recpan").text($("#recpan").text()+'s');
		}

		/*
		* Form updated, now produce a reoccurrence summary
		*/
		eo_produce_summary();
	};



	/*
	* Takes a date object and returns it's suffix
	*/
	function eo_date_suffix(date){
		var suffix = ["th", "st", "nd", "rd"];
		if (3<date.getDate() && date.getDate()<20){
			var s=0;
		}else{
			var s = Math.min(date.getDate()%10,4)%4;
		}
		return suffix[s];
	}


	})( jQuery );
/**
 * Timepicker. Not made by me. 
* Copyright info below
 *
 * @since 1.0.0
*/

/*
 * jQuery UI Timepicker 0.2.5
 *
 * Copyright 2010-2011, Francois Gelinas
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://fgelinas.com/code/timepicker
 *
 * Depends:
 *	jquery.ui.core.js
 *  jquery.ui.position.js (only if position settngs are used)
*/
(function (jQuery, undefined) {

    jQuery.extend(jQuery.ui, { timepicker: { version: "0.2.5"} });

    var PROP_NAME = 'timepicker';
    var tpuuid = new Date().getTime();

    /* Time picker manager.
    Use the singleton instance of this class, jQuery.timepicker, to interact with the time picker.
    Settings for (groups of) time pickers are maintained in an instance object,
    allowing multiple different settings on the same page. */

    function Timepicker() {
        this.debug = true; // Change this to true to start debugging
        this._curInst = null; // The current instance in use
        this._isInline = false; // true if the instance is displayed inline
        this._disabledInputs = []; // List of time picker inputs that have been disabled
        this._timepickerShowing = false; // True if the popup picker is showing , false if not
        this._inDialog = false; // True if showing within a "dialog", false if not
        this._dialogClass = 'ui-timepicker-dialog'; // The name of the dialog marker class
        this._mainDivId = 'ui-timepicker-div'; // The ID of the main timepicker division
        this._inlineClass = 'ui-timepicker-inline'; // The name of the inline marker class
        this._currentClass = 'ui-timepicker-current'; // The name of the current hour / minutes marker class
        this._dayOverClass = 'ui-timepicker-days-cell-over'; // The name of the day hover marker class

        this.regional = []; // Available regional settings, indexed by language code
        this.regional[''] = { // Default regional settings
            hourText: 'Hour', // Display text for hours section
            minuteText: 'Minute', // Display text for minutes link
            amPmText: ['AM', 'PM'] // Display text for AM PM
        };
        this._defaults = { // Global defaults for all the time picker instances
            showOn: 'focus',    // 'focus' for popup on focus,
                                // 'button' for trigger button, or 'both' for either (not yet implemented)
            button: null,                   // 'button' element that will trigger the timepicker
            showAnim: 'fadeIn',             // Name of jQuery animation for popup
            showOptions: {},                // Options for enhanced animations
            appendText: '',                 // Display text following the input box, e.g. showing the format

            beforeShow: null,               // Define a callback function executed before the timepicker is shown
            onSelect: null,                 // Define a callback function when a hour / minutes is selected
            onClose: null,                  // Define a callback function when the timepicker is closed

            timeSeparator: ':',             // The character to use to separate hours and minutes.
            periodSeparator: ' ',           // The character to use to separate the time from the time period.
            showPeriod: false,              // Define whether or not to show AM/PM with selected time
            showPeriodLabels: true,         // Show the AM/PM labels on the left of the time picker
            showLeadingZero: true,          // Define whether or not to show a leading zero for hours < 10. [true/false]
            showMinutesLeadingZero: true,   // Define whether or not to show a leading zero for minutes < 10.
            altField: '',                   // Selector for an alternate field to store selected time into
            defaultTime: 'now',             // Used as default time when input field is empty or for inline timePicker
                                            // (set to 'now' for the current time, '' for no highlighted time)
            myPosition: 'left top',         // Position of the dialog relative to the input.
                                            // see the position utility for more info : http://jqueryui.com/demos/position/
            atPosition: 'left bottom',      // Position of the input element to match
                                            // Note : if the position utility is not loaded, the timepicker will attach left top to left bottom
            //NEW: 2011-02-03
            onHourShow: null,			    // callback for enabling / disabling on selectable hours  ex : function(hour) { return true; }
            onMinuteShow: null,             // callback for enabling / disabling on time selection  ex : function(hour,minute) { return true; }
            // 2011-03-22 - v 0.0.9
            zIndex: null,                   // specify zIndex

            hours: {
                starts: 0,                  // first displayed hour
                ends: 23                    // last displayed hour
            },
            minutes: {
                starts: 0,                  // first displayed minute
                ends: 55,                   // last displayed minute
                interval: 5                 // interval of displayed minutes
            },
            rows: 4,                        // number of rows for the input tables, minimum 2, makes more sense if you use multiple of 2
            // 2011-08-05 0.2.4
            showHours: true,                // display the hours section of the dialog
            showMinutes: true               // display the minute section of the dialog

        };
        jQuery.extend(this._defaults, this.regional['']);

        this.tpDiv = jQuery('<div id="' + this._mainDivId + '" class="ui-timepicker ui-widget ui-helper-clearfix ui-corner-all " style="display: none"></div>');
    }

    jQuery.extend(Timepicker.prototype, {
        /* Class name added to elements to indicate already configured with a time picker. */
        markerClassName: 'hasTimepicker',

        /* Debug logging (if enabled). */
        log: function () {
            if (this.debug)
                console.log.apply('', arguments);
        },

        // TODO rename to "widget" when switching to widget factory
        _widgetTimepicker: function () {
            return this.tpDiv;
        },

        /* Override the default settings for all instances of the time picker.
        @param  settings  object - the new settings to use as defaults (anonymous object)
        @return the manager object */
        setDefaults: function (settings) {
            extendRemove(this._defaults, settings || {});
            return this;
        },

        /* Attach the time picker to a jQuery selection.
        @param  target    element - the target input field or division or span
        @param  settings  object - the new settings to use for this time picker instance (anonymous) */
        _attachTimepicker: function (target, settings) {
            // check for settings on the control itself - in namespace 'time:'
            var inlineSettings = null;
            for (var attrName in this._defaults) {
                var attrValue = target.getAttribute('time:' + attrName);
                if (attrValue) {
                    inlineSettings = inlineSettings || {};
                    try {
                        inlineSettings[attrName] = eval(attrValue);
                    } catch (err) {
                        inlineSettings[attrName] = attrValue;
                    }
                }
            }
            var nodeName = target.nodeName.toLowerCase();
            var inline = (nodeName == 'div' || nodeName == 'span');

            if (!target.id) {
                this.uuid += 1;
                target.id = 'tp' + this.uuid;
            }
            var inst = this._newInst(jQuery(target), inline);
            inst.settings = jQuery.extend({}, settings || {}, inlineSettings || {});
            if (nodeName == 'input') {
                this._connectTimepicker(target, inst);
                // init inst.hours and inst.minutes from the input value
                this._setTimeFromField(inst);
            } else if (inline) {
                this._inlineTimepicker(target, inst);
            }


        },

        /* Create a new instance object. */
        _newInst: function (target, inline) {
            var id = target[0].id.replace(/([^A-Za-z0-9_-])/g, '\\\\jQuery1'); // escape jQuery meta chars
            return {
                id: id, input: target, // associated target
                inline: inline, // is timepicker inline or not :
                tpDiv: (!inline ? this.tpDiv : // presentation div
                    jQuery('<div class="' + this._inlineClass + ' ui-timepicker ui-widget  ui-helper-clearfix"></div>'))
            };
        },

        /* Attach the time picker to an input field. */
        _connectTimepicker: function (target, inst) {
            var input = jQuery(target);
            inst.append = jQuery([]);
            inst.trigger = jQuery([]);
            if (input.hasClass(this.markerClassName)) { return; }
            this._attachments(input, inst);
            input.addClass(this.markerClassName).
                keydown(this._doKeyDown).
                keyup(this._doKeyUp).
                bind("setData.timepicker", function (event, key, value) {
                    inst.settings[key] = value;
                }).
                bind("getData.timepicker", function (event, key) {
                    return this._get(inst, key);
                });
            jQuery.data(target, PROP_NAME, inst);
        },

        /* Handle keystrokes. */
        _doKeyDown: function (event) {
            var inst = jQuery.timepicker._getInst(event.target);
            var handled = true;
            inst._keyEvent = true;
            if (jQuery.timepicker._timepickerShowing) {
                switch (event.keyCode) {
                    case 9: jQuery.timepicker._hideTimepicker();
                        handled = false;
                        break; // hide on tab out
                    case 13:
                        jQuery.timepicker._updateSelectedValue(inst);
                        jQuery.timepicker._hideTimepicker();
                            
						return false; // don't submit the form
						break; // select the value on enter
                    case 27: jQuery.timepicker._hideTimepicker();
                        break; // hide on escape
                    default: handled = false;
                }
            }
            else if (event.keyCode == 36 && event.ctrlKey) { // display the time picker on ctrl+home
                jQuery.timepicker._showTimepicker(this);
            }
            else {
                handled = false;
            }
            if (handled) {
                event.preventDefault();
                event.stopPropagation();
            }
        },

        /* Update selected time on keyUp */
        /* Added verion 0.0.5 */
        _doKeyUp: function (event) {
            var inst = jQuery.timepicker._getInst(event.target);
            jQuery.timepicker._setTimeFromField(inst);
            jQuery.timepicker._updateTimepicker(inst);
        },

        /* Make attachments based on settings. */
        _attachments: function (input, inst) {
            var appendText = this._get(inst, 'appendText');
            var isRTL = this._get(inst, 'isRTL');
            if (inst.append) { inst.append.remove(); }
            if (appendText) {
                inst.append = jQuery('<span class="' + this._appendClass + '">' + appendText + '</span>');
                input[isRTL ? 'before' : 'after'](inst.append);
            }
            input.unbind('focus.timepicker', this._showTimepicker);
            if (inst.trigger) { inst.trigger.remove(); }

            var showOn = this._get(inst, 'showOn');
            if (showOn == 'focus' || showOn == 'both') { // pop-up time picker when in the marked field
                input.bind("focus.timepicker", this._showTimepicker);
            }
            if (showOn == 'button' || showOn == 'both') { // pop-up time picker when 'button' element is clicked
                var button = this._get(inst, 'button');
                jQuery(button).bind("click.timepicker", function () {
                    if (jQuery.timepicker._timepickerShowing && jQuery.timepicker._lastInput == input[0]) { jQuery.timepicker._hideTimepicker(); }
                    else { jQuery.timepicker._showTimepicker(input[0]); }
                    return false;
                });

            }
        },


        /* Attach an inline time picker to a div. */
        _inlineTimepicker: function(target, inst) {
            var divSpan = jQuery(target);
            if (divSpan.hasClass(this.markerClassName))
                return;
            divSpan.addClass(this.markerClassName).append(inst.tpDiv).
                bind("setData.timepicker", function(event, key, value){
                    inst.settings[key] = value;
                }).bind("getData.timepicker", function(event, key){
                    return this._get(inst, key);
                });
            jQuery.data(target, PROP_NAME, inst);

            this._setTimeFromField(inst);
            this._updateTimepicker(inst);
            inst.tpDiv.show();
        },

        /* Pop-up the time picker for a given input field.
        @param  input  element - the input field attached to the time picker or
        event - if triggered by focus */
        _showTimepicker: function (input) {
            input = input.target || input;
            if (input.nodeName.toLowerCase() != 'input') { input = jQuery('input', input.parentNode)[0]; } // find from button/image trigger
            if (jQuery.timepicker._isDisabledTimepicker(input) || jQuery.timepicker._lastInput == input) { return; } // already here

            // fix v 0.0.8 - close current timepicker before showing another one
            jQuery.timepicker._hideTimepicker();

            var inst = jQuery.timepicker._getInst(input);
            if (jQuery.timepicker._curInst && jQuery.timepicker._curInst != inst) {
                jQuery.timepicker._curInst.tpDiv.stop(true, true);
            }
            var beforeShow = jQuery.timepicker._get(inst, 'beforeShow');
            extendRemove(inst.settings, (beforeShow ? beforeShow.apply(input, [input, inst]) : {}));
            inst.lastVal = null;
            jQuery.timepicker._lastInput = input;

            jQuery.timepicker._setTimeFromField(inst);

            // calculate default position
            if (jQuery.timepicker._inDialog) { input.value = ''; } // hide cursor
            if (!jQuery.timepicker._pos) { // position below input
                jQuery.timepicker._pos = jQuery.timepicker._findPos(input);
                jQuery.timepicker._pos[1] += input.offsetHeight; // add the height
            }
            var isFixed = false;
            jQuery(input).parents().each(function () {
                isFixed |= jQuery(this).css('position') == 'fixed';
                return !isFixed;
            });
            if (isFixed && jQuery.browser.opera) { // correction for Opera when fixed and scrolled
                jQuery.timepicker._pos[0] -= document.documentElement.scrollLeft;
                jQuery.timepicker._pos[1] -= document.documentElement.scrollTop;
            }

            var offset = { left: jQuery.timepicker._pos[0], top: jQuery.timepicker._pos[1] };

            jQuery.timepicker._pos = null;
            // determine sizing offscreen
            inst.tpDiv.css({ position: 'absolute', display: 'block', top: '-1000px' });
            jQuery.timepicker._updateTimepicker(inst);


            // position with the ui position utility, if loaded
            if ( ( ! inst.inline )  && ( typeof jQuery.ui.position == 'object' ) ) {
                inst.tpDiv.position({
                    of: inst.input,
                    my: jQuery.timepicker._get( inst, 'myPosition' ),
                    at: jQuery.timepicker._get( inst, 'atPosition' ),
                    // offset: jQuery( "#offset" ).val(),
                    // using: using,
                    collision: 'flip'
                });
                var offset = inst.tpDiv.offset();
                jQuery.timepicker._pos = [offset.top, offset.left];
            }


            // reset clicked state
            inst._hoursClicked = false;
            inst._minutesClicked = false;

            // fix width for dynamic number of time pickers
            // and adjust position before showing
            offset = jQuery.timepicker._checkOffset(inst, offset, isFixed);
            inst.tpDiv.css({ position: (jQuery.timepicker._inDialog && jQuery.blockUI ?
			    'static' : (isFixed ? 'fixed' : 'absolute')), display: 'none',
                left: offset.left + 'px', top: offset.top + 'px'
            });
            if ( ! inst.inline ) {
                var showAnim = jQuery.timepicker._get(inst, 'showAnim');
                var duration = jQuery.timepicker._get(inst, 'duration');
                var zIndex = jQuery.timepicker._get(inst, 'zIndex');
                var postProcess = function () {
                    jQuery.timepicker._timepickerShowing = true;
                    var borders = jQuery.timepicker._getBorders(inst.tpDiv);
                    inst.tpDiv.find('iframe.ui-timepicker-cover'). // IE6- only
					css({ left: -borders[0], top: -borders[1],
					    width: inst.tpDiv.outerWidth(), height: inst.tpDiv.outerHeight()
					});
                };

                // if not zIndex specified in options, use target zIndex + 1
                if ( ! zIndex) {
                    zIndex = jQuery(input).attr('zIndex') + 1;
                }
                inst.tpDiv.attr('zIndex', zIndex);
                inst.tpDiv.css('zIndex', zIndex);

                if (jQuery.effects && jQuery.effects[showAnim]) {
                    inst.tpDiv.show(showAnim, jQuery.timepicker._get(inst, 'showOptions'), duration, postProcess);
                }
                else {
                    inst.tpDiv[showAnim || 'show']((showAnim ? duration : null), postProcess);
                }
                if (!showAnim || !duration) { postProcess(); }
                if (inst.input.is(':visible') && !inst.input.is(':disabled')) { inst.input.focus(); }
                jQuery.timepicker._curInst = inst;
            }
        },

        /* Generate the time picker content. */
        _updateTimepicker: function (inst) {
            inst.tpDiv.empty().append(this._generateHTML(inst));
            this._rebindDialogEvents(inst);

        },

        _rebindDialogEvents: function (inst) {
            var borders = jQuery.timepicker._getBorders(inst.tpDiv),
                self = this;
            inst.tpDiv
			.find('iframe.ui-timepicker-cover') // IE6- only
				.css({ left: -borders[0], top: -borders[1],
				    width: inst.tpDiv.outerWidth(), height: inst.tpDiv.outerHeight()
				})
			.end()
            // after the picker html is appended bind the click & double click events (faster in IE this way
            // then letting the browser interpret the inline events)
            // the binding for the minute cells also exists in _updateMinuteDisplay
            .find('.ui-timepicker-minute-cell')
                .bind("click", { fromDoubleClick:false }, jQuery.proxy(jQuery.timepicker.selectMinutes, this))
                .bind("dblclick", { fromDoubleClick:true }, jQuery.proxy(jQuery.timepicker.selectMinutes, this))
            .end()
            .find('.ui-timepicker-hour-cell')
                .bind("click", { fromDoubleClick:false }, jQuery.proxy(jQuery.timepicker.selectHours, this))
                .bind("dblclick", { fromDoubleClick:true }, jQuery.proxy(jQuery.timepicker.selectHours, this))
            .end()
			.find('.ui-timepicker td a')
				.bind('mouseout', function () {
				    jQuery(this).removeClass('ui-state-hover');
				    if (this.className.indexOf('ui-timepicker-prev') != -1) jQuery(this).removeClass('ui-timepicker-prev-hover');
				    if (this.className.indexOf('ui-timepicker-next') != -1) jQuery(this).removeClass('ui-timepicker-next-hover');
				})
				.bind('mouseover', function () {
				    if ( ! self._isDisabledTimepicker(inst.inline ? inst.tpDiv.parent()[0] : inst.input[0])) {
				        jQuery(this).parents('.ui-timepicker-calendar').find('a').removeClass('ui-state-hover');
				        jQuery(this).addClass('ui-state-hover');
				        if (this.className.indexOf('ui-timepicker-prev') != -1) jQuery(this).addClass('ui-timepicker-prev-hover');
				        if (this.className.indexOf('ui-timepicker-next') != -1) jQuery(this).addClass('ui-timepicker-next-hover');
				    }
				})
			.end()
			.find('.' + this._dayOverClass + ' a')
				.trigger('mouseover')
			.end();
        },

        /* Generate the HTML for the current state of the date picker. */
        _generateHTML: function (inst) {

            var h, m, row, col, html, hoursHtml, minutesHtml = '',
                showPeriod = (this._get(inst, 'showPeriod') == true),
                showPeriodLabels = (this._get(inst, 'showPeriodLabels') == true),
                showLeadingZero = (this._get(inst, 'showLeadingZero') == true),
                showHours = (this._get(inst, 'showHours') == true),
                showMinutes = (this._get(inst, 'showMinutes') == true),
                amPmText = this._get(inst, 'amPmText'),
                rows = this._get(inst, 'rows'),
                amRows = 0,
                pmRows = 0,
                amItems = 0,
                pmItems = 0,
                amFirstRow = 0,
                pmFirstRow = 0,
                hours = Array(),
                hours_options = this._get(inst, 'hours'),
                hoursPerRow = null,
                hourCounter = 0,
                hourLabel = this._get(inst, 'hourText');



            // prepare all hours and minutes, makes it easier to distribute by rows
            for (h = hours_options.starts; h <= hours_options.ends; h++) {
                hours.push (h);
            }
            hoursPerRow = Math.ceil(hours.length / rows); // always round up

            if (showPeriodLabels) {
                for (hourCounter = 0; hourCounter < hours.length; hourCounter++) {
                    if (hours[hourCounter] < 12) {
                        amItems++;
                    }
                    else {
                        pmItems++;
                    }
                }
                hourCounter = 0; 

                amRows = Math.floor(amItems / hours.length * rows);
                pmRows = Math.floor(pmItems / hours.length * rows);

                // assign the extra row to the period that is more densly populated
                if (rows != amRows + pmRows) {
                    // Make sure: AM Has Items and either PM Does Not, AM has no rows yet, or AM is more dense
                    if (amItems && (!pmItems || !amRows || (pmRows && amItems / amRows >= pmItems / pmRows))) {
                        amRows++;
                    } else {
                        pmRows++;
                    }
                }
                amFirstRow = Math.min(amRows, 1);
                pmFirstRow = amRows + 1;
                hoursPerRow = Math.ceil(Math.max(amItems / amRows, pmItems / pmRows));
            }


            html = '<table class="ui-timepicker-table ui-widget-content ui-corner-all"><tr>';

            if (showHours) {

                html += '<td class="ui-timepicker-hours">' +
                        '<div class="ui-timepicker-title ui-widget-header ui-helper-clearfix ui-corner-all">' +
                        hourLabel +
                        '</div>' +
                        '<table class="ui-timepicker">';

                for (row = 1; row <= rows; row++) {
                    html += '<tr>';
                    // AM
                    if (row == amFirstRow && showPeriodLabels) {
                        html += '<th rowspan="' + amRows.toString() + '" class="periods" scope="row">' + amPmText[0] + '</th>';
                    }
                    // PM
                    if (row == pmFirstRow && showPeriodLabels) {
                        html += '<th rowspan="' + pmRows.toString() + '" class="periods" scope="row">' + amPmText[1] + '</th>';
                    }
                    for (col = 1; col <= hoursPerRow; col++) {
                        if (showPeriodLabels && row < pmFirstRow && hours[hourCounter] >= 12) {
                            html += this._generateHTMLHourCell(inst, undefined, showPeriod, showLeadingZero);
                        } else {
                            html += this._generateHTMLHourCell(inst, hours[hourCounter], showPeriod, showLeadingZero);
                            hourCounter++;
                        }
                    }
                    html += '</tr>';
                }
                html += '</tr></table>' + // Close the hours cells table
                        '</td>';          // Close the Hour td
            }

            if (showMinutes) {
                html += '<td class="ui-timepicker-minutes">';
                html += this._generateHTMLMinutes(inst);
                html += '</td>';
            }
            
            html += '</tr></table>';

             /* IE6 IFRAME FIX (taken from datepicker 1.5.3, fixed in 0.1.2 */
            html += (jQuery.browser.msie && parseInt(jQuery.browser.version,10) < 7 && !inst.inline ?
                '<iframe src="javascript:false;" class="ui-timepicker-cover" frameborder="0"></iframe>' : '');

            return html;
        },

        /* Special function that update the minutes selection in currently visible timepicker
         * called on hour selection when onMinuteShow is defined  */
        _updateMinuteDisplay: function (inst) {
            var newHtml = this._generateHTMLMinutes(inst);
            inst.tpDiv.find('td.ui-timepicker-minutes').html(newHtml);
            this._rebindDialogEvents(inst);
                // after the picker html is appended bind the click & double click events (faster in IE this way
                // then letting the browser interpret the inline events)
                // yes I know, duplicate code, sorry
/*                .find('.ui-timepicker-minute-cell')
                    .bind("click", { fromDoubleClick:false }, jQuery.proxy(jQuery.timepicker.selectMinutes, this))
                    .bind("dblclick", { fromDoubleClick:true }, jQuery.proxy(jQuery.timepicker.selectMinutes, this));
*/

        },

        /*
         * Generate the minutes table
         * This is separated from the _generateHTML function because is can be called separately (when hours changes)
         */
        _generateHTMLMinutes: function (inst) {

            var m, row, html = '',
                rows = this._get(inst, 'rows'),
                minutes = Array(),
                minutes_options = this._get(inst, 'minutes'),
                minutesPerRow = null,
                minuteCounter = 0,
                showMinutesLeadingZero = (this._get(inst, 'showMinutesLeadingZero') == true),
                onMinuteShow = this._get(inst, 'onMinuteShow'),
                minuteLabel = this._get(inst, 'minuteText');

            if ( ! minutes_options.starts) {
                minutes_options.starts = 0;
            }
            if ( ! minutes_options.ends) {
                minutes_options.ends = 59;
            }
            for (m = minutes_options.starts; m <= minutes_options.ends; m += minutes_options.interval) {
                minutes.push(m);
            }
            minutesPerRow = Math.round(minutes.length / rows + 0.49); // always round up

            /*
             * The minutes table
             */
            // if currently selected minute is not enabled, we have a problem and need to select a new minute.
            if (onMinuteShow &&
                (onMinuteShow.apply((inst.input ? inst.input[0] : null), [inst.hours , inst.minutes]) == false) ) {
                // loop minutes and select first available
                for (minuteCounter = 0; minuteCounter < minutes.length; minuteCounter += 1) {
                    m = minutes[minuteCounter];
                    if (onMinuteShow.apply((inst.input ? inst.input[0] : null), [inst.hours, m])) {
                        inst.minutes = m;
                        break;
                    }
                }
            }



            html += '<div class="ui-timepicker-title ui-widget-header ui-helper-clearfix ui-corner-all">' +
                    minuteLabel +
                    '</div>' +
                    '<table class="ui-timepicker">';
            
            minuteCounter = 0;
            for (row = 1; row <= rows; row++) {
                html += '<tr>';
                while (minuteCounter < row * minutesPerRow) {
                    var m = minutes[minuteCounter];
                    var displayText = '';
                    if (m !== undefined ) {
                        displayText = (m < 10) && showMinutesLeadingZero ? "0" + m.toString() : m.toString();
                    }
                    html += this._generateHTMLMinuteCell(inst, m, displayText);
                    minuteCounter++;
                }
                html += '</tr>';
            }
            
            html += '</table>';

            return html;
        },

        /* Generate the content of a "Hour" cell */
        _generateHTMLHourCell: function (inst, hour, showPeriod, showLeadingZero) {

            var displayHour = hour;
            if ((hour > 12) && showPeriod) {
                displayHour = hour - 12;
            }
            if ((displayHour == 0) && showPeriod) {
                displayHour = 12;
            }
            if ((displayHour < 10) && showLeadingZero) {
                displayHour = '0' + displayHour;
            }

            var html = "";
            var enabled = true;
            var onHourShow = this._get(inst, 'onHourShow');		//custom callback

            if (hour == undefined) {
                html = '<td><span class="ui-state-default ui-state-disabled">&nbsp;</span></td>';
                return html;
            }

            if (onHourShow) {
            	enabled = onHourShow.apply((inst.input ? inst.input[0] : null), [hour]);
            }

            if (enabled) {
                html = '<td class="ui-timepicker-hour-cell" data-timepicker-instance-id="#' + inst.id.replace("\\\\","\\") + '" data-hour="' + hour.toString() + '">' +
                   '<a class="ui-state-default ' +
                   (hour == inst.hours ? 'ui-state-active' : '') +
                   '">' +
                   displayHour.toString() +
                   '</a></td>';
            }
            else {
            	html =
            		'<td>' +
		                '<span class="ui-state-default ui-state-disabled ' +
		                (hour == inst.hours ? ' ui-state-active ' : ' ') +
		                '">' +
		                displayHour.toString() +
		                '</span>' +
		            '</td>';
            }
            return html;
        },

        /* Generate the content of a "Hour" cell */
        _generateHTMLMinuteCell: function (inst, minute, displayText) {
        	 var html = "";
             var enabled = true;
             var onMinuteShow = this._get(inst, 'onMinuteShow');		//custom callback
             if (onMinuteShow) {
            	 //NEW: 2011-02-03  we should give the hour as a parameter as well!
             	enabled = onMinuteShow.apply((inst.input ? inst.input[0] : null), [inst.hours,minute]);		//trigger callback
             }

             if (minute == undefined) {
                 html = '<td><span class="ui-state-default ui-state-disabled">&nbsp;</span></td>';
                 return html;
             }

             if (enabled) {
	             html = '<td class="ui-timepicker-minute-cell" data-timepicker-instance-id="#' + inst.id.replace("\\\\","\\") + '" data-minute="' + minute.toString() + '" >' +
	                   '<a class="ui-state-default ' +
	                   (minute == inst.minutes ? 'ui-state-active' : '') +
	                   '" >' +
	                   displayText +
	                   '</a></td>';
             }
             else {

            	html = '<td>' +
	                 '<span class="ui-state-default ui-state-disabled" >' +
	                 	displayText +
	                 '</span>' +
                 '</td>';
             }
             return html;
        },


        /* Enable the date picker to a jQuery selection.
           @param  target    element - the target input field or division or span */
        _enableTimepicker: function(target) {
            var jQuerytarget = jQuery(target),
                target_id = jQuerytarget.attr('id'),
                inst = jQuery.data(target, PROP_NAME);
            
            if (!jQuerytarget.hasClass(this.markerClassName)) {
                return;
            }
            var nodeName = target.nodeName.toLowerCase();
            if (nodeName == 'input') {
                target.disabled = false;
                inst.trigger.filter('button').
                    each(function() { this.disabled = false; }).end();
            }
            else if (nodeName == 'div' || nodeName == 'span') {
                var inline = jQuerytarget.children('.' + this._inlineClass);
                inline.children().removeClass('ui-state-disabled');
            }
            this._disabledInputs = jQuery.map(this._disabledInputs,
                function(value) { return (value == target_id ? null : value); }); // delete entry
        },

        /* Disable the time picker to a jQuery selection.
           @param  target    element - the target input field or division or span */
        _disableTimepicker: function(target) {
            var jQuerytarget = jQuery(target);
            var inst = jQuery.data(target, PROP_NAME);
            if (!jQuerytarget.hasClass(this.markerClassName)) {
                return;
            }
            var nodeName = target.nodeName.toLowerCase();
            if (nodeName == 'input') {
                target.disabled = true;

                inst.trigger.filter('button').
                    each(function() { this.disabled = true; }).end();

            }
            else if (nodeName == 'div' || nodeName == 'span') {
                var inline = jQuerytarget.children('.' + this._inlineClass);
                inline.children().addClass('ui-state-disabled');
            }
            this._disabledInputs = jQuery.map(this._disabledInputs,
                function(value) { return (value == target ? null : value); }); // delete entry
            this._disabledInputs[this._disabledInputs.length] = jQuerytarget.attr('id');
        },

        /* Is the first field in a jQuery collection disabled as a timepicker?
        @param  target    element - the target input field or division or span
        @return boolean - true if disabled, false if enabled */
        _isDisabledTimepicker: function (target_id) {
            if ( ! target_id) { return false; }
            for (var i = 0; i < this._disabledInputs.length; i++) {
                if (this._disabledInputs[i] == target_id) { return true; }
            }
            return false;
        },

        /* Check positioning to remain on screen. */
        _checkOffset: function (inst, offset, isFixed) {
            var tpWidth = inst.tpDiv.outerWidth();
            var tpHeight = inst.tpDiv.outerHeight();
            var inputWidth = inst.input ? inst.input.outerWidth() : 0;
            var inputHeight = inst.input ? inst.input.outerHeight() : 0;
            var viewWidth = document.documentElement.clientWidth + jQuery(document).scrollLeft();
            var viewHeight = document.documentElement.clientHeight + jQuery(document).scrollTop();

            offset.left -= (this._get(inst, 'isRTL') ? (tpWidth - inputWidth) : 0);
            offset.left -= (isFixed && offset.left == inst.input.offset().left) ? jQuery(document).scrollLeft() : 0;
            offset.top -= (isFixed && offset.top == (inst.input.offset().top + inputHeight)) ? jQuery(document).scrollTop() : 0;

            // now check if datepicker is showing outside window viewport - move to a better place if so.
            offset.left -= Math.min(offset.left, (offset.left + tpWidth > viewWidth && viewWidth > tpWidth) ?
			Math.abs(offset.left + tpWidth - viewWidth) : 0);
            offset.top -= Math.min(offset.top, (offset.top + tpHeight > viewHeight && viewHeight > tpHeight) ?
			Math.abs(tpHeight + inputHeight) : 0);

            return offset;
        },

        /* Find an object's position on the screen. */
        _findPos: function (obj) {
            var inst = this._getInst(obj);
            var isRTL = this._get(inst, 'isRTL');
            while (obj && (obj.type == 'hidden' || obj.nodeType != 1)) {
                obj = obj[isRTL ? 'previousSibling' : 'nextSibling'];
            }
            var position = jQuery(obj).offset();
            return [position.left, position.top];
        },

        /* Retrieve the size of left and top borders for an element.
        @param  elem  (jQuery object) the element of interest
        @return  (number[2]) the left and top borders */
        _getBorders: function (elem) {
            var convert = function (value) {
                return { thin: 1, medium: 2, thick: 3}[value] || value;
            };
            return [parseFloat(convert(elem.css('border-left-width'))),
			parseFloat(convert(elem.css('border-top-width')))];
        },


        /* Close time picker if clicked elsewhere. */
        _checkExternalClick: function (event) {
            if (!jQuery.timepicker._curInst) { return; }
            var jQuerytarget = jQuery(event.target);
            if (jQuerytarget[0].id != jQuery.timepicker._mainDivId &&
				jQuerytarget.parents('#' + jQuery.timepicker._mainDivId).length == 0 &&
				!jQuerytarget.hasClass(jQuery.timepicker.markerClassName) &&
				!jQuerytarget.hasClass(jQuery.timepicker._triggerClass) &&
				jQuery.timepicker._timepickerShowing && !(jQuery.timepicker._inDialog && jQuery.blockUI))
                jQuery.timepicker._hideTimepicker();
        },

        /* Hide the time picker from view.
        @param  input  element - the input field attached to the time picker */
        _hideTimepicker: function (input) {
            var inst = this._curInst;
            if (!inst || (input && inst != jQuery.data(input, PROP_NAME))) { return; }
            if (this._timepickerShowing) {
                var showAnim = this._get(inst, 'showAnim');
                var duration = this._get(inst, 'duration');
                var postProcess = function () {
                    jQuery.timepicker._tidyDialog(inst);
                    this._curInst = null;
                };
                if (jQuery.effects && jQuery.effects[showAnim]) {
                    inst.tpDiv.hide(showAnim, jQuery.timepicker._get(inst, 'showOptions'), duration, postProcess);
                }
                else {
                    inst.tpDiv[(showAnim == 'slideDown' ? 'slideUp' :
					    (showAnim == 'fadeIn' ? 'fadeOut' : 'hide'))]((showAnim ? duration : null), postProcess);
                }
                if (!showAnim) { postProcess(); }
                var onClose = this._get(inst, 'onClose');
                if (onClose) {
                    onClose.apply(
                        (inst.input ? inst.input[0] : null),
					    [(inst.input ? inst.input.val() : ''), inst]);  // trigger custom callback
                }
                this._timepickerShowing = false;
                this._lastInput = null;
                if (this._inDialog) {
                    this._dialogInput.css({ position: 'absolute', left: '0', top: '-100px' });
                    if (jQuery.blockUI) {
                        jQuery.unblockUI();
                        jQuery('body').append(this.tpDiv);
                    }
                }
                this._inDialog = false;
            }
        },

        /* Tidy up after a dialog display. */
        _tidyDialog: function (inst) {
            inst.tpDiv.removeClass(this._dialogClass).unbind('.ui-timepicker');
        },

        /* Retrieve the instance data for the target control.
        @param  target  element - the target input field or division or span
        @return  object - the associated instance data
        @throws  error if a jQuery problem getting data */
        _getInst: function (target) {
            try {
                return jQuery.data(target, PROP_NAME);
            }
            catch (err) {
                throw 'Missing instance data for this timepicker';
            }
        },

        /* Get a setting value, defaulting if necessary. */
        _get: function (inst, name) {
            return inst.settings[name] !== undefined ?
			inst.settings[name] : this._defaults[name];
        },

        /* Parse existing time and initialise time picker. */
        _setTimeFromField: function (inst) {
            if (inst.input.val() == inst.lastVal) { return; }
            var defaultTime = this._get(inst, 'defaultTime');

            var timeToParse = defaultTime == 'now' ? this._getCurrentTimeRounded(inst) : defaultTime;
            if ((inst.inline == false) && (inst.input.val() != '')) { timeToParse = inst.input.val() }

            var timeVal = inst.lastVal = timeToParse;

            

            if (timeToParse == '') {
                inst.hours = -1;
                inst.minutes = -1;
            } else {
                var time = this.parseTime(inst, timeVal);
                inst.hours = time.hours;
                inst.minutes = time.minutes;
            }

            jQuery.timepicker._updateTimepicker(inst);
        },
        /* Set the dates for a jQuery selection.
	    @param  target   element - the target input field or division or span
	    @param  date     Date - the new date */
	    _setTimeTimepicker: function(target, time) {
		    var inst = this._getInst(target);
		    if (inst) {
			    this._setTime(inst, time);
    			this._updateTimepicker(inst);
	    		this._updateAlternate(inst, time);
		    }
	    },

        /* Set the time directly. */
        _setTime: function(inst, time, noChange) {
            var origHours = inst.hours;
            var origMinutes = inst.minutes;
            var time = this.parseTime(inst, time);
            inst.hours = time.hours;
            inst.minutes = time.minutes;

            if ((origHours != inst.hours || origMinutes != inst.minuts) && !noChange) {
                inst.input.trigger('change');
            }
            this._updateTimepicker(inst);
            this._updateSelectedValue(inst);
        },

        /* Return the current time, ready to be parsed, rounded to the closest 5 minute */
        _getCurrentTimeRounded: function (inst) {
            var currentTime = new Date();
            var timeSeparator = this._get(inst, 'timeSeparator');
            // setting selected time , least priority first
            var currentMinutes = currentTime.getMinutes()
            // round to closest 5
            currentMinutes = Math.round( currentMinutes / 5 ) * 5;

            return currentTime.getHours().toString() + timeSeparator + currentMinutes.toString();
        },

        /*
        * Pase a time string into hours and minutes
        */
        parseTime: function (inst, timeVal) {
            var retVal = new Object();
            retVal.hours = -1;
            retVal.minutes = -1;

            var timeSeparator = this._get(inst, 'timeSeparator');
            var amPmText = this._get(inst, 'amPmText');
            var p = timeVal.indexOf(timeSeparator);
            if (p == -1) { return retVal; }

            retVal.hours = parseInt(timeVal.substr(0, p), 10);
            retVal.minutes = parseInt(timeVal.substr(p + 1), 10);

            var showPeriod = (this._get(inst, 'showPeriod') == true);
            var timeValUpper = timeVal.toUpperCase();
            if ((retVal.hours < 12) && (showPeriod) && (timeValUpper.indexOf(amPmText[1].toUpperCase()) != -1)) {
                retVal.hours += 12;
            }
            // fix for 12 AM
            if ((retVal.hours == 12) && (showPeriod) && (timeValUpper.indexOf(amPmText[0].toUpperCase()) != -1)) {
                retVal.hours = 0;
            }

            return retVal;
        },



        selectHours: function (event) {
            var jQuerytd = jQuery(event.currentTarget),
                id = jQuerytd.attr("data-timepicker-instance-id"),
                newHours = jQuerytd.attr("data-hour"),
                fromDoubleClick = event.data.fromDoubleClick,
                jQuerytarget = jQuery(id),
                inst = this._getInst(jQuerytarget[0]),
                showMinutes = (this._get(inst, 'showMinutes') == true);

            // don't select if disabled
            if ( jQuery.timepicker._isDisabledTimepicker(jQuerytarget.attr('id')) ) { return false }

            jQuerytd.parents('.ui-timepicker-hours:first').find('a').removeClass('ui-state-active');
            jQuerytd.children('a').addClass('ui-state-active');
            inst.hours = newHours;

            // added for onMinuteShow callback
            var onMinuteShow = this._get(inst, 'onMinuteShow');
            if (onMinuteShow) {
                // this will trigger a callback on selected hour to make sure selected minute is allowed. 
                this._updateMinuteDisplay(inst);
            }

            this._updateSelectedValue(inst);

            inst._hoursClicked = true;
            if ((inst._minutesClicked) || (fromDoubleClick) || (showMinutes == false)) {
                jQuery.timepicker._hideTimepicker();
            }
            // return false because if used inline, prevent the url to change to a hashtag
            return false;
        },

        selectMinutes: function (event) {
            var jQuerytd = jQuery(event.currentTarget),
                id = jQuerytd.attr("data-timepicker-instance-id"),
                newMinutes = jQuerytd.attr("data-minute"),
                fromDoubleClick = event.data.fromDoubleClick,
                jQuerytarget = jQuery(id),
                inst = this._getInst(jQuerytarget[0]),
                showHours = (this._get(inst, 'showHours') == true);

            // don't select if disabled
            if ( jQuery.timepicker._isDisabledTimepicker(jQuerytarget.attr('id')) ) { return false }

            jQuerytd.parents('.ui-timepicker-minutes:first').find('a').removeClass('ui-state-active');
            jQuerytd.children('a').addClass('ui-state-active');

            inst.minutes = newMinutes;
            this._updateSelectedValue(inst);

            inst._minutesClicked = true;
            if ((inst._hoursClicked) || (fromDoubleClick) || (showHours == false)) {
                jQuery.timepicker._hideTimepicker();
                // return false because if used inline, prevent the url to change to a hashtag
                return false;
            }

            // return false because if used inline, prevent the url to change to a hashtag
            return false;
        },

        _updateSelectedValue: function (inst) {
            var newTime = this._getParsedTime(inst);
            if (inst.input) {
                inst.input.val(newTime);
                inst.input.trigger('change');
            }
            var onSelect = this._get(inst, 'onSelect');
            if (onSelect) { onSelect.apply((inst.input ? inst.input[0] : null), [newTime, inst]); } // trigger custom callback
            this._updateAlternate(inst, newTime);
            return newTime;
        },
        
        /* this function process selected time and return it parsed according to instance options */
        _getParsedTime: function(inst) {

            if ((inst.hours < 0) || (inst.hours > 23)) { inst.hours = 12; }
            if ((inst.minutes < 0) || (inst.minutes > 59)) { inst.minutes = 0; }

            var period = "",
                showPeriod = (this._get(inst, 'showPeriod') == true),
                showLeadingZero = (this._get(inst, 'showLeadingZero') == true),
                showHours = (this._get(inst, 'showHours') == true),
                showMinutes = (this._get(inst, 'showMinutes') == true),
                amPmText = this._get(inst, 'amPmText'),
                selectedHours = inst.hours ? inst.hours : 0,
                selectedMinutes = inst.minutes ? inst.minutes : 0,
                displayHours = selectedHours ? selectedHours : 0,
                parsedTime = '';

            if (showPeriod) {
                if (inst.hours == 0) {
                    displayHours = 12;
                }
                if (inst.hours < 12) {
                    period = amPmText[0];
                }
                else {
                    period = amPmText[1];
                    if (displayHours > 12) {
                        displayHours -= 12;
                    }
                }
            }

            var h = displayHours.toString();
            if (showLeadingZero && (displayHours < 10)) { h = '0' + h; }

            var m = selectedMinutes.toString();
            if (selectedMinutes < 10) { m = '0' + m; }

            if (showHours) {
                parsedTime += h;
            }
            if (showHours && showMinutes) {
                parsedTime += this._get(inst, 'timeSeparator');
            }
            if (showMinutes) {
                parsedTime += m;
            }
            if (showHours) {
                if (period.length > 0) { parsedTime += this._get(inst, 'periodSeparator') + period; }
            }
            
            return parsedTime;
        },
        
        /* Update any alternate field to synchronise with the main field. */
        _updateAlternate: function(inst, newTime) {
            var altField = this._get(inst, 'altField');
            if (altField) { // update alternate field too
                jQuery(altField).each(function(i,e) {
                    jQuery(e).val(newTime);
                });
            }
        },

        /* This might look unused but it's called by the jQuery.fn.timepicker function with param getTime */
        /* added v 0.2.3 - gitHub issue #5 - Thanks edanuff */
        _getTimeTimepicker : function(input) {
            var inst = this._getInst(input);
            return this._getParsedTime(inst);
        },
        _getHourTimepicker: function(input) {
            var inst = this._getInst(input);
            if ( inst == undefined) { return -1; }
            return inst.hours;
        },
        _getMinuteTimepicker: function(input) {
            var inst= this._getInst(input);
            if ( inst == undefined) { return -1; }
            return inst.minutes;
        }

    });



    /* Invoke the timepicker functionality.
    @param  options  string - a command, optionally followed by additional parameters or
    Object - settings for attaching new timepicker functionality
    @return  jQuery object */
    jQuery.fn.timepicker = function (options) {

        /* Initialise the date picker. */
        if (!jQuery.timepicker.initialized) {
            jQuery(document).mousedown(jQuery.timepicker._checkExternalClick).
			find('body').append(jQuery.timepicker.tpDiv);
            jQuery.timepicker.initialized = true;
        }

        var otherArgs = Array.prototype.slice.call(arguments, 1);
        if (typeof options == 'string' && (options == 'getTime' || options == 'getHour' || options == 'getMinute' ))
            return jQuery.timepicker['_' + options + 'Timepicker'].
			apply(jQuery.timepicker, [this[0]].concat(otherArgs));
        if (options == 'option' && arguments.length == 2 && typeof arguments[1] == 'string')
            return jQuery.timepicker['_' + options + 'Timepicker'].
			apply(jQuery.timepicker, [this[0]].concat(otherArgs));
        return this.each(function () {
            typeof options == 'string' ?
			jQuery.timepicker['_' + options + 'Timepicker'].
				apply(jQuery.timepicker, [this].concat(otherArgs)) :
			jQuery.timepicker._attachTimepicker(this, options);
        });
    };

    /* jQuery extend now ignores nulls! */
    function extendRemove(target, props) {
        jQuery.extend(target, props);
        for (var name in props)
            if (props[name] == null || props[name] == undefined)
                target[name] = props[name];
        return target;
    };

    jQuery.timepicker = new Timepicker(); // singleton instance
    jQuery.timepicker.initialized = false;
    jQuery.timepicker.uuid = new Date().getTime();
    jQuery.timepicker.version = "0.2.5";

    // Workaround for #4055
    // Add another global to avoid noConflict issues with inline event handlers
    window['TP_jQuery_' + tpuuid] = jQuery;

})(jQuery);
