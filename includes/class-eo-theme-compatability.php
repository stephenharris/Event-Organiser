<?php
/**
 * Theme compatability class inspired by bbPress
 *
 * When 'theme  compatability' is enabled the plug-in does the following:
 * - Buffers the events loop for events pages (but not single.php)
 * - Forces the template to be page.php (or similar). The list of appropriate templates is
 * filterable.
 * - Uses the buffered event loop as the 'page' content.
 * - Adds eo-tc-page class to the body/page class
 * - Adds eo-tc-event class to each event
 * - Frontend stylesheet contains rules which reference eo-tc-page/eo-tc-event classes
 *
 * @since 3.0.0
 * @see https://core.trac.wordpress.org/ticket/20509
 * @see https://core.trac.wordpress.org/ticket/22355
 * @see https://bbpress.trac.wordpress.org/ticket/2343
 *
 * Questions
 * 1. Should we load styles after the theme's style.css (but risk that wp_footer is not triggered?)
 * 2. Should we use loop start/end to remove all content/excerpt filters and restore them again, or
 * just clobber the callbacks by making sure we're last. Or both?
 */
class EO_Theme_Compat {

	/**
	 * Singleton instance.
	 */
	private static $instance = false;

	protected function __construct() {
	}

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Starts the ball rolling
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'setup_hooks' ), $this->highest_priority( 'template_redirect' ) );
	}

	function add_filter( $filter, $priority = 10, $accepted_args = 1 ) {
		add_filter( $filter, array( $this, $filter ), $priority, $accepted_args );
	}

	function remove_filter( $filter, $priority = 10 ) {
		remove_filter( $filter, array( $this, $filter ), $priority );
	}

	/**
	 * Given a tag returns the highest existing callback priority + 1
	 */
	function highest_priority( $tag ) {
		global $wp_filter;

		if ( isset( $wp_filter[$tag] ) ) {
			if ( is_array( $wp_filter[$tag] ) ) {
				return max( array_keys( $wp_filter[$tag] ) ) + 1;
			} elseif ( $wp_filter[$tag] instanceof WP_Hook && is_array( $wp_filter[$tag]->callbacks ) ) {
				//@see https://core.trac.wordpress.org/ticket/17817
				return max( array_keys( $wp_filter[$tag]->callbacks ) ) + 1;
			} else {
				return 9999;
			}
		}

		return 1;
	}

	/**
	 * We set up our template_include callback, where most of the work is done.
	 * We used the template_redirect template to add our callback as late as possible.
	 */
	function setup_hooks() {
		$this->add_filter( 'template_include', $this->highest_priority( 'template_include' ) );
	}

	/**
	 * Main body of the class. If theme compatability is enabled and it is an events page (not
	 * single event), and no appropriate template has been found we create a dummy post, and use
	 * the page.php template. The content of the dummy post is the events loop.
	 */
	function template_include( $template ) {

		//Has EO template handling been turned off?
		if ( 'themecompat' !== eventorganiser_get_option( 'templates' ) || get_theme_support( 'event-organiser' ) ) {
			return $template;
		}

		//We only care about events/venue/category/tag pages
		if ( ! is_post_type_archive( 'event' ) && ! is_tax( 'event-venue' ) && ! is_tax( 'event-category' )  && ! is_tax( 'event-tag' ) ) {
			return $template;
		}

		//Set the title, and where appropriate, pre-content (term description, venue map etc).
		$precontent = false;
		if ( is_tax( 'event-venue' ) ) {
			$venue_id = get_queried_object_id();
			$title = sprintf(
				__( 'Events at %s','eventorganiser' ).' '.eo_get_event_archive_date( 'jS F Y' ),
				'<span>' . eo_get_venue_name( $venue_id ) . '</span>'
			);

			if ( $venue_description = eo_get_venue_description( $venue_id ) ) {
				$precontent = '<div class="venue-archive-meta">'.$venue_description.'</div>';
			}
			$precontent .= eo_get_venue_map( $venue_id, array( 'width' => '100%' ) );

		} elseif ( is_tax( 'event-category' ) ) {
			$title = sprintf(
				__( 'Event Category: %s', 'eventorganiser' ),
				'<span>' . single_cat_title( '', false ) . '</span>'
			);

			$tag_description = category_description();
			if ( ! empty( $tag_description ) ) {
				$precontent = apply_filters( 'category_archive_meta', '<div class="category-archive-meta">' . $tag_description . '</div>' );
			}
		} elseif ( is_tax( 'event-tag' ) ) {
			$title = sprintf(
				__( 'Event Tag: %s', 'eventorganiser' ),
				'<span>' . single_cat_title( '', false ) . '</span>'
			);

			$tag_description = category_description();
			if ( ! empty( $tag_description ) ) {
				$precontent = apply_filters( 'category_archive_meta', '<div class="category-archive-meta">' . $tag_description . '</div>' );
			}
		} elseif ( eo_is_event_archive( 'day' ) ) {
			$title = __( 'Events: ','eventorganiser' ).' '.eo_get_event_archive_date( 'jS F Y' );
		} elseif ( eo_is_event_archive( 'month' ) ) {
			$title = __( 'Events: ','eventorganiser' ).' '.eo_get_event_archive_date( 'F Y' );
		} elseif ( eo_is_event_archive( 'year' ) ) {
			$title = __( 'Events: ','eventorganiser' ).' '.eo_get_event_archive_date( 'Y' );
		} else {
			$title = __( 'Events','eventorganiser' );
		}

		//Set the content - this is just the events loop.
		$priority = max( 10, $this->highest_priority( 'post_class' ) );
		$this->add_filter( 'post_class', $priority, 10, 3 ); //Injecting our eo-tc-event class
		ob_start();
		eo_get_template_part( 'eo-loop-events' );
		$content = ob_get_clean();
		$this->remove_filter( 'post_class', $priority );

		//Create the dummy post
		$this->reset_post( array(
			'eo_theme_compat' => true,
			'post_title'      => $title,
			'post_content'    => $precontent . $content,
			'post_excerpt'    => $precontent . $content,
			'post_type'       => 'event',
			'comment_status'  => 'closed',
			'is_archive'      => true,
		) );

		//If triggered these remove all callbacks (including two below) so that the content is set as above
		//Not all theme page.php templates contain a loop. In which case, we fallback to the two callbacks below
		add_action( 'loop_start', array( $this, 'remove_content_filters' ) ,  9999 );
		add_action( 'loop_end',   array( $this, 'restore_content_filters' ),   -9999 );

		//Fallback to ensure the content is as set above
		add_filter( 'the_content', array( $this, 'replace_page_content' ), $this->highest_priority( 'the_content' ) );
		add_filter( 'the_excerpt', array( $this, 'replace_page_content' ), $this->highest_priority( 'the_excerpt' ) );

		//Injecting our eo-tc-page class - use (dummy) post and body class as theme might not call one or the other
		add_filter( 'post_class', array( $this, 'post_class_events_page' ), $priority, 3 );
		$this->add_filter( 'body_class', $this->highest_priority( 'body_class' ), 2 );

		//Load template
		$template = locate_template( $this->get_theme_compat_templates() );

		//Ensure our styles are loaded
		add_action( 'wp_footer', array( $this, 'load_styles' ) );
		//$this->load_styles();

		return $template;

	}
	/**
	 * A list of templates that we will try to use (in the order they are attempted)
	 * This is filterable via `eventorganiser_theme_compatability_templates`
	 */
	function get_theme_compat_templates() {
		$templates = array(
			'plugin-event-organiser.php',
			'generic.php',
			'page.php',
			'single.php',
			'index.php',
		);

		$templates = apply_filters( 'eventorganiser_theme_compatability_templates', $templates );

		return $templates;
	}

	/**
	 * Injects 'eo-tc-event' class to events in theme compatabilty mode.
	 */
	function post_class( $classes, $class, $post_id ) {

		if ( 'event' == get_post_type( $post_id ) ) {
			$classes[] = 'eo-tc-event';
		}

		return $classes;
	}

	/**
	 * Injects 'eo-tc-page' class to body in theme compatabilty mode.
	 */
	function body_class( $classes, $class ) {

		global $post;

		if ( $this->shadow_post->ID === $post->ID && ! empty( $post->eo_theme_compat ) ) {
			$classes[] = 'eo-tc-page';
		}

		return $classes;
	}

	/**
	 * Injects 'eo-tc-page' class to dummy post in theme compatabilty mode.
	 */
	function post_class_events_page( $classes, $class, $post_id ) {
		global $post;

		if ( $this->shadow_post->ID === $post_id && ! empty( $post->eo_theme_compat ) ) {
			$classes[] = 'eo-tc-page';
		}

		return $classes;
	}

	/**
	 * Ensure dummy page content is as we want it
	 */
	function replace_page_content( $content ) {
		global $post;

		if ( $this->shadow_post->ID === $post->ID && ! empty( $post->eo_theme_compat ) ) {
			return $this->shadow_post->post_content;
		}

		return $content;
	}

	/**
	 * Removes all filters for the dummy page content
	 * May not be called, in which case we fallback on replace_page_content();
	 */
	function remove_content_filters( $query ) {
		$the_post = $query->post;
		if ( $this->shadow_post->ID === $the_post->ID && ! empty( $the_post->eo_theme_compat ) ) {
			$this->remove_filters( 'the_excerpt' );
			$this->remove_filters( 'get_the_excerpt' );
			$this->remove_filters( 'the_content' );
		}
	}

	/**
	 * Restores all removed filters
	 * May not be called
	 */
	function restore_content_filters( $query ) {
		$the_post = $query->post;
		if ( $this->shadow_post->ID === $the_post->ID && ! empty( $the_post->eo_theme_compat ) ) {
			$this->restore_filters( 'the_excerpt' );
			$this->restore_filters( 'get_the_excerpt' );
			$this->restore_filters( 'the_content' );
		}
	}

	/**
	 * Enqueues the front-end stylesheet
	 */
	function load_styles() {
		wp_enqueue_style( 'eo_front' );
	}

	/**
	 * Creates a dummy post modifies the globals $wp_query, $post
	 */
	function reset_post( $args ) {

		global $wp_query, $post;

		// Switch defaults if post is set
		if ( false ) {
			$dummy = wp_parse_args( $args, array(
				'ID'                    => $wp_query->post->ID,
				'post_status'           => $wp_query->post->post_status,
				'post_author'           => $wp_query->post->post_author,
				'post_parent'           => $wp_query->post->post_parent,
				'post_type'             => $wp_query->post->post_type,
				'post_date'             => $wp_query->post->post_date,
				'post_date_gmt'         => $wp_query->post->post_date_gmt,
				'post_modified'         => $wp_query->post->post_modified,
				'post_modified_gmt'     => $wp_query->post->post_modified_gmt,
				'post_content'          => $wp_query->post->post_content,
				'post_title'            => $wp_query->post->post_title,
				'post_excerpt'          => $wp_query->post->post_excerpt,
				'post_content_filtered' => $wp_query->post->post_content_filtered,
				'post_mime_type'        => $wp_query->post->post_mime_type,
				'post_password'         => $wp_query->post->post_password,
				'post_name'             => $wp_query->post->post_name,
				'guid'                  => $wp_query->post->guid,
				'menu_order'            => $wp_query->post->menu_order,
				'pinged'                => $wp_query->post->pinged,
				'to_ping'               => $wp_query->post->to_ping,
				'ping_status'           => $wp_query->post->ping_status,
				'comment_status'        => $wp_query->post->comment_status,
				'comment_count'         => $wp_query->post->comment_count,
				'filter'                => $wp_query->post->filter,

				'is_404'                => false,
				'is_page'               => false,
				'is_single'             => false,
				'is_archive'            => false,
				'is_tax'                => false,
			) );
		} else {
			$dummy = wp_parse_args( $args, array(
				'ID'                    => -9999,
				'post_status'           => 'publish',
				'post_author'           => 0,
				'post_parent'           => 0,
				'post_type'             => 'page',
				'post_date'             => 0,
				'post_date_gmt'         => 0,
				'post_modified'         => 0,
				'post_modified_gmt'     => 0,
				'post_content'          => '',
				'post_title'            => '',
				'post_excerpt'          => '',
				'post_content_filtered' => '',
				'post_mime_type'        => '',
				'post_password'         => '',
				'post_name'             => '',
				'guid'                  => '',
				'menu_order'            => 0,
				'pinged'                => '',
				'to_ping'               => '',
				'ping_status'           => '',
				'comment_status'        => 'closed',
				'comment_count'         => 0,
				'filter'                => 'raw',

				'is_404'                => false,
				'is_page'               => false,
				'is_single'             => false,
				'is_archive'            => false,
				'is_tax'                => false,
			) );
		}

		// Bail if dummy post is empty
		if ( empty( $dummy ) ) {
			return;
		}

		// Set the $post global
		$post = new WP_Post( (object) $dummy );
		$this->shadow_post = $post;

		// Copy the new post global into the main $wp_query
		$wp_query->post       = $post;
		$wp_query->posts      = array( $post );

		// Prevent comments form from appearing
		$wp_query->post_count = 1;
		$wp_query->is_404     = $dummy['is_404'];
		$wp_query->is_page    = $dummy['is_page'];
		$wp_query->is_single  = $dummy['is_single'];
		$wp_query->is_archive = $dummy['is_archive'];
		$wp_query->is_tax     = $dummy['is_tax'];

		$wp_query->is_singular = $wp_query->is_single;

		// Clean up the dummy post
		unset( $dummy );

		if ( ! $wp_query->is_404() ) {
			status_header( 200 );
		}

	}

	/**
	 * Remove all callbacks for a particular hook $tag.
	 */
	function remove_filters( $tag ) {

		global $wp_filter, $merged_filters;

		//Filters exist
		if ( isset( $wp_filter[$tag] ) ) {

			// Store filters in a backup
			$this->wp_filter[$tag] = $wp_filter[$tag];

			// Unset the filters
			unset( $wp_filter[$tag] );
		}

		// Check merged filters
		if ( isset( $merged_filters[$tag] ) ) {

			// Store filters in a backup
			$this->merged_filters[$tag] = $merged_filters[$tag];

			// Unset the filters
			unset( $merged_filters[$tag] );
		}

	}

	/**
	 * Restores all callbacks for a particular hook $tag.
	 */
	function restore_filters( $tag ) {

		global $wp_filter, $merged_filters;

		if ( isset( $this->wp_filter[$tag] ) ) {
			// Store filters in a backup
			$wp_filter[$tag] = $this->wp_filter[$tag];
			// Unset the filters
			unset( $this->wp_filter[$tag] );
		}

		// Check merged filters
		if ( isset( $this->merged_filters[$tag] ) ) {
			// Store filters in a backup
			$merged_filters[$tag] = $this->merged_filters[$tag];
			// Unset the filters
			unset( $this->merged_filters[$tag] );
		}
		return true;

	}

}
$compat = EO_Theme_Compat::get_instance();
$compat->init();