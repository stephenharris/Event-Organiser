<?php
	/*
	 * Public full calendar:
	 * This returns events to be displayed on the front-end full calendar
	*/
	add_action( 'wp_ajax_eventorganiser-fullcal', 'eo_public_fullcal' ); 
	add_action( 'wp_ajax_nopriv_eventorganiser-fullcal', 'eo_public_fullcal' ); 
	function eo_public_fullcal() {
		$request = array(
			'event_start_before'=>$_GET['end'],
			'event_end_after'=>$_GET['start']
		);
		$presets = array('numberposts'=>-1, 'showrepeats'=>true,'showpastevents'=>true);

		//Retrieve events		
		$query = array_merge($request,$presets);
		$events = eo_get_events($query);
		$eventsarray = array();

		//Loop through events
		global $post;
		if ($events) : 
			foreach  ($events as $post) :
				$event=array();
				$event['className']=array('eo-event');

				//Title and url
				$event['title']=html_entity_decode(get_the_title($post->ID),ENT_QUOTES,'UTF-8');
				$event['url']= esc_js(get_permalink( $post->ID));

				//All day or not?
				$event['allDay'] = ($post->event_allday ? true : false);
	
				//Get Event Start and End date, set timezone to the blog's timzone
				$event_start = new DateTime($post->StartDate.' '.$post->StartTime, EO_Event::get_timezone());
				$event_end = new DateTime($post->EndDate.' '.$post->FinishTime, EO_Event::get_timezone());
				$event['start']= $event_start->format('Y-m-d\TH:i:s\Z');
				$event['end']= $event_end->format('Y-m-d\TH:i:s\Z');	

				//Colour past events
				$now = new DateTIme(null,EO_Event::get_timezone());
				if($event_start <= $now)
					$event['className'][] = 'eo-past-event';
				else
					$event['className'][] = 'eo-future-event';
				
				//Include venue if this is set
				if($post->Venue){
					$event['className'][]= 'venue-'.eo_get_venue_slug($post->ID);
					$event['venue']=$post->Venue;
				}
				
				//Event categories
				$terms = get_the_terms( $post->ID, 'event-category' );
				$event['category']=array();
				if($terms):
					foreach ($terms as $term):
						$event['category'][]= $term->slug;
						if(empty($event['color'])):
							$term_meta = get_option( "eo-event-category_$term->term_id");
							$event['color'] = (isset($term_meta['colour']) ? $term_meta['colour'] : '');
						endif;
						$event['className'][]='category-'.$term->slug;
					endforeach;
				endif;

				//Add event to array
				$eventsarray[]=$event;
			endforeach;
		endif;

		//Echo result and exit
		echo json_encode($eventsarray);
		exit;
	}

	/*
	 * Admin calendar: Calendar View
	 * This gets events and generates summaries for events to be displayed
	 *  in the admin 'calendar view'
	*/
	add_action( 'wp_ajax_event-admin-cal', 'eo_ajax_admin_cal' ); 
	function eo_ajax_admin_cal() {
		//request
		$request = array(
			'event_end_after'=>$_GET['start'],
			'event_start_before'=>$_GET['end']
		);

		//Presets
		$presets = array( 
			'posts_per_page'=>-1,
			'post_type'=>'event',
			'showrepeats'=>true,
			'perm' => 'readable');

		//Create query
		$query_array = array_merge($presets, $request);	
		$query = new WP_Query($query_array );

		//Retrieve events		
		$query->get_posts();
		$eventsarray = array();

		//Loop through events
		global $post;
		if ( $query->have_posts() ) : 
			while ( $query->have_posts() ) : $query->the_post(); 
				$event=array();
				$colour='';
				//Get title, append status if applicable
				$title = get_the_title();
				if(!empty($post->post_password)){
					$title.=' - '.__('Protected');
				}elseif($post->post_status=='private'){
					$title.=' - '.__('Private');
				}elseif	($post->post_status=='draft'){
					$title.=' - '.__('Draft');
				}
				$event['title']= html_entity_decode ($title,ENT_QUOTES,'UTF-8');

				//Check if all day, set format accordingly
				if($post->event_allday){
					$event['allDay'] = true;
					$format = get_option('date_format');
				}else{
					$event['allDay'] = false;
					$format = get_option('date_format').'  '.get_option('time_format');
				}
	
				//Get author (or organiser)
				$organiser = get_userdata( $post->post_author)->display_name;
	
				//Get Event Start and End date, set timezone to the blog's timzone
				$event_start = new DateTime($post->StartDate.' '.$post->StartTime, EO_Event::get_timezone());
				$event_end = new DateTime($post->EndDate.' '.$post->FinishTime, EO_Event::get_timezone());
	
				$event['start']= $event_start->format('Y-m-d\TH:i:s\Z');
				$event['end']= $event_end->format('Y-m-d\TH:i:s\Z');
	
				//Produce summary of event
				$summary= "<table>"
								."<tr><th> ".__('Start','eventorganiser').": </th><td> ".eo_format_datetime($event_start,$format)."</td></tr>"
								."<tr><th> ".__('End','eventorganiser').": </th><td> ".eo_format_datetime($event_end, $format)."</td></tr>"
								."<tr><th> ".__('Organiser','eventorganiser').": </th><td>".$organiser."</td></tr>";
	
				$event['className']=array('event');

				 $now = new DateTIme(null,EO_Event::get_timezone());
				if($event_start <= $now)
					$event['className'][]='past-event';

				//Include venue if this is set
				if($post->Venue){
					$summary .="<tr><th>".__('Where','eventorganiser').": </th><td>".eo_get_venue_name((int)$post->Venue)."</td></tr>";
					$event['className'][]= 'venue-'.eo_get_venue_slug($post->ID);
					$event['venue']=$post->Venue;
				}
	
				$summary .= "</table><p>";
							
				//Include schedule summary if event reoccurrs
				if($post->event_schedule !='once')
					$summary .='<em>'.__('This event reoccurs','eventorganiser').' '.eo_get_schedule_summary().'</em>';
				$summary .='</p>';

				//Include edit link in summary if user has permission
				if (current_user_can('edit_event', $post->ID)){
					$edit_link = get_edit_post_link( $post->ID,'');
					$summary .= "<span class='edit'><a title='Edit this item' href='".$edit_link."'> ".__('Edit Event','eventorganiser')."</a></span>";
					$event['url']= $edit_link;
				}

				//Include a delete occurrence link in summary if user has permission
				if (current_user_can('delete_event', $post->ID)){
					$delete_url  = admin_url('edit.php');
					$delete_url = add_query_arg(array(
						'post_type'=>'event',
						'page'=>'calendar',
						'series'=>$post->ID,
						'event'=>$post->event_id,
						'action'=>'delete_occurrence'
					),$delete_url);
					$delete_url  = wp_nonce_url( $delete_url , 'eventorganiser_delete_occurrence_'.$post->event_id);
					$summary .= "<span class='delete'><a class='submitdelete' style='color:red;float:right' title='".__('Delete this occurrence','eventorganiser')."' href='".$delete_url."'> ".__('Delete this occurrence','eventorganiser')."</a></span>";
				}

				$terms = get_the_terms( $post->ID, 'event-category' );
				$event['category']=array();
				if($terms):
					foreach ($terms as $term):
						$event['category'][]= $term->slug;
						if(empty($event['color'])):
							$term_meta = get_option( "eo-event-category_$term->term_id");
							$event['color'] = (isset($term_meta['colour']) ? $term_meta['colour'] : '');
						endif;
						$event['className'][]='category-'.$term->slug;
					endforeach;
				endif;

				$event['summary'] = $summary;

				//Add event to array
				$eventsarray[]=$event;
			endwhile;
		endif;

		//Echo result and exit
		echo json_encode($eventsarray);
		exit;
}

	/*
	 * Widget and Shortcode calendar:
	 * This gets the month being viewed and generates the
	 * html code to view that month and its events. 
	*/
 	add_action( 'wp_ajax_nopriv_eo_widget_cal', 'ajax_widget_cal' );
	add_action( 'wp_ajax_eo_widget_cal', 'ajax_widget_cal' );
	function ajax_widget_cal() {

		/*Retrieve the month we are after. $month must be a 
		DateTime object of the first of that month*/
		if(isset($_GET['eo_month'])){
			$month  = new DateTime($_GET['eo_month'].'-01'); 
		}else{
			$month = new DateTime();
			$month->modify('first day of this month');
		}		
		echo json_encode(EO_Calendar_Widget::generate_output($month));
		exit;
}

	/*
	 * Widget and Shortcode agenda:
	 * This gets the month being viewed and generates the
	 * html code to view that month and its events. 
	*/
 	add_action( 'wp_ajax_nopriv_eo_widget_agenda', 'ajax_widget_agenda' );
	add_action( 'wp_ajax_eo_widget_agenda', 'ajax_widget_agenda' );
	function ajax_widget_agenda() {
		global $wpdb,$eventorganiser_events_table,$wp_locale;
		$meridiem =$wp_locale->meridiem;
		$direction = intval($_GET['direction']);
		$today= new DateTIme('now');

		$before_or_after = ($direction <1 ? 'before' : 'after');
		$date = ($direction <1? $_GET['start'] : $_GET['end']);
		$order = ($direction <1? 'DESC' : 'ASC');
		
		$selectDates="SELECT DISTINCT StartDate FROM {$eventorganiser_events_table}";

		if($order=='ASC')
			$whereDates = " WHERE {$eventorganiser_events_table}.StartDate >= %s ";
		else
			$whereDates = " WHERE {$eventorganiser_events_table}.StartDate <= %s ";

		$whereDates .= " AND {$eventorganiser_events_table}.StartDate >= %s ";

		$orderlimit = "ORDER BY  {$eventorganiser_events_table}.StartDate $order LIMIT 4";

		$dates = $wpdb->get_col($wpdb->prepare($selectDates.$whereDates.$orderlimit, $date,$today->format('Y-m-d')));

		if(!$dates)
			return false;

		$date1  = min($dates[0],$dates[count($dates)-1]);
		$date2 = max($dates[0],$dates[count($dates)-1]);

		$events = eo_get_events(array(
			'event_start_after'=>$date1,
			'event_start_before'=>$date2
		));

		$return_array = array();

		global $post;

		foreach ($events as $post):

			$startDT = new DateTime($post->StartDate.' '.$post->StartTime);
			
			if(!$post->event_allday):
				$ampm = trim($meridiem[$startDT->format('a')]);
				$ampm =  (empty($ampm) ? $startDT->format('a') : $ampm); //Tranlsate am/pm
				$time = $startDT->format('g:i').$ampm;
			else:		
				$time =  __('All Day','eventorganiser');
			endif;

			$color='';
			$terms = get_the_terms( $post->ID, 'event-category' );
			if($terms):
				foreach ($terms as $term):
					if(empty($color)):
						$term_meta = get_option( "eo-event-category_$term->term_id");
						$color = (isset($term_meta['colour']) ? $term_meta['colour'] : '');
					endif;
				endforeach;
			endif;
			//'StartDate'=>eo_format_date($post->StartDate,'l jS F'),
			$return_array[] = array(
				'StartDate'=>$post->StartDate,
				'time'=>$time,
				'post_title'=>substr($post->post_title,0,25),
				'event_allday'=>$post->event_allday,
				'color'=>$color,
				'link'=>get_permalink(),
				'Glink'=>eo_get_GoogleLink()
			);
		endforeach;

		echo json_encode($return_array);
		exit;
	}


	/*
	 * Venue search
	 * Returns a list of venues that match the term
	 * Queries venue name.
	*/
	add_action( 'wp_ajax_eo-search-venue', 'eventorganiser_search_venues' ); 
	function eventorganiser_search_venues() {
		// Query the venues with the given term
		$EO_Venues = new EO_Venues;
		$EO_Venues->query(array('s'=>$_GET["term"]));

		$venues_array = $EO_Venues->results;
		$novenue = array('venue_id'=>0,'venue_name'=>__('No Venue','eventorganiser'));

		$venues_array =array_merge (array($novenue),$venues_array);
		//echo JSON to page  
		$response = $_GET["callback"] . "(" . json_encode($venues_array) . ")";  
		echo $response;  
		exit;
}
?>
