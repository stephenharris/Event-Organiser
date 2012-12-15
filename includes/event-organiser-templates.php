<?php
/**
 * Checks if provided template path points to an 'event' template recognised by EO, given the context.
 * This will one day ignore context, and if only the event archive template is present in theme folder
 * it will use that  regardless. If no event-archive tempate is present the plug-in will pick the most appropriate
 * option, first from the theme/child-theme directory then the plugin.
 *
 * @ignore
 * @since 1.3.1
 *
 * @param string $templatePath absolute path to template or filename (with .php extension)
 * @param string $context What the template is for ('event','archive-event','event-venue', etc).
 * @return (true|false) return true if template is recognised as an 'event' template. False otherwise.
 */
function eventorganiser_is_event_template($templatePath,$context=''){

	$template = basename($templatePath);

	switch($context):
		case 'event';	
			return $template == 'single-event.php';

		case 'archive':
			return $template == 'archive-event.php';

		case 'event-venue':
			if((1 == preg_match('/^taxonomy-event-venue((-(\S*))?).php/',$template) || $template == 'venues-template.php'))
				return true;
			return false;

		case 'event-category':
			return (1 == preg_match('/^taxonomy-event-category((-(\S*))?).php/',$template));

		case 'event-tag':
			return (1 == preg_match('/^taxonomy-event-tag((-(\S*))?).php/',$template));
	endswitch;

	return false;
}

/**
 * Checks to see if appropriate templates are present in active template directory.
 * Otherwises uses templates present in plugin's template directory.
 * Hooked onto template_include'
 *
 * @ignore
 * @since 1.0.0
 * @param string $template Absolute path to template
 * @return string Absolute path to template
 */
function eventorganiser_set_template( $template ){

	//Has EO template handling been turned off?
	if( !eventorganiser_get_option('templates') )
		return $template;

	//If WordPress couldn't find an 'event' template use plug-in instead:
	$found_template = true;

	if( (is_post_type_archive('event') && !eventorganiser_is_event_template($template,'archive')
		|| ( is_singular('event') && !eventorganiser_is_event_template($template,'event'))
		|| ( is_tax('event-venue') || eo_is_venue() ) && !eventorganiser_is_event_template($template,'event-venue'))
		|| ( is_tax('event-category')  && !eventorganiser_is_event_template($template,'event-category'))
		|| ( is_tax('event-tag') && eventorganiser_get_option('eventtag') && !eventorganiser_is_event_template($template,'event-tag') )
	)
		$found_template = false;

	//If the template matches, return that:
	if( $found_template )
		return $template;

	if( is_singular('event') ){	
		//Viewing a single event

		//Hide next/previous post link
		add_filter("next_post_link",'__return_false');
		add_filter("previous_post_link",'__return_false');

		//Prepend our event details
		add_filter('the_content','_eventorganiser_single_event_content');
		return $template;
	}		

	/*
 	 * EO needs to provide the templates.
	 * If we're displaying a single event we just use the passed template, and over-ride the content
	 * by object buffering on the the_content filter
	 * Otherwise we pass the page template and use object buffering to insert our content
	*/

	//Remove all loop_start callbacks - restored in _eventorganiser_the_content
	eventorganiser_remove_filters( 'loop_start');

	//Set up fake global $wp_query. To be restored in our handler. Trick wordpress into displaying a 'page'.
	if( is_tax('event-venue') )
		$post_title = sprintf( __( 'Events at: %s', 'eventorganiser' ), '<span>' .eo_get_venue_name(). '</span>');
	elseif( is_tax('event-category') )
		$post_title = sprintf(__( 'Event Category Archives: %s', 'eventorganiser' ), '<span>' . single_cat_title( '', false ) . '</span>' );
	elseif( is_tax('event-tag') )
		$post_title = sprintf(__( 'Event Tag Archives: %s', 'eventorganiser' ), '<span>' . single_cat_title( '', false ) . '</span>' );
	elseif( is_post_type_archive('event') )
		$post_title = __('Events','eventorganiser');

	global $wp_query;
	$wp_query->post                        = new stdClass; 
	$wp_query->post->ID                    = 0;
	$wp_query->post->post_title = $post_title;
	$wp_query->post->post_author           = 1;
	$wp_query->post->post_parent           = 0;
	$wp_query->post->post_type             = '';
	$wp_query->post->post_date             = '';
	$wp_query->post->post_date_gmt  = '';
	$wp_query->post->post_modified         ='';
	$wp_query->post->post_modified_gmt  = '';
	$wp_query->post->post_content          = '';
	$wp_query->post->post_excerpt          = '';
	$wp_query->post->post_content_filtered ='';
	$wp_query->post->post_mime_type        = '';
	$wp_query->post->post_password         = '';
	$wp_query->post->post_name             = '';
	$wp_query->post->guid                  = '';
	$wp_query->post->menu_order            ='';
	$wp_query->post->pinged                ='';
	$wp_query->post->to_ping               = '';
	$wp_query->post->ping_status           ='';
	$wp_query->post->comment_status        = 'closed';
	$wp_query->post->comment_count         = 0;
	$wp_query->posts[0]=$wp_query->post;

	//Add our handler
	add_action('loop_start', '_eventorganiser_default_template_handler');

	$template = get_query_template('page');

	return $template;
}
add_filter('template_include', 'eventorganiser_set_template');



