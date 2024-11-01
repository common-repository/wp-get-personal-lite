<?php

add_action( 'admin_init', 'wpgp_nag_ignore' );
function wpgp_nag_ignore() {
	global $current_user;
	$user_id = $current_user->ID;
	/* If user clicks to ignore the notice, add that to their user meta */
	if ( isset( $_GET['wpgp_admin_notice_register_ignore'] ) && '0' == $_GET['wpgp_admin_notice_register_ignore'] ) {
		add_user_meta( $user_id, 'wpgp_admin_notice_register', 'true', true );
	}
}


// add the admin options page
add_action( 'admin_menu', 'wpgp_plugin_admin_add_page' );
function wpgp_plugin_admin_add_page() {
	add_options_page( 'WP Get Personal', 'WP Get Personal', 'manage_options', 'wpgetpersonal', 'wpgp_options_page' );
}

// display the admin options page
function wpgp_options_page() {
	?>
	<div>
		<h2>WP Get Personal Settings</h2>
		Version: <?php echo wpgp_get_version(); ?>
		<form action="options.php" method="post">
			<?php settings_fields( 'wpgp_advanced_options' ); ?>
			<?php do_settings_sections( 'wpgetpersonal-advanced-options' ); ?>

			<p class="submit">
				<input id="submit" class="button-primary" type="submit" value="Save settings" name="submit">
			</p>
		</form>
	</div>
	<?php
}

// add the admin settings
add_action( 'admin_init', 'wpgp_plugin_admin_init' );
function wpgp_plugin_admin_init() {
	// validation settings
	register_setting( 'wpgp_validation_options', 'wpgp_validation_options', 'wpgp_validation_options_validate' );
	add_settings_section( 'wpgp_main', 'Activation', 'wpgp_validation_section_text', 'wpgetpersonal-activation' );
	add_settings_field( 'activation_key', 'Activation key:', 'wpgp_setting_string', 'wpgetpersonal-activation', 'wpgp_main', 'activation_key' );

	// advanced settings
	register_setting( 'wpgp_advanced_options', 'wpgp_advanced_options', 'wpgp_advanced_options_validate' );
	add_settings_section( 'wpgp_advanced_options_main', 'Advanced Settings', 'wpgp_plugin_section_text', 'wpgetpersonal-advanced-options' );
	add_settings_field( 'wpgp_cookie_life', 'Cookie life (days):', 'wpgp_advanced_options_string', 'wpgetpersonal-advanced-options', 'wpgp_advanced_options_main', 'wpgp_cookie_life' );
	add_settings_field( 'wpgp_section_separator', 'Section separator (valid filename characters only):', 'wpgp_advanced_options_string', 'wpgetpersonal-advanced-options', 'wpgp_advanced_options_main', 'wpgp_section_separator' );

}

// validation settings
function wpgp_validation_options_validate( $input ) {

	// Verify against the mothership
	$options                      = get_option( 'wpgp_validation_options' );
	$options['activation_key']    = $input['activation_key'];
	$mothership                   = 'http://support.wpgetpersonal.com';
	$options['request_type']      = 'verify';
	$options['plugin_slug']       = 'wpgetpersonal';
	$options['activation_status'] = '';
	$options['activation_date']   = current_time( 'mysql' );
	$url                          = $mothership . '/?' . http_build_query( $options );
	$result                       = wp_remote_get( $url );
	if ( ! is_array( $result ) or ! is_array( $result['response'] ) or $result['response']['code'] != 200 ) {
		$options['activation_status'] = 'failed';

	} elseif ( $result['body'] != 'verified' ) {

		$options['activation_status'] = 'false';


	} else {
		$options['activation_status'] = 'true';

	}

	return $options;
}

function wpgp_advanced_options_validate( $input ) {


	return $input;
}


function wpgp_validation_section_text() {
	echo '<p>To receive updates you need to activate the plugin. Please enter your activation key. If you haven\'t received your activation key please log in to your downloads page on the <a href="http://support.wpgetpersonal.com">support desk</a>.';
	$options = get_option( 'wpgp_validation_options' );
	if ( is_array( $options ) and isset( $options['activation_key'] ) ) {
		$date_format = get_option( 'date_format' );
		$mysql_date  = $options['activation_date'];
		if ( $options['activation_status'] == 'true' ) {
			$note = "Activation status: <b>activated</b> on " . mysql2date( $date_format, $mysql_date );
			echo sprintf( '<div class="updated fade"><p>%s</p></div>', $note );
		} elseif ( $options['activation_status'] == 'false' ) {
			$note = "Activation failed. If you feel this is an error, please get in touch with support. Last activation attempt: " . mysql2date( $date_format, $mysql_date );
			echo sprintf( '<div class="error fade"><p>%s</p></div>', $note );
		} elseif ( $options['activation_status'] == 'failed' ) {
			$note = "Unable to communicate with the activation server. Last activation attempt: " . mysql2date( $date_format, $mysql_date );
			echo sprintf( '<div class="error fade"><p>%s</p></div>', $note );
		}

	}
}


function wpgp_setting_string( $what ) {
	$options = get_option( 'wpgp_validation_options' );
	$option  = $options[ $what ];
	$name    = $id = $what;
	switch ( $what ) {
		default:
			$type = 'text';
			break;
	}
	echo "<input id=\"$id\" name=\"wpgp_validation_options[$name]\" type=\"$type\" value=\"" . esc_attr( $option ) . "\" class=\"regular-text\"/>";
}

// end validation settings

// advanced settings
function wpgp_plugin_section_text() {

}

function wpgp_advanced_options_string( $what ) {
	$options = get_option( 'wpgp_advanced_options' );
	$option  = $options[ $what ];
	$name    = $id = $what;
	switch ( $what ) {
		default:
			$type = 'text';
			break;
	}
	echo "<input id=\"$id\" name=\"wpgp_advanced_options[$name]\" type=\"$type\" value=\"" . esc_attr( $option ) . "\" class=\"small-text\"/>";
}

add_filter( 'puc_request_info_query_args-wpgetpersonal', 'wpgp_request_info_query_args' );
function wpgp_request_info_query_args( $queryArgs ) {
	$options = get_option( 'wpgp_validation_options' );

	$a['activation_key'] = isset( $options['activation_key'] ) ? $options['activation_key'] : "";
	$a['plugin_slug']    = isset( $options['plugin_slug'] ) ? $options['plugin_slug'] : "wpgetpersonal";

	$queryArgs += $a;

	return $queryArgs;
}

