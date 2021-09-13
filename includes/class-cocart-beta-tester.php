<?php
/**
 * Beta Tester plugin main class
 *
 * @package CoCart_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * CoCart_Beta_Tester Main Class.
 */
class CoCart_Beta_Tester {

	/**
	 * Config.
	 *
	 * @var array
	 */
	private $plugin_config;

	/**
	 * Plugin instance.
	 *
	 * @var CoCart_Beta_Tester
	 */
	protected static $_instance = null;

	/**
	 * Main Instance.
	 *
	 * @access public
	 * @static
	 */
	public static function instance() {
		self::$_instance = is_null( self::$_instance ) ? new self() : self::$_instance;

		return self::$_instance;
	}

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->plugin_name   = plugin_basename( COCART_TESTER_FILE );
		$this->plugin_config = array(
			'plugin_file'        => 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php',
			'slug'               => 'cart-rest-api-for-woocommerce',
			'proper_folder_name' => 'cart-rest-api-for-woocommerce',
			'api_url'            => 'https://api.github.com/repos/co-cart/co-cart/releases?per_page=100',
			'repo_url'           => 'co-cart/co-cart',
		);

		register_activation_hook( COCART_TESTER_FILE, array( $this, 'activate' ) );

		add_action( 'init', array( $this, 'load_text_domain' ), 0 );
		add_filter( "plugin_action_links_{$this->plugin_name}", array( $this, 'plugin_action_links' ), 10, 1 );
		add_filter( 'auto_update_plugin', array( $this, 'auto_update_cocart' ), 100, 2 );