function _eventorganiser_default_template_handler($query){

	if( !$query->is_main_query() )
		return;

	/* TODO restore $wp_query */

	/* Unhook us to prevent infinite loops and other nasties */
	remove_action('loop_start', '_eventorganiser_default_template_handler');

	/* Remove and store all hooks on the_content */
	eventorganiser_remove_filters( 'the_content');
	
	add_filter('the_content','_eventorganiser_the_content');
	return;
}

function _eventorganiser_single_event_content( $content ){

	//Sanity check!
	if( !is_singular('event') )
		return $content;

	//Object buffering				
	ob_start();
	include(EVENT_ORGANISER_DIR.'templates/event-meta-event-single.php');
	$event_content = ob_get_contents();
	ob_end_clean();

	$event_content = apply_filters('eventorganiser_pre_event_content', $event_content, $content);

	return $event_content.$content;
}


function _eventorganiser_the_content( $page_content ){
	global $wp_query;

	//We're now in charge of the page content. First lets prevent any infinite loops
	remove_action('loop_start', '_eventorganiser_default_template_handler');
	remove_filter('the_content','_eventorganiser_the_content');

	//Restore loop_start & the_content callbacks in time for the fake loop.
	eventorganiser_restore_filters( 'loop_start');
	eventorganiser_restore_filters( 'the_content');

	//Make sure we're at the start of the loop
	wp_reset_query();

	//Sanity check
	if( !is_tax('event-venue') && !is_tax('event-category') && !is_tax('event-tag') && !is_post_type_archive('event') )
		return $page_content;

	//Start object buffering
	ob_start();

	/* 'Headers' */
	if( is_tax('event-venue') ){
		$venue_id = get_queried_object_id();

		//Get venue address
		$venue_address =eo_get_venue_address($venue_id);
		$venue_address = array_filter($venue_address);
		printf('<p>%s</p>',implode(', ', $venue_address));

		//Get the description and print it if it exists
		$venue_description =eo_get_venue_description($venue_id);
		if( !empty($venue_description) ){
			//If the venue has a description display it
			printf('<div class="venue-archive-meta">%s</div>',$venue_description);
		 }

		//Display map
		echo '<p>'.eo_get_venue_map($venue_id).'</p>';

	}elseif( is_tax('event-category') || is_tax('event-tag') ){
		$category_description = term_description();
		if ( ! empty( $category_description ) )
			echo '<div class="category-archive-meta">' . $category_description . '</div>';
	}

	/* The loop */
	if( have_posts() ):
		global $wp_query;
		if ( $wp_query->max_num_pages > 1 ) : ?>
			<nav id="nav-above">
				<div class="nav-next events-nav-newer"><?php next_posts_link( __( 'Later events <span class="meta-nav">&rarr;</span>' , 'eventorganiser' ) ); ?></div>
				<div class="nav-previous events-nav-newer"><?php previous_posts_link( __( ' <span class="meta-nav">&larr;</span> Newer events', 'eventorganiser' ) ); ?></div>
			</nav><!-- #nav-above -->
		<?php endif; 

		while( have_posts() ): the_post();
			include(EVENT_ORGANISER_DIR.'templates/loop-event-archive.php');
		endwhile;

		if ( $wp_query->max_num_pages > 1 ) : ?>
			<nav id="nav-below">
				<div class="nav-next events-nav-newer"><?php next_posts_link( __( 'Later events <span class="meta-nav">&rarr;</span>' , 'eventorganiser' ) ); ?></div>
				<div class="nav-previous events-nav-newer"><?php previous_posts_link( __( ' <span class="meta-nav">&larr;</span> Newer events', 'eventorganiser' ) ); ?></div>
			</nav><!-- #nav-below -->
		<?php endif;

	endif;

	//End object buffering
	$new_content = ob_get_contents();
	ob_end_clean();

	/*Ensure that the_content will only be called once in this theme */
	$wp_query->current_post = $wp_query->post_count-1;

	return $new_content;
}



function eventorganiser_remove_filters($tag){

	global $eventorganiser_removed_filters,$wp_filter, $merged_filters;
/*
	if( isset($wp_filter[$tag]) ) {
		if( false !== $priority && isset($wp_filter[$tag][$priority]) ){
			$eventorganiser_removed_filters[$tag][$priority] =  $wp_filter[$tag][$priority];
			unset($wp_filter[$tag][$priority]);

		}else{
			unset($wp_filter[$tag]);
		}
	}

	if( isset($merged_filters[$tag]) )
		unset($merged_filters[$tag]);

	return true;
*/
}

function eventorganiser_restore_filters($tag){




}

?>
