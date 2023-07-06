<?php
/**
 * This file acts as the entry point for the WP Super Cache Minify add-on, extending
 * the core capabilities of the WP Super Cache plugin by integrating HTML/CSS/JS minification.
 *
 * It loads the WPSC_Minify class, which encapsulates the minification logic. This class, in
 * conjunction with several hook functions defined in this file, extends the core WP Super Cache
 * plugin to support content minification.
 *
 * The hook system of the WP Super Cache plugin is used to hook these features
 * into the relevant parts of the WP Super Cache processing pipeline.
 *
 * For more details on the WP Super Cache hooks, refer to the developer's guide:
 * http://ocaoimh.ie/wp-super-cache-developers/
 *
 * @package wpsc-minify
 */

/**
 * Require the WPSC_Minify class.
 */
require_once WP_CONTENT_DIR . '/plugins/wp-super-cache-minify/src/class-wpsc-minify.php';

/**
 * This function integrates the HTML Minify configuration option into the WP Super Cache settings page.
 * It takes care of rendering the configuration form as well as updating the
 * minify setting if it has been changed.
 */
function wpsc_minify_settings() {
	// Check if minify setting has been updated, and verify the nonce to prevent CSRF attacks.
	if ( isset( $_POST[ WPSC_Minify::$config_varname ], $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wp-super-cache-minify' ) ) {
		WPSC_Minify::get_instance()->update_option( sanitize_text_field( wp_unslash( $_POST[ WPSC_Minify::$config_varname ] ) ) );
	}

	// Print HTML Minify configuration form.
	WPSC_Minify::get_instance()->print_admin_settings_form();
}

add_cacheaction( 'cache_admin_page', 'wpsc_minify_settings' );

/**
 * Adds a filter to the WP Super Cache buffer to minify the buffer contents.
 */
function wpsc_minify_buffer() {
	add_filter( 'wpsupercache_buffer', array( 'WPSC_Minify', 'minify_page' ) );
}

add_cacheaction( 'add_cacheaction', 'wpsc_minify_buffer' );


/**
 * Checks if a logged in user is detected and skips minification of dynamic page contents.
 */
function wpsc_minify_check_logged_in_user() {
	if ( wpsc_is_caching_user_disabled() ) {
		WPSC_Minify::skip_logged_in_user();
	}
}

add_cacheaction( 'add_cacheaction', 'wpsc_minify_check_logged_in_user' );