		if ( 'stable' !== $this->get_settings()->channel ) {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
			add_filter( 'plugins_api_result', array( $this, 'plugins_api_result' ), 10, 3 );
			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 3 );
		}

		$this->includes();
	} // END__construct()

	/**
	 * Load the plugin text domain once the plugin has initialized.
	 *
	 * @access public
	 * @return void
	 */
	public function load_text_domain() {
		load_plugin_textdomain( 'cocart-beta-tester', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // END load_text_domain()

	/**
	 * Ran on activation to flush update cache.
	 *
	 * @access public
	 * @static
	 */
	public static function activate() {
		delete_site_transient( 'update_plugins' );
		delete_site_transient( $this->plugin_config['slug'] . '_latest_tag' );
	} // END activate()

	/**
	 * Get plugin settings.
	 *
	 * @access public
	 * @static
	 * @return object
	 */
	public static function get_settings() {
		$settings = (object) wp_parse_args(
			get_option( 'cocart_beta_tester_options', array() ),
			array(
				'channel'     => 'beta',
				'auto_update' => false,
			)
		);

		$settings->channel     = $settings->channel;
		$settings->auto_update = (bool) $settings->auto_update;

		return $settings;
	} // END get_settings()

	/**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', COCART_TESTER_FILE ) );
	} // END plugin_url()

	/**
	 * Include any classes we need within admin.
	 *
	 * @access public
	 */
	public function includes() {
		include_once dirname( __FILE__ ) . '/class-cocart-beta-tester-admin-menus.php';
		include_once dirname( __FILE__ ) . '/class-cocart-beta-tester-channel.php';
		include_once dirname( __FILE__ ) . '/class-cocart-beta-tester-version-picker.php';
	} // END includes()

	/**
	 * Check whether or not the transients need to be overruled and 
	 * API needs to be called for every single page load.
	 *
	 * @access public
	 * @return bool overrule or not.
	 */
	public function overrule_transients() {
		return defined( 'COCART_BETA_TESTER_FORCE_UPDATE' ) && COCART_BETA_TESTER_FORCE_UPDATE;
	} // END overrule_transients()

	/**
	 * Checks if a given version is a pre-release.
	 *
	 * @access public
	 * @param  string $version Version to compare.
	 * @return bool
	 */
	public function is_prerelease( $version ) {
		return preg_match( '/(.*)?-(beta|nightly|rc)(.*)/', $version );
	} // END is_prerelease()

	/**
	 * Get New Version from GitHub.
	 *
	 * @access public
	 * @return int $version the version number.
	 */
	public function get_latest_channel_release() {
		$tagged_version = get_site_transient( md5( $this->plugin_config['slug'] ) . '_latest_tag' );

		if ( $this->overrule_transients() || empty( $tagged_version ) ) {

			$versions = array_reverse( $this->get_data() );
			$channel  = $this->get_settings()->channel;

			foreach ( $versions as $version ) {
				switch ( $channel ) {
					case 'stable':
						if ( $this->is_in_stable_channel( $version->tag_name ) ) {
							$tagged_version = $version->tag_name;
						}
						break;
					case 'rc':
						if ( $this->is_in_rc_channel( $version->tag_name ) ) {
							$tagged_version = $version->tag_name;
						}
						break;
					case 'beta':
						if ( $this->is_in_beta_channel( $version->tag_name ) ) {
							$tagged_version = $version->tag_name;
						}
						break;
					case 'nightly':
						if ( $this->is_in_nightly_channel( $version->tag_name ) ) {
							$tagged_version = $version->tag_name;
						}
						break;
				}
			}

			// Refresh every 6 hours.
			if ( ! empty( $tagged_version ) ) {
				set_site_transient( md5( $this->plugin_config['slug'] ) . '_latest_tag', $tagged_version, HOUR_IN_SECONDS * 6 );
			}
		}

		return $tagged_version;
	} // END get_latest_channel_release()

	/**
	 * Get Data from GitHub API.
	 *
	 * @access public
	 * @return array $plugin_data The data.
	 */
	public function get_data() {
		if ( ! empty( $this->plugin_data ) ) {
			return $this->plugin_data;
		}

		$plugin_data = get_site_transient( md5( $this->plugin_config['slug'] ) . '_plugin_data' );

		if ( $this->overrule_transients() || ( ! isset( $plugin_data ) || ! $plugin_data || '' === $plugin_data ) ) {
			$plugin_data = wp_remote_get( $this->plugin_config['api_url'] );

			if ( is_wp_error( $plugin_data ) ) {
				return false;
			}

			$plugin_data = json_decode( $plugin_data['body'] );

			// Refresh every 6 hours.
			set_site_transient( md5( $this->plugin_config['slug'] ) . '_plugin_data', $plugin_data, HOUR_IN_SECONDS * 6 );
		}

		// Store the data in this class instance for future calls.
		$this->plugin_data = $plugin_data;

		return $plugin_data;
	} // END get_data()

	/**
	 * Get plugin download URL.
	 *
	 * @access public
	 * @param  string $version The version.
	 * @return string
	 */
	public function get_download_url( $version ) {
		$releases = $this->get_data();

		$download_url = '';

		foreach ( $releases as $release ) {
			if ( $version === $release->tag_name ) {
				$download_url = 'https://github.com/' . $this->plugin_config['repo_url'] . '/releases/download/' . $version . '/' . $this->plugin_config['slug']  . '-' . $version . '.zip';
			}
		}

		return $download_url;
	} // END get_download_url()

	/**
	 * Get Plugin data.
	 *
	 * @access public
	 * @return object $data The data.
	 */
	public function get_plugin_data() {
		return get_plugin_data( WP_PLUGIN_DIR . '/' . $this->plugin_config['plugin_file'] );
	} // END get_plugin_data()

	/**
	 * Hook into the plugin update check and connect to GitHub.
	 *
	 * @access public
	 * @param  object $transient The plugin data transient.
	 * @return object $transient Updated plugin data transient.
	 */
	public function api_check( $transient ) {
		// Clear our transient.
		delete_site_transient( md5( $this->plugin_config['slug'] ) . '_latest_tag' );

		// Get version data.
		$plugin_data = $this->get_plugin_data();
		$version     = $plugin_data['Version'];
		$new_version = $this->get_latest_channel_release();

		// Check the version and decide if it's new.
		$update = version_compare( $new_version, $version, '>' );

		if ( ! $update ) {
			return $transient;
		}

		// Populate response data.
		if ( ! isset( $transient->response[ $this->plugin_config['plugin_file'] ] ) ) {
			$transient->response[ $this->plugin_config['plugin_file'] ] = (object) $this->plugin_config;
		}

		$transient->response[ $this->plugin_config['plugin_file'] ]->new_version = $new_version;
		$transient->response[ $this->plugin_config['plugin_file'] ]->zip_url     = $this->get_download_url( $new_version );
		$transient->response[ $this->plugin_config['plugin_file'] ]->package     = $this->get_download_url( $new_version );

		unset( $transient->no_update[ $this->plugin_config['plugin_file'] ] );

		return $transient;
	} // END api_check()

	/**
	 * Filters the Plugin Installation API response results.
	 *
	 * @access public
	 * @param  object|WP_Error $response Response object or WP_Error.
	 * @param  string          $action The type of information being requested from the Plugin Installation API.
	 * @param  object          $args Plugin API arguments.
	 * @return object
	 */
	public function plugins_api_result( $response, $action, $args ) {
		// Check if this call API is for the right plugin.
		if ( ! isset( $response->slug ) || $response->slug !== $this->plugin_config['slug'] ) {
			return $response;
		}

		$new_version = $this->get_latest_channel_release();

		if ( version_compare( $response->version, $new_version, '=' ) ) {
			return $response;
		}

		if ( $this->is_nightly_version( $new_version ) ) {
			$warning = __( '<h1><span>&#9888;</span>This is a nightly build<span>&#9888;</span></h1>', 'cocart-beta-tester' );
		}

		if ( $this->is_beta_version( $new_version ) ) {
			$warning = __( '<h1><span>&#9888;</span>This is a beta release<span>&#9888;</span></h1>', 'cocart-beta-tester' );
		}

		if ( $this->is_rc_version( $new_version ) ) {
			$warning = __( '<h1><span>&#9888;</span>This is a pre-release version<span>&#9888;</span></h1>', 'cocart-beta-tester' );
		}

		$build = $new_version;

		if ( 'nightly' === $new_version ) {
			$build = sprintf( esc_html__( '%s Build', 'cocart-beta-tester' ), ucfirst( $new_version ) );
		} else {
			$build = sprintf( esc_html__( 'v%s', 'cocart-beta-tester' ), $new_version );
		}

		$response->name          = 'CoCart Lite (' . $build . ')';
		$response->plugin_name   = 'CoCart Lite (' . $build . ')';

		// If we are returning a different version than the stable tag on .org, manipulate the returned data.
		$response->version       = $build;
		$response->download_link = $this->get_download_url( $new_version );

		$response->sections['changelog'] = sprintf(
			'<p><a target="_blank" href="%s">' . __( 'Read the changelog and find out more about the release on GitHub.', 'cocart-beta-tester' ) . '</a></p>',
			'https://github.com/co-cart/co-cart/blob/' . $response->version . '/CHANGELOG.md'
		);

		foreach ( $response->sections as $key => $section ) {
			$response->sections[ $key ] = $warning . $section;
		}

		return $response;
	} // END plugins_api_result()

	/**
	 * Rename the downloaded zip.
	 *
	 * @access public
	 * @param  string      $source        File source location.
	 * @param  string      $remote_source Remote file source location.
	 * @param  WP_Upgrader $upgrader      WordPress Upgrader instance.
	 * @global object      $wp_filesystem WordPress file system.
	 * @return string
	 */
	public function upgrader_source_selection( $source, $remote_source, $upgrader ) {
		global $wp_filesystem;

		if ( strstr( $source, '/co-cart-' ) ) {
			$corrected_source = trailingslashit( $remote_source ) . trailingslashit( $this->plugin_config['proper_folder_name'] );

			if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
				return $corrected_source;
			} else {
				return new WP_Error();
			}
		}

		return $source;
	} // END upgrader_source_selection()

	/**
	 * Enable auto updates for CoCart.
	 *
	 * @access public
	 * @param  bool   $update Should this autoupdate.
	 * @param  object $plugin Plugin being checked.
	 * @return bool
	 */
	public function auto_update_cocart( $update, $plugin ) {
		if ( true === $this->get_settings()->auto_update && $this->plugin_config['slug'] === $plugin->slug ) {
			return true;
		} else {
			return $update;
		}
	} // END auto_update_cocart()

	/**
	 * Return true if version string is a nightly version.
	 *
	 * @access protected
	 * @static
	 * @param  string $version_str Version string.
	 * @return bool
	 */
	protected static function is_nightly_version( $version_str ) {
		return strpos( $version_str, 'nightly' ) !== false;
	} // END is_nightly_version()

	/**
	 * Return true if version string is a beta version.
	 *
	 * @access protected
	 * @static
	 * @param  string $version_str Version string.
	 * @return bool
	 */
	protected static function is_beta_version( $version_str ) {
		return strpos( $version_str, 'beta' ) !== false;
	} // END is_beta_version()

	/**
	 * Return true if version string is a Release Candidate.
	 *
	 * @access protected
	 * @static
	 * @param  string $version_str Version string.
	 * @return bool
	 */
	protected static function is_rc_version( $version_str ) {
		return strpos( $version_str, 'rc' ) !== false;
	} // END is_rc_version()

	/**
	 * Return true if version string is a stable version.
	 *
	 * @access protected
	 * @static
	 * @param  string $version_str Version string.
	 * @return bool
	 */
	protected static function is_stable_version( $version_str ) {
		return ! self::is_beta_version( $version_str ) && ! self::is_nightly_version( $version_str ) && ! self::is_rc_version( $version_str );
	} // END is_stable_version()

	/**
	 * Return true if release's version string belongs to beta channel, i.e.
	 * if it's beta, rc or stable release.
	 *
	 * @access protected
	 * @static
	 * @param  string $version_str Version string of the release.
	 * @return bool
	 */
	protected static function is_in_beta_channel( $version_str ) {
		return self::is_beta_version( $version_str ) || self::is_rc_version( $version_str ) || self::is_stable_version( $version_str );
	} // END is_in_beta_channel()

	/**
	 * Return true if release's version string belongs to nightly channel.
	 *
	 * @access protected
	 * @static
	 * @param  string $version_str Version string of the release.
	 * @return bool
	 */
	protected static function is_in_nightly_channel( $version_str ) {
		return self::is_nightly_version( $version_str );
	} // END is_in_nightly_channel()

	/**
	 * Return true if release's version string belongs to release candidate channel, i.e.
	 * if it's rc or stable release.
	 *
	 * @access protected
	 * @static
	 * @param  string $version_str Version string of the release.
	 * @return bool
	 */
	protected static function is_in_rc_channel( $version_str ) {
		return self::is_rc_version( $version_str ) || self::is_stable_version( $version_str );
	} // END is_in_rc_channel()

	/**
	 * Return true if release's version string belongs to stable channel, i.e.
	 * if it's stable release and not a beta or rc.
	 *
	 * @access protected
	 * @static
	 * @param  string $version_str Version string of the release.
	 * @return bool
	 */
	protected static function is_in_stable_channel( $version_str ) {
		return self::is_stable_version( $version_str );
	} // END is_in_stable_channel()

	/**
	 * Return available versions from GitHub tags belonging to selected channel.
	 *
	 * @access public
	 * @param  string $channel Filter versions by channel: all|beta|nightly|rc|stable.
	 * @return array(string)
	 */
	public function get_tags( $channel = 'all' ) {
		$releases = $this->get_data();

		$tags = array();

		foreach ( $releases as $tag ) {
			switch( $channel ) {
				case 'stable':
					if ( $this->is_in_stable_channel( $tag->tag_name ) ) {
						$tags[] = $tag;
					}
					break;
				case 'rc':
					if ( $this->is_in_rc_channel( $tag->tag_name ) ) {
						$tags[] = $tag;
					}
					break;
				case 'beta':
					if ( $this->is_in_beta_channel( $tag->tag_name ) ) {
						$tags[] = $tag;
					}
					break;
				case 'nightly':
					if ( $this->is_in_nightly_channel( $tag->tag_name ) ) {
						$tags[] = $tag;
					}
					break;
			}
		}

		return $tags;
	} // END get_tags()

	/**
	 * Show action links on the plugin screen.
	 *
	 * @access public
	 * @param  mixed $links Plugin Action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$action_links = array(
			'switch-version' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'plugins.php?page=cocart-beta-tester-version-picker' ) ),
				esc_html__( 'Switch versions', 'cocart-beta-tester' )
			),
			'settings'       => sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'plugins.php?page=cocart-beta-tester' ) ),
				esc_html__( 'Settings', 'cocart-beta-tester' )
			),
		);

		return array_merge( $action_links, $links );
	} // END plugin_action_links()

} // END class
