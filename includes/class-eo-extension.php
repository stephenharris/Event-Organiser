<?php
if ( ! class_exists( 'EO_Extension' ) ) {
/**
 * Useful abstract class which can be utilised by extensions
 * @ignore
 */
abstract class EO_Extension {

	private static $extensions = [];

	public $slug;

	public $name = false;

	public $label;

	public $public_url;

	public $api_url = 'http://wp-event-organiser.com';

	public $id;

	public $dependencies = false;

	public function __construct() {
		$this->hooks();
	}

	public static function hasExtensions() {
		return !empty(self::$extensions);
	}

	/**
	 * Returns true if event organsier version is $v or higher
	 */
	static function eo_is_after( $v ) {
		$installed_plugins = get_plugins();
		$eo_version = isset( $installed_plugins['event-organiser/event-organiser.php'] )  ? $installed_plugins['event-organiser/event-organiser.php']['Version'] : false;
		return ( $eo_version && ( version_compare( $eo_version, $v ) >= 0 )  );
	}


	/**
	 * Get's current version of installed plug-in.
	 */
	public function get_current_version() {
		$plugins = get_plugins();

		if ( ! isset( $plugins[$this->slug] ) ) {
			return false;
		}

		$plugin_data = $plugins[$this->slug];
		return $plugin_data['Version'];
	}


	/* Check that the minimum required dependency is loaded */
	public function check_dependencies() {

		$installed_plugins = get_plugins();

		if ( empty( $this->dependencies ) ) {
			return;
		}

		foreach ( $this->dependencies as $dep_slug => $dep ) {

			if ( ! isset( $installed_plugins[$dep_slug] ) ) {
				$this->not_installed[] = $dep_slug;

			} elseif ( -1 == version_compare( $installed_plugins[$dep_slug]['Version'], $dep['version'] )  ) {
				$this->outdated[] = $dep_slug;

			} elseif ( ! is_plugin_active( $dep_slug ) ) {
				$this->not_activated[] = $dep_slug;
			}
		}

		/* If dependency does not exist - uninstall. If the version is incorrect, we'll try to cope */
		if ( ! empty( $this->not_installed ) ) {
			deactivate_plugins( $this->slug );
		}

		if ( ! empty( $this->not_installed )  || ! empty( $this->outdated )  || ! empty( $this->not_activated ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	public function admin_notices() {

		echo '<div class="notice notice-success updated">';

		//Display warnings for uninstalled dependencies
		if ( ! empty( $this->not_installed )  ) {
			foreach (  $this->not_installed as $dep_slug ) {
				printf(
					'<p> <strong>%1$s</strong> has been deactivated as it requires %2$s (version %3$s or higher). Please <a href="%4$s"> install %2$s</a>.</p>',
					$this->label,
					$this->dependencies[$dep_slug]['name'],
					$this->dependencies[$dep_slug]['version'],
					$this->dependencies[$dep_slug]['url']
				);
			}
		}

		//Display warnings for outdated dependencides.
		if ( ! empty( $this->outdated ) && 'update-core' != get_current_screen()->id ) {
			foreach (  $this->outdated as $dep_slug ) {
				printf(
					'<p><strong>%1$s</strong> requires version %2$s <strong>%3$s</strong> or higher to function correctly. Please update <strong>%2$s</strong>.</p>',
					$this->label,
					$this->dependencies[$dep_slug]['name'],
					$this->dependencies[$dep_slug]['version']
				);
			}
		}

		//Display notice for activated dependencides
		if ( ! empty( $this->not_activated )  ) {
			foreach (  $this->not_activated as $dep_slug ) {
				printf(
					'<p><strong>%1$s</strong> requires %2$s to function correctly. Click to <a href="%3$s" >activate <strong>%2$s</strong></a>.</p>',
					$this->label,
					$this->dependencies[$dep_slug]['name'],
					wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $dep_slug, 'activate-plugin_' . $dep_slug )
				);
			}
		}

		echo '</div>';
	}


	public function hooks() {

		self::$extensions[] = array(
			'id' => $this->id,
			'slug' => $this->slug,
			'label' => $this->label,
		);
		add_action( 'admin_init', array( $this, 'check_dependencies' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_endpoint' ) );

		add_action( 'in_plugin_update_message-' . $this->slug, array( $this, 'plugin_update_message' ), 10, 2 );

		if ( is_multisite() ) {
			//add_action( 'network_admin_menu', array( 'EO_Extension', 'setup_ntw_settings' ) );
			add_action( 'network_admin_menu', array( $this, 'add_multisite_field' ) );
			add_action( 'wpmu_options', array( 'EO_Extension', 'do_ntw_settings' ) );
		} else {
			add_action( 'eventorganiser_register_tab_general', array( __CLASS__, 'add_field' ) );
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );

		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 9999, 3 );
	}

	static public function register_endpoint() 
	{
		register_rest_route('eventorg/v1', '/license/?', array(
			'show_in_rest' => true,
			'methods' => 'POST',
			'callback' => [__CLASS__, 'handle_validate']
		));

		register_rest_route('eventorg/v1', '/remove-license/?', array(
			'show_in_rest' => true,
			'methods' => 'POST',
			'callback' => [__CLASS__, 'handle_dissociate']
		));
	}

	static public function handle_validate(\WP_REST_Request  $request) {
		$input = $request->get_json_params();		
		$license = strtoupper( str_replace( '-', '', $input['key'] ) );

		delete_transient($input['id'] . '_status_' . $license);

		$response = self::validate_license($license, $input['id'], $input['item']);
		
		update_site_option( $input['id'] . '_license', $license );

		return new \WP_REST_Response($response); 
	}

	static public function handle_dissociate(\WP_REST_Request  $request) {
		$input = $request->get_json_params();		
		$license = strtoupper( str_replace( '-', '', $input['key'] ) );

		delete_transient($input['id'] . '_status_' . $license);

		$resp = wp_remote_post("http://wp-event-organiser.dev/wp-json/wpeo/v1/licenses/dissociate", array(
			'method'  => 'POST',
			'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
			'timeout' => 45,
			'body'    => json_encode(array(
				'license'    => $license,
				'product'    => $slug,
				'domain'     => \get_site_option('siteurl'),
			)),
		));

		update_site_option( $input['id'] . '_license', "" );

		return new \WP_REST_Response([
			'success' => true,
			'license' => $license,
			'input' => $input,
			'resp' => wp_remote_retrieve_body($resp)
		]); 
	}

	public static function validate_license($license, $id, $slug) {

		if ( $check = get_transient( $id . '_status_' . $license) ) {
			return [
				'status'       => $check['status'],
				'expires'      => $check['expires'],
				'last_checked' => $check['last_checked']
			]; 
		}


		$resp = wp_remote_post("http://wp-event-organiser.dev/wp-json/wpeo/v1/licenses/associate", array(
			'method'  => 'POST',
			'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
			'timeout' => 45,
			'body'    => json_encode(array(
				'license'    => $license,
				'product'    => $slug,
				'domain'     => \get_site_option('siteurl'),
			)),
		));

		$body = json_decode(wp_remote_retrieve_body($resp), true);
		$payload = $body;

		$response = [
			'status' => $payload['status'],
			'expires' => empty($payload['expires']) ? null : $payload['expires'],
			'last_checked' => date('c'),
			'body' => $payload
		];

		set_transient( $id . '_status_' . $license, $response, 14 * 24 * 60 * 60);
		
		return $response;
	}

	public function plugin_update_message( $plugin_data, $r ) {

		$response = self::validate_license(get_site_option($this->id . '_license' ), $this->id, $this->slug);

		if ( $response['status'] !== 'valid' ) {
			printf(
				'<br> The license key you have entered is invalid.
			<a href="%s"> Purchase a license key </a> or enter a valid license key <a href="%s">here</a>',
				$this->public_url,
				admin_url( 'options-general.php?page=event-settings' )
			);
		}
	}

	public function add_multisite_field() {

		register_setting( 'settings-network', $this->id . '_license' );

		add_settings_section( 'eo-ntw-settings', 'Event Organiser Extension Licenses', '__return_false', 'settings-network' );

		add_settings_field(
			$this->id . '_license',
			$this->label,
			array( $this, 'field_callback' ),
			'settings-network',
			'eo-ntw-settings'
		);
	}

	static function do_ntw_settings() {
		wp_nonce_field( 'eo-ntw-settings-options', '_eontwnonce' );
		do_settings_sections( 'settings-network' );
	}

	public static function add_field() {

		register_setting( 'eventorganiser_general', 'eo_license' );
		$section_id = 'general_licence';
		
		wp_enqueue_script( 'license-field', EVENT_ORGANISER_URL.'js/dist/license-field.js',array(
			'react',
			'react-dom',
			'wp-element',
		),$version,true);

		wp_localize_script('license-field', 'eoLicenses', array(
			'extensions' => array_map(function($extension){
				$license = get_site_option( $extension['id'] . '_license' );
				$response = self::validate_license($license, $extension['id'], $extension['slug']);
				return array_merge($extension, $response, array(
					'key' => $license,
				));
			}, self::$extensions)
		));
	}

	public function plugin_info( $check, $action, $args ) {
		if ( isset( $args->slug ) && basename( $args->slug, '.php' ) == basename( $this->slug, '.php' ) ) {
			$obj = $this->get_remote_plugin_info( 'plugin_info' );
			return $obj;
		}
		return $check;
	}

	/**
	 * Fired just before setting the update_plugins site transient. Remotely checks if a new version is available
	 */
	public function check_update( $transient ) {

		/**
		 * wp_update_plugin() triggers this callback twice by saving the transient twice
		 * The repsonse is kept in a transient - so there isn't much of it a hit.
		 */

		//Get remote information
		$plugin_info = $this->get_remote_plugin_info( 'plugin_info' );

		// If a newer version is available, add the update
		if ( $plugin_info && version_compare( $this->get_current_version(), $plugin_info->new_version, '<' ) ) {

			$obj = new stdClass();
			$obj->slug        = basename( $this->slug, '.php' );
			$obj->plugin      = basename( $this->slug, '.php' );
			$obj->new_version = $plugin_info->new_version;
			$obj->package     = $plugin_info->download_link;

			if ( isset( $plugin_info->sections['upgrade_notice'] ) ) {
				$obj->upgrade_notice = $plugin_info->sections['upgrade_notice'];
			}

			//Add plugin to transient.
			$transient->response[$this->slug] = $obj;
		}

		return $transient;
	}


	/**
	 * Return remote data
	 * Store in transient for 12 hours for performance
	 *
	 * @param (string) $action -'info', 'version' or 'license'
	 * @return mixed $remote_version
	 */
	public function get_remote_plugin_info( $action = 'plugin_info' ) {

		$key = wp_hash( 'plm_' . $this->id . '_' . $action . '_' . $this->slug );
		if ( false !== ( $plugin_obj = get_site_transient( $key ) ) && ! $this->force_request() ) {
			return $plugin_obj;
		}

		$request = wp_remote_post( $this->api_url, array(
			'method' => 'POST',
			'timeout' => 45,
			'body' => array(
				'plm-action' => $action,
				'license'    => get_site_option( $this->id . '_license' ),
				'product'    => $this->slug,
				'domain'     => \get_site_option('siteurl'),
			),
		));

		if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			//If its the plug-in object, unserialize and store for 12 hours.
			$plugin_obj = ( 'plugin_info' == $action ? unserialize( $request['body'] ) : $request['body'] );

			if ( $this->name && empty( $plugin_obj->name ) ) {
				$plugin_obj->name = $this->name;
			}

			set_site_transient( $key, $plugin_obj, 12 * 60 * 60 );
			return $plugin_obj;
		}
		//Don't try again for 5 minutes
		set_site_transient( $key, '', 5 * 60 );
		return false;
	}


	public function force_request() {

		//We don't use get_current_screen() because of conclict with InfiniteWP
		global $current_screen;

		if ( ! isset( $current_screen ) ) {
			return false;
		}

		return isset( $current_screen->id ) && ( 'plugins' == $current_screen->id || 'update-core' == $current_screen->id );
	}
}
}