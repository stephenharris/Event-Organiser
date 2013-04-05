<?php
/**
 * Checks if wp_footer() is called.
 */
class EventOrganiser_No_WP_Footer
{

	/**
	 * Instance
	 * @static
	 * @access private
	 * @var object
	 */
	private static $instance;

	/**
	 * Was `wp_footer()` fired?
	 * @static
	 * @var   bool
	 */
	static $footer_fired = false;

	/**
	 * Creates a new static instance
	 * @static
	 * @return void
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook the functions.
	 * Check for wp_footer() only on admin pages (via shutdown).
	 * Display notice only if the option 'sh_no_wp_footer' is present in the database, but is not set to 'suppress'.
	 */
	private function __construct() {
		add_action( 'shutdown', array( __CLASS__, 'did_footer' ) );
	}

	/**
	 * Checks if wp_footer() was fired.
	 * Hooked onto shutdown.
	 * If wp_footer() wasn't fired it sets 'sh_no_wp_footer' option to true, unless it already set to 'supress'
	 * If wp_footer() was fired it removes the 'sh_no_wp_footer' option.
	 */
	static function did_footer(){
		if( !did_action( 'wp_footer' ) )
			update_option( 'eo_wp_footer_present', -1 );
		elseif( did_action( 'wp_footer' ) )
			update_option( 'eo_wp_footer_present', 1 );
	}
}
$eo_no_wp_footer = EventOrganiser_No_WP_Footer::get_instance();