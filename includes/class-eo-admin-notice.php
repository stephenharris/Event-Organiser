<?php
/**
 * Helper class for adding notices to admin screen
 * <code>
 * $notice_handler = EO_Admin_Notice_Handler::get_instance();
 * $notice_handler->add_notice( 'foobar', 'screen_id', 'Notice...' );
 * </code>
 * @ignore
 * When minimum requirement is increased to 4.2 we can refactor some of this in light of
 * trac ticket 31233.
 * @see https://core.trac.wordpress.org/ticket/31233
 */
class EO_Admin_Notice_Handler {

	static $prefix = 'eventorganiser';

	static $notices = array();

	static $instance;

	/**
	 * Singleton model
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Construct the controller and listen for form submission
	 */
	private function __construct() {

		if ( did_action( 'plugins_loaded' ) ) {
			self::load();
		} else {
			add_action( 'plugins_loaded', array( __CLASS__, 'load' ) );
		}
	}

	/**
	 * Hooks the dismiss listener (ajax and no-js) and maybe shows notices on admin_notices
	 */
	static function load() {
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		add_action( 'admin_init',array( __CLASS__, 'dismiss_handler' ) );
		add_action( 'wp_ajax_' . self::$prefix . '-dismiss-notice', array( __CLASS__, 'dismiss_handler' ) );
	}

	function add_notice( $id, $screen_id, $message, $type = 'alert' ) {
		self::$notices[$id] = array(
				'screen_id' => $screen_id,
				'message'   => $message,
				'type'      => $type,
		);
	}

	function remove_notice( $id ) {
		if ( isset( self::$notices[$id] ) ) {
			unset( self::$notices[$id] );
		}
	}

	/**
	 * Print appropriate notices.
	 * Hooks EO_Admin_Notice_Handle::print_footer_scripts to admin_print_footer_scripts to
	 * print js to handle AJAX dismiss.
	 */
	static function admin_notice() {

		$screen_id = get_current_screen()->id;

		//Notices of the form ID=> array('screen_id'=>screen ID, 'message' => Message,'type'=>error|alert)
		if ( ! self::$notices ) {
			return;
		}

		$seen_notices = get_option( self::$prefix . '_admin_notices', array() );

		foreach ( self::$notices as $id => $notice ) {
			$id = sanitize_key( $id );

			//Notices cannot have been dismissed and must have a message
			if ( in_array( $id, $seen_notices ) || empty( $notice['message'] )  ) {
				continue;
			}

			$notice_screen_id = (array) $notice['screen_id'];
			$notice_screen_id = array_filter( $notice_screen_id );

			//Notices must for this screen. If empty, its for all screens.
			if ( ! empty( $notice_screen_id ) && ! in_array( $screen_id, $notice_screen_id ) ) {
				continue;
			}

			switch ( strtolower( $notice['type'] ) ) {

				case 'error':
					$class = 'notice-error error';
					break;
				case 'warning':
					$class = 'notice-warning';
					break;
				default:
					$class = 'notice-success updated';
			}

			printf(
				'<div class="notice %1$s-notice %6$s" id="%1$s-notice-%2$s">
					<form action="" method="post">
						%3$s
						<button type="submit" class="notice-dismiss %1$s-dismiss">
							<span class="screen-reader-text">%4$s</span>
						</button>
						<input type="hidden" name="action" value="%1$s-dismiss-notice" />
						<input type="hidden" name="_wpnonce" value="%5$s" />
						<input type="hidden" name="notice" value="%2$s" />
					</form>
				</div>',
				esc_attr( self::$prefix ),
				esc_attr( $id ),
				$notice['message'],
				esc_html__( 'Dismiss this notice','eventorganiser' ),
				wp_create_nonce( self::$prefix . '-dismiss-' . $id ),
				esc_attr( $class )
			);
			add_action( 'admin_print_footer_scripts', array( __CLASS__, 'print_footer_scripts' ), 11 );
		}
	}

