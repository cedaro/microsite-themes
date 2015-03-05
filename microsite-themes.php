<?php
/**
 * Microsite Themes
 *
 * @package   MicrositeThemes
 * @author    Brady Vercher
 * @link      http://www.cedaro.com/
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       Microsite Themes
 * Plugin URI:        https://github.com/cedaro/microsite-themes
 * Description:       Load custom themes based on the URL.
 * Version:           1.0.0
 * Author:            Cedaro
 * Author URI:        http://www.cedaro.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: cedaro/microsite-themes
 */

/**
 * Main plugin class.
 *
 * @package MicrositeThemes
 * @since 1.0.0
 */
class Cedaro_Microsite_Themes {
	/**
	 * The URI for the current request.
	 *
	 * @since 1.0.0
	 * @type string $request_uri
	 */
	protected $request_uri = '';

	/**
	 * The current microsite template slug.
	 *
	 * @since 1.0.0
	 * @type string $template
	 */
	protected $template = '';

	/**
	 * A list of microsite theme slugs.
	 *
	 * @since 1.0.0
	 * @type array $theme_slugs
	 */
	protected $theme_slugs = array();

	/**
	 * The root directory for microsite themes.
	 *
	 * @since 1.0.0
	 * @type string $themes_root_directory
	 */
	protected $themes_root_directory = '';

	/**
	 * Load the plugin.
	 *
	 * Registers the theme directory for microsite themes, determines if the
	 * current request is for a microsite, and filters the template and
	 * stylesheet values if so.
	 *
	 * @since 1.0.0
	 */
	public function load() {
		register_theme_directory( $this->get_themes_root_directory() );
		add_filter( 'wp_prepare_themes_for_js', array( $this, 'hide_microsite_themes' ) );

		if ( ! $this->is_microsite_request() ) {
			return;
		}

		$this->template = $this->get_microsite_slug();
		add_filter( 'template',   array( $this, 'filter_template' ) );
		add_filter( 'stylesheet', array( $this, 'filter_template' ) );
	}

	/**
	 * Retrieve the microsite slug from the current URI.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_microsite_slug() {
		$uri_parts = explode( '/', $this->get_request_uri() );
		return reset( $uri_parts );
	}

	/**
	 * Retrieve the root directory for microsite themes.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_themes_root_directory() {
		return $this->themes_root_directory;
	}

	/**
	 * Retrieve the slugs for current microsite themes.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_theme_slugs() {
		if ( empty( $this->theme_slugs ) ) {
			$this->theme_slugs = array_slice( scandir( $this->get_themes_root_directory() ), 2 );
		}

		return $this->theme_slugs;
	}

	/**
	 * Whether the current request is for a microsite.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_microsite_request() {
		return in_array( $this->get_microsite_slug(), $this->get_theme_slugs() );
	}

	/**
	 * Set the root directory for microsite themes.
	 *
	 * @since 1.0.0
	 */
	public function set_themes_root_directory( $directory ) {
		$this->themes_root_directory = $directory;
	}

	/**
	 * Hide microsite themes from the admin browser.
	 *
	 * @since 1.0.0
	 */
	public function hide_microsite_themes( $themes ) {
		return array_diff_key( $themes, array_flip( $this->get_theme_slugs() ) );
	}

	/**
	 * Filter the template/stylesheet value.
	 *
	 * @since 1.0.0
	 */
	public function filter_template() {
		return $this->template;
	}

	/**
	 * Retrieve the URI for the current request.
	 *
	 * @since 1.0.0
	 *
	 * @see WP::parse_request()
	 *
	 * @return string
	 */
	protected function get_request_uri() {
		global $wp_rewrite;

		if ( ! empty( $this->request_uri ) ) {
			return $this->request_uri;
		}

		$pathinfo         = isset( $_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : '';
		list( $pathinfo ) = explode( '?', $pathinfo );
		$pathinfo         = str_replace( "%", "%25", $pathinfo );

		list( $request_uri ) = explode( '?', $_SERVER['REQUEST_URI'] );
		$self                = $_SERVER['PHP_SELF'];
		$home_path           = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );

		// Trim path info from the end and the leading home path from the
		// front. For path info requests, this leaves us with the requesting
		// filename, if any. For 404 requests, this leaves us with the
		// requested permalink.
		$request_uri  = str_replace( $pathinfo, '', $request_uri );
		$request_uri  = trim( $request_uri, '/' );
		$request_uri  = preg_replace( "|^$home_path|i", '', $request_uri );
		$request_uri  = trim( $request_uri, '/' );
		$pathinfo     = trim( $pathinfo, '/' );
		$pathinfo     = preg_replace( "|^$home_path|i", '', $pathinfo );
		$pathinfo     = trim( $pathinfo, '/' );
		$self         = trim( $self, '/' );
		$self         = preg_replace( "|^$home_path|i", '', $self );
		$self         = trim( $self, '/' );

		// The requested permalink is in $pathinfo for path info requests and
		//  $request_uri for other requests.
		if ( ! empty( $pathinfo ) && ! preg_match( '|^.*' . $wp_rewrite->index . '$|', $pathinfo ) ) {
			$request_uri = $pathinfo;
		}

		// If the request uri is the index, blank it out so that we don't
		// try to match it against a rule.
		elseif ( $request_uri == $wp_rewrite->index ) {
			$request_uri = '';
		}

		return $this->request_uri = $request_uri;
	}
}

/**
 * Initialize the plugin.
 */
$microsite_themes = new Cedaro_Microsite_Themes;
add_action( 'setup_theme', array( $microsite_themes, 'load' ) );

// This is the directory where microsite themes are located.
$microsite_themes->set_themes_root_directory( WP_CONTENT_DIR . '/microsites' );
