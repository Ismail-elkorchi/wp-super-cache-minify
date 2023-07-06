<?php
/**
 * Plugin Name: WP Super Cache Minify Add-on
 * Plugin URI: https://github.com/Ismail-elkorchi/wp-super-cache-minify
 * Description: This plugin serves as an add-on for WP Super Cache that provides an additional feature to minify the HTML files stored in the file system.
 * Author: Ismail El Korchi
 * Author URI: https://profiles.wordpress.org/ismailelkorchi/
 * Version: 1.0.0
 * License: GPL version 2 or later - https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * This project is based on the original work of Joel Hardi's WPSCMin plugin.
 * More information: https://lyncd.com/wpscmin/
 *
 * @package wpsc-minify
 */

/**
 * Registers the minify add-on on plugin activation.
 */
function wpsc_minify_activate() {
	wpsc_add_plugin( WP_PLUGIN_DIR . '/wp-super-cache-minify/src/wpsc-minify-addon.php' );
}
register_activation_hook( __FILE__, 'wpsc_minify_activate' );

/**
 * Deregisters the minify add-on on plugin deactivation.
 */
function wpsc_minify_deactivate() {
	wpsc_delete_plugin( WP_PLUGIN_DIR . '/wp-super-cache-minify/src/wpsc-minify-addon.php' );
}
register_deactivation_hook( __FILE__, 'wpsc_minify_deactivate' );
