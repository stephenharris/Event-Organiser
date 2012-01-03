<?php


	//Only call action if logged in
	if(isset($_REQUEST['action'])):
		switch($_REQUEST['action']):
			case 'event-admin-cal': //The admin calendar
				do_action( 'wp_ajax_' . $_REQUEST['action'] );
				break;

			case 'eventorganiser-fullcal': //The public 'full' calendar
				do_action( 'wp_ajax_' . $_REQUEST['action'] );
				do_action( 'wp_ajax_nopriv_' . $_REQUEST['action'] );
				break;

			case 'eo_widget_cal': //The mini / 'widget-sized' public calendar
				do_action( 'wp_ajax_nopriv_' . $_REQUEST['action'] );
				do_action( 'wp_ajax_' . $_REQUEST['action'] );	
				break;
			default;
		endswitch;
	endif;


	/*
	 * Public full calendar:
	 * This gets events to be displayed on the front-end full calendar
	*/
	add_action( 'wp_ajax_eventorganiser-fullcal', 'eo_public_fullcal' ); 
	add_action( 'wp_ajax_nopriv_eventorganiser-fullcal', 'eo_public_fullcal' ); 
	function eo_public_fullcal() {
		$request = array(
			'start_before'=>$_GET['start'],
			'end_after'=>$_GET['end']
		);

		//Retrieve events		
		$events = eo_get_events(array('numberposts'=>-1, 'showrepeats'=>true));
		$eventsarray = array();

		//Loop through events
		global $post;
		if ($events) : 
			foreach  ($events as $post) :
				$event=array();
				$event['className']=array('eo-event');

				//Title and url
				$event['title']=esc_js(get_the_title($post->ID));
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
				$event['backgroundColor']=  '#21759B';	
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
			'start_before'=>$_GET['start'],
			'end_after'=>$_GET['end']
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
				//Get title, append status if applicable
				$title = get_the_title();
				if(!empty($post->post_password)){
					$title.=' - protected';
				}elseif($post->post_status=='private'){
					$title.=' - private';
				}elseif	($post->post_status=='draft'){
					$title.=' - draft';
				}
				$event['title']=esc_js($title);

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
				//Colour past events
				 $now = new DateTIme(null,EO_Event::get_timezone());

				if($event_start <= $now){
					$event['backgroundColor']=  '#74B2CD';	
				}else{
					$event['backgroundColor']=  '#21759B';	
				}
	
				//Produce summary of event
				$summary= "<table>"
								."<tr><th> Start: </th><td> ".$event_start->format($format)."</td></tr>"
								."<tr><th> End: </th><td> ".$event_end->format($format)."</td></tr>"
								."<tr><th> Organiser: </th><td>".$organiser."</td></tr>";
	
				$event['className']=array('event');

				//Include venue if this is set
				if($post->Venue){
					$summary .="<tr><th> Where: </th><td>".eo_get_venue_name((int)$post->Venue)."</td></tr>";
					$event['className'][]= 'venue-'.eo_get_venue_slug($post->ID);
					$event['venue']=$post->Venue;
				}
	
				$summary .= "</table>";
							
				//Include schedule summary if event reoccurrs
				if($post->event_id !='once')
					$summary .='<p><em>This event reoccurs every '.eo_get_schedule_summary().'</em></p>';

				//Include edit link in summary if user has permission
				if (current_user_can('edit_event', $post->ID)){
					$edit_link = get_edit_post_link( $post->ID,'');
					$summary .= "<span class='edit'><a title='Edit this item' href='".$edit_link."'> Edit Event</a></span>";
					$event['url']= $edit_link;
				}
				$terms = get_the_terms( $post->ID, 'event-category' );
				$event['category']=array();
				if($terms):
					foreach ($terms as $term):
						$event['category'][]= $term->slug;
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
?>
