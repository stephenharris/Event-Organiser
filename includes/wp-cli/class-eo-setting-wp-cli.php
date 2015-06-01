<?php

/**
 * Manage Event Organiser settings
 *
 * ## EXAMPLES
 *
 *     wp option get disable_css
 *
 *     wp option update dateformat d-m-Y
 *
 */
class EO_Setting_CLI_Command extends WP_CLI_Command {

	/**
	 * Get an option.
	 *
	 * @synopsis <key> [--format=<format>]
	 */
	public function get( $args, $assoc_args ) {
		list( $key ) = $args;

		$value = eventorganiser_get_option( $key );

		if ( false === $value ){
			die(1);
		}

		WP_CLI::print_value( $value, $assoc_args );
	}


	/**
	 * Update a setting.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The name of the option to add.
	 *
	 * [<value>]
	 * : The new value. If ommited, the value is read from STDIN.
	 *
	 * ## EXAMPLES
	 *
	 *     # Update an option by reading from a file
	 *     wp option update dateformat d-m-Y
	 *
	 * @alias set
	 */
	public function update( $args, $assoc_args ) {
		$key = $args[0];
		
		$value = WP_CLI::get_value_from_arg_or_stdin( $args, 1 );
		$value = WP_CLI::read_value( $value, $assoc_args );

		$value = sanitize_option( $key, $value );
		$old_value = sanitize_option( $key, eventorganiser_get_option( $key ) );

		if ( $value === $old_value ) {
			WP_CLI::success( "Value passed for '$key' option is unchanged." );
		} else {
			$options = eventorganiser_get_option();
			$options[$key] = $value;
			
			if ( update_option( 'eventorganiser_options', $options ) ) {
				WP_CLI::success( "Updated '$key' option." );
			} else {
				WP_CLI::error( "Could not update option '$key'." );
			}
		}
	}

}

WP_CLI::add_command( 'eo setting', 'EO_Setting_CLI_Command' );