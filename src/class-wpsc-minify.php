<?php
/**
 * This file defines the WPSC_Minify class, an add-on to the WP Super Cache WordPress plugin. It interfaces
 * with the Minify PHP library to provide HTML/CSS/JS minification features for cached content.
 *
 * The WPSC_Minify class uses the Singleton pattern, ensuring that only one instance is in operation at any time.
 *
 * @package wpsc-minify
 */

/**
 * WPSC_Minify is a Singleton class add-on to WP Super Cache that interfaces with HTML Minify.
 *
 * This class handles minification of static HTML and gzipped HTML files that WP Super Cache
 * saves to the filesystem. It also adds an on/off configuration panel to
 * WP Super Cache's WordPress settings page in the WordPress backend.
 *
 * WP Super Cache is a static caching plugin for WordPress
 *    For more information, see: http://ocaoimh.ie/wp-super-cache/
 *
 * Minify is an HTML/CSS/JS whitespace compression library in PHP
 *    For more information, see: https://github.com/mrclay/minify
 */
class WPSC_Minify {
	/**
	 * Flag indicating whether the WP Super Cache minify plugin is activated.
	 *
	 * @var bool
	 */
	private bool $enabled = false;

	/**
	 * Flag indicating whether the value of $enabled has been updated.
	 *
	 * @var bool
	 */
	private bool $changed = false;


	/**
	 * Stores the absolute path and filename of the wp-cache-config.php file.
	 * The path is retrieved from the global variable $wp_cache_config_file.
	 *
	 * @var string
	 */
	private string $wp_cache_config_file;

	/**
	 * Set to TRUE if caching is disabled for the current visitor based on their cookies.
	 *
	 * @var bool
	 */
	private bool $skip_logged_in_user = false;

	/**
	 * An array to hold strings that are escaped from minification.
	 *
	 * @var array
	 */
	private array $escaped_strings = array();

	/**
	 * Holds the name of the global configuration variable in wp-cache-config.php.
	 *
	 * @var string
	 */
	public static string $config_varname = 'cache_minify';

	/**
	 * Holds the singleton instance of the WPSC_Minify class.
	 *
	 * @var WPSC_Minify
	 */
	private static WPSC_Minify $instance;

	/**
	 * Singleton constructor. Initializes the plugin and sets up necessary variables.
	 **/
	private function __construct() {
		// vars from wp-cache-config.php are initialized in global scope, so just
		// get initial value of $enabled from there.
		if ( isset( $GLOBALS[ self::$config_varname ] ) && $GLOBALS[ self::$config_varname ] ) {
			$this->enabled = true;
		}

		// Set location of WP Super Cache config file wp-cache-config.php from global var.
		if ( isset( $GLOBALS['wp_cache_config_file'] ) && file_exists( $GLOBALS['wp_cache_config_file'] ) ) {
			$this->wp_cache_config_file = $GLOBALS['wp_cache_config_file'];
		}
	}

	/**
	 * Returns the singleton instance of the WPSC_Minify class.
	 *
	 * @return WPSC_Minify The singleton instance.
	 */
	public static function get_instance(): WPSC_Minify {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Method to prevent caching for logged-in users.
	 *
	 * @return void
	 */
	public static function skip_logged_in_user(): void {
		self::get_instance()->skip_logged_in_user = true;
	}

	/**
	 * Minify the provided HTML string while preserving HTML comments added by WP Super Cache.
	 *
	 * @param string $html The HTML to be minified.
	 * @return string The minified HTML.
	 */
	public static function minify_page( $html ): string {
		self::get_instance()->minify( $html );
		return $html;
	}

	/**
	 * Minifies the HTML string referenced by $html, only if $this->enabled is TRUE.
	 *
	 * @param string $html The HTML string to minify.
	 * @return void
	 */
	public function minify( &$html ): void {
		if ( ! $this->enabled || $this->skip_logged_in_user ) {
			return;
		}

		require WP_CONTENT_DIR . '/plugins/wp-super-cache-minify/vendor/autoload.php';

		// Protect from minify any fragments escaped by
		// <!--[minify_skip]-->   protected text  <!--[/minify_skip]-->.
		$this->escaped_strings = array();
		$html                  = preg_replace_callback(
			'#<\!--\s*\[minify_skip\]\s*-->((?:[^<]|<(?!<\!--\s*\[/minify_skip\]))+?)<\!--\s*\[/minify_skip\]\s*-->#i',
			array( $this, 'str_capture' ),
			$html
		);

		$html = Minify_HTML::minify(
			$html,
			array(
				'cssMinifier' => array( 'Minify_CSS', 'minify' ),
				'jsMinifier'  => array(
					'Minify\JS\JShrink',
					'minify',
				),
			)
		);

		// Restore any escaped fragments.
		$html = str_replace(
			array_keys( $this->escaped_strings ),
			$this->escaped_strings,
			$html
		);
	}

	/**
	 * Updates the $enabled variable and writes it to the configuration file if it's changed.
	 *
	 * @param string $value The new value to be set.
	 * @return void
	 */
	public function update_option( $value ): void {
		$enabled = (bool) $value;
		if ( $enabled !== $this->enabled ) {
			$this->enabled = $enabled;
			$this->changed = true;
			wp_cache_replace_line( '^ *\$' . self::$config_varname, '$' . self::$config_varname . ' = ' . intval( $enabled ) . ';', $this->wp_cache_config_file );
		}
	}

	/**
	 * Prints the HTML Minify option form in the WP Super Cache settings page.
	 *
	 * @return void
	 */
	public function print_admin_settings_form(): void {
		$id = 'htmlminify-section';
		?>
			<fieldset id="<?php echo esc_attr( $id ); ?>" class="options"> 
				<h4><?php esc_html_e( 'HTML Minify', 'wpsc_minify' ); ?></h4>
				<form name="wp_manager" action="" method="post">
					<label>
						<input type="radio" name="<?php echo esc_attr( self::$config_varname ); ?>" value="1" <?php echo ( $this->enabled ? 'checked="checked" ' : '' ); ?> /> <?php esc_html_e( 'Enabled', 'wpsc_minify' ); ?>
					</label>
					<label>
						<input type="radio" name="<?php echo esc_attr( self::$config_varname ); ?>" value="0" <?php echo ( ! $this->enabled ? 'checked="checked" ' : '' ); ?> /> <?php esc_html_e( 'Disabled', 'wpsc_minify' ); ?>
					</label>
					<p>Enables or disables <a target="_blank" href="https://github.com/mrclay/minify">Minify</a> (stripping of unnecessary comments and whitespace) of cached HTML output. Disable this if you encounter any problems or need to read your source code.</p>
					<?php
					if ( $this->changed ) {
						echo '<p><strong>HTML Minify is now ' . ( $this->enabled ? 'enabled' : 'disabled' ) . '.</strong></p>';
					}
					echo '<div class="submit"><input ' . SUBMITDISABLED . 'class="button-primary" type="submit" value="Update" /></div>'; // phpcs:ignore
					wp_nonce_field( 'wp-super-cache-minify' );
					?>
				</form>
			</fieldset>
		<?php
	}

	/**
	 * Helper function to capture strings that need to be escaped from minification.
	 *
	 * @param array $matches Matches from the regex pattern for finding strings to be escaped.
	 * @return string The placeholder for the escaped string.
	 */
	private function str_capture( $matches ): string {
		$placeholder                           = 'X_wpscmin_escaped_string_' . count( $this->escaped_strings );
		$this->escaped_strings[ $placeholder ] = $matches[1];
		return $placeholder;
	}
}
