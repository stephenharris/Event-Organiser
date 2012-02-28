<?php
    /*
	Class to support venues as taxonomeies 
    */
	class EO_Venue_Tax {
	
		//TODO
		//Check converter for bugs :D

		//Interpret 'Venue' as tax query???
		//FIXME On deletion of venue - delete tax term (delete tax term, then delete meta with hook)
		//Bulk delete
		//importing venues
		//Fix venue being saved (convert slug/ID to to term, add to object save in events table as term_id)
		//On edit of venue - edit tax-term (name/slug)?
		//Get descriptions from tax-table rather than eo_venues
		//Venue class - add / update
		//Event class - insert event
		//Altered drop-down
		//Altered manage table - links & drop-down
		//Altered getting venue link
		//Alterations made to event.js.
		//Alterations made to event.js.
		//Added search term function
		//fullcalendar.js

		static function load() {
			//To be run once
			//add_filter( 'admin_init', array( __CLASS__, 'converter'), 15 );
		}	
	
		//To be run once after installation
		static function converter(){
			global $eventorganiser_venue_table,$eventorganiser_events_table, $wpdb;

			//Get venues from meta table
			$venues = eo_get_the_venues();

			//Tack any changes from an old slug to another
			$slimetrail = array();

			//For each term insert it as a taxonomy term.
			foreach ($venues as $venue){
				$old_slug =esc_attr($venue->venue_slug);	
				$term = wp_insert_term(esc_attr($venue->venue_name),'event-venue',array(
						'description'=> $venue->venue_description,
						'slug' => $old_slug ));
				if(!is_wp_error($term)){
					$term= get_term_by('id',$term['term_id'],'event-venue');
					//WordPress may have change the slug
					$slimetrail[$old_slug] = $term->slug;
					if($term->slug != $old_slug){
						$wpdb->update($eventorganiser_venue_table, array('venue_slug'=>$term->slug),  array('venue_id'=>$venue->venue_id), '%s', '%d'); 
					}
				}
			}
		
			//Loop through ALL events...
			$events = new WP_Query(array(
				'post_type'=>'event',	'posts_per_page'=>-1,'showpastevents'=>1,'showrepeats'=>0,
				'post_status' => array('publish','private', 'pending', 'draft', 'future','trash')
			));

			global $post;
			if($events->have_posts()):
				while($events->have_posts()): $events->the_post();
					if(empty($post->Venue))
						continue; //Doesn't have a venue

					$post_id = intval($post->ID);
					$venue_id =intval($post->Venue);
					$venue = eo_get_venue_by('id',$venue_id);//Get venue meta.

					if($venue){
						$slug = esc_attr($venue->venue_slug);
						$venue_tax= get_term_by('slug',$slug,'event-venue');
						if($venue_tax){
							$venue_tax_id =(int) $venue_tax->term_id;
							wp_set_object_terms( $post_id, array($venue_tax_id),'event-venue');
							//Change Venue column to tax ID
							$wpdb->update($eventorganiser_events_table, array('Venue'=>$venue_tax_id),  array('post_id'=>$post_id,'Venue'=>$venue_id), '%d', '%d'); 
						}
					}
				endwhile;
				wp_reset_postdata();
			endif;
		}
    }
    EO_Venue_Tax::load();
    ?>