	/**
	 * Handles AJAX and no-js requests to dismiss a notice
	 */
	static function dismiss_handler() {

		$request = array_merge( $_GET, $_POST );
		$notice  = isset( $request['notice'] ) ? $request['notice'] : false;
		if ( empty( $notice ) ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			//Ajax dismiss handler
			if ( empty( $request['notice'] )  || empty( $request['_wpnonce'] )  || $request['action'] !== self::$prefix . '-dismiss-notice' ) {
				return;
			}

			if ( ! wp_verify_nonce( $request['_wpnonce'],self::$prefix . '-ajax-dismiss' ) ) {
				return;
			}
		} else {
			//Fallback dismiss handler
			if ( empty( $request['action'] ) || empty( $request['notice'] )  || empty( $request['_wpnonce'] )  || $request['action'] !== self::$prefix . '-dismiss-notice' ) {
				return;
			}

			if ( ! wp_verify_nonce( $request['_wpnonce'],self::$prefix . '-dismiss-' . $notice ) ) {
				return;
			}
		}

		self::dismiss_notice( $notice );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			wp_die( 1 );
		}
	}

	/**
	 * Dismiss a given a notice
	 *@param string $notice The notice (ID) to dismiss
	 */
	static function dismiss_notice( $notice ) {
		$seen_notices   = get_option( self::$prefix . '_admin_notices', array() );
		$seen_notices[] = $notice;
		$seen_notices   = array_unique( $seen_notices );
		update_option( self::$prefix . '_admin_notices', $seen_notices );
	}

	/**
	 * Prints javascript in footer to handle AJAX dismiss.
	 */
	static function print_footer_scripts() {
		?>
		<style>
			.eventorganiser-notice {position: relative;}
			/* Backwards compatability with 4.1 and earlier */ 
			/* @see https://core.trac.wordpress.org/ticket/31233 */
			.eventorganiser-dismiss:before {
				background: none;
				color: #b4b9be;
				content: "\f153";
				display: block;
				font: normal 16px/20px dashicons;
				speak: none;
				height: 20px;
				text-align: center;
				width: 20px;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
			}

			.eventorganiser-dismiss {
				position: absolute;
				top: 0;
				right: 1px;
				border: none;
				margin: 0;
				padding: 9px;
				background: none;
				color: #b4b9be;
				cursor: pointer;
			}

			.eventorganiser-dismiss:hover:before,
			.eventorganiser-dismiss:active:before,
			.eventorganiser-dismiss:focus:before {
				color: #c00;
			}

			.eventorganiser-dismiss:focus {
				outline: none;
				-webkit-box-shadow: 0 0 0 1px #5b9dd9, 0 0 2px 1px rgba(30, 140, 190, .8);
				box-shadow: 0 0 0 1px #5b9dd9, 0 0 2px 1px rgba(30, 140, 190, .8);
			}

			.ie8 .eventorganiser-dismiss:focus {outline: 1px solid #5b9dd9;}

			@media screen and ( max-width: 782px ) {
				.eventorganiser-dismiss {padding: 13px;}
			}
		</style>
		<script type="text/javascript">
		jQuery(document).ready(function ($){
			var dismissClass = '<?php echo esc_js( self::$prefix . '-dismiss' );?>';
			var ajaxaction   = '<?php echo esc_js( self::$prefix . '-dismiss-notice' ); ?>';
			var _wpnonce     = '<?php echo wp_create_nonce( self::$prefix . '-ajax-dismiss' )?>';
			var noticeClass  = '<?php echo esc_js( self::$prefix . '-notice' );?>';

			jQuery('.'+dismissClass).click(function(e){
				e.preventDefault();
				var noticeID= $(this).parents('.'+noticeClass).attr('id').substring(noticeClass.length+1);
				$('#'+noticeClass+'-'+noticeID).fadeOut('slow');
				$.post(ajaxurl, 
					{
						action: ajaxaction,
						notice: noticeID,
						_wpnonce: _wpnonce
					}, 
					function (response) {
						if ('1' !== response) {
							$('#'+noticeClass+'-'+noticeID).removeClass('updated notice-success').addClass('notice-error error').show();
						}
					}
				);
			});
		});
		</script><?php
	}
}//End EO_Admin_Notice_Handler
