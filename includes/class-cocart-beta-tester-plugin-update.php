<?php
/**
 * CoCart Beta Tester Plugin Update.
 *
 * Calls the requested CoCart plugin to check for any beta updates available.
 *
 * @package CoCart_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * CoCart_Beta_Tester_Plugin_Update Class.
 */
class CoCart_Beta_Tester_Plugin_Update {

	/**
	 * Plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Plugin SLug
	 *
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * API URL
	 *
	 * @var string
	 */
	public $api_url;

	/**
	 * Plugin Data
	 *
	 * @var string
	 */
	public $plugin_data;

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct( $plugin_file, $plugin_slug, $api_url = '' ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_slug = $plugin_slug;

		// If API URL not specified then set default.
		if ( empty( $api_url ) ) {
			$this->api_url = 'https://api.github.com/repos/co-cart/co-cart/releases?per_page=100';
		}

		// Auto update plugin if enabled.
		add_filter( 'auto_update_plugin', array( $this, 'auto_update_cocart' ), 100, 2 );

		// Only check for beta updates if channel is not stable.
		if ( 'stable' !== CoCart_Beta_Tester::get_settings()->channel ) {
			add_action( 'install_plugins_pre_plugin-information', array( $this, 'install_plugin_information_style' ), 0 );
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
			add_filter( 'plugins_api_result', array( $this, 'plugins_api_result' ), 10, 3 );
			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 3 );
		}
	}

	/**
	 * Enable auto updates for CoCart.
	 *
	 * @access public
	 *
	 * @param bool   $update Should this autoupdate.
	 * @param object $plugin Plugin being checked.
	 *
	 * @return bool
	 */
	public function auto_update_cocart( $update, $plugin ) {
		if ( true === CoCart_Beta_Tester::get_settings()->auto_update && $this->plugin_slug === $plugin->slug ) {
			return true;
		} else {
			return $update;
		}
	} // END auto_update_cocart()

	/**
	 * Applies some style tweaks for the information to display correctly.
	 *
	 * @access public
	 *
	 * @since 2.2.0
	 */
	public function install_plugin_information_style() {
		?>
		<style type="text/css">
			.plugin-install-php h2 {
				clear: inherit !important;
			}
		</style>
		<?php
	} // END install_plugin_information_style()

	/**
	 * Hook into the plugin update check and connect to GitHub.
	 *
	 * @access public
	 *
	 * @param object $transient The plugin data transient.
	 *
	 * @return object $transient Updated plugin data transient.
	 */
	public function api_check( $transient ) {
		// Clear our transient.
		delete_site_transient( md5( $this->plugin_slug ) . '_latest_tag' );

		// Get version data.
		$plugin_data = $this->get_plugin_data();

		// If unable to get new plugin data then just return the current plugin data.
		if ( is_wp_error( $plugin_data ) ) {
			return $transient;
		}

		$filename    = $this->plugin_file;
		$plugin_slug = $this->plugin_slug;

		// If plugin is not CoCart core.
		if ( $plugin_slug != 'cart-rest-api-for-woocommerce' ) {
			$plugin_slug = 'cocart-api-' . $this->plugin_slug;
		}

		// Get versions.
		$version     = $plugin_data['Version'];
		$new_version = $this->get_latest_channel_release();

		// Prepare results.
		$results = (object) array(
			'id'            => 'cocart-api-' . $this->plugin_slug,
			'slug'          => $plugin_slug,
			'plugin'        => $filename,
			'new_version'   => $version,
			'url'           => 'https://cocartapi.com',
			'package'       => '',
			'icons'         => array(
				'2x' => esc_url( 'https://raw.githubusercontent.com/co-cart/co-cart/trunk/.wordpress-org/icon-256x256.png' ),
				'1x' => esc_url( 'https://raw.githubusercontent.com/co-cart/co-cart/trunk/.wordpress-org/icon-128x128.png' ),
			),
			'banners'       => array(
				'low'  => esc_url( 'https://raw.githubusercontent.com/co-cart/co-cart/trunk/.wordpress-org/banner-772x250.jpg' ),
				'high' => esc_url( 'https://raw.githubusercontent.com/co-cart/co-cart/trunk/.wordpress-org/banner-1544x500.jpg' ),
			),
			'banners_rtl'   => array(),
			'compatibility' => new stdClass(),
		);

		// Populate data if update is available.
		if ( version_compare( ltrim( $new_version, 'v' ), $version, '>' ) || 'nightly' === $new_version ) {
			$results->new_version = ltrim( $new_version, 'v' );
			$results->zip_url     = $this->get_download_url( $new_version );
			$results->package     = $results->zip_url;

			$transient->response[ $filename ] = $results;
			unset( $transient->no_update[ $filename ] );
		} else {
			$transient->no_update[ $filename ] = $results;
			unset( $transient->response[ $filename ] );
		}

		return $transient;
	} // END api_check()

	/**
	 * Filters the Plugin Installation API response results.
	 *
	 * @access public
	 *
	 * @param object|WP_Error $response Response object or WP_Error.
	 * @param string          $action The type of information being requested from the Plugin Installation API.
	 * @param object          $args Plugin API arguments.
	 *
	 * @return object
	 */
	public function plugins_api_result( $response, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return false;
		}

		// Check if this call API is for the right plugin.
		if ( ! isset( $response->slug ) || $response->slug !== $this->plugin_slug ) {
			return $response;
		}

		$new_version = $this->get_latest_channel_release();

		if ( version_compare( $response->version, $new_version, '=' ) ) {
			return $response;
		}

		$plugins_allowedtags = array(
			'a'          => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
			),
			'abbr'       => array( 'title' => array() ),
			'acronym'    => array( 'title' => array() ),
			'code'       => array(),
			'pre'        => array(),
			'em'         => array(),
			'strong'     => array(),
			'div'        => array( 'class' => array() ),
			'span'       => array( 'class' => array() ),
			'p'          => array(),
			'br'         => array(),
			'ul'         => array(),
			'ol'         => array(),
			'li'         => array(),
			'h1'         => array(),
			'h2'         => array(),
			'h3'         => array(),
			'h4'         => array(),
			'h5'         => array(),
			'h6'         => array(),
			'img'        => array(
				'src'   => array(),
				'class' => array(),
				'alt'   => array(),
			),
			'blockquote' => array( 'cite' => true ),
		);

		$warning = '';

		// If we are returning a different version than the stable tag on .org, manipulate the returned data.
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
			$build = esc_html__( 'Nightly Build', 'cocart-beta-tester' );
		}

		$plugin_data = $this->get_plugin_data();

		$response->name          = $plugin_data['Name'] . ' (' . $build . ')';
		$response->plugin_name   = $plugin_data['Name'] . ' (' . $build . ')';
		$response->slug          = $this->plugin_slug;
		$response->version       = ltrim( $build, 'v' );
		$response->last_updated  = $this->get_release_date( $new_version );
		$response->download_link = $this->get_download_url( $new_version );

		$response->sections['changelog'] = $this->get_release_changelog( $new_version );

		foreach ( $response->sections as $key => $section ) {
			$response->sections[ $key ] = wp_kses( $warning . $section, $plugins_allowedtags );
		}

		// Override plugin banner.
		$response->banners = array(
			'low'  => 'https://raw.githubusercontent.com/co-cart/co-cart/trunk/.wordpress-org/banner-772x250.jpg',
			'high' => 'https://raw.githubusercontent.com/co-cart/co-cart/trunk/.wordpress-org/banner-1544x500.jpg',
		);

		return $response;
	} // END plugins_api_result()

	/**
	 * Rename the downloaded zip.
	 *
	 * @access public
	 *
	 * @param string      $source        File source location.
	 * @param string      $remote_source Remote file source location.
	 * @param WP_Upgrader $upgrader      WordPress Upgrader instance.
	 *
	 * @global object $wp_filesystem WordPress file system.
	 *
	 * @return string
	 */
	public function upgrader_source_selection( $source, $remote_source, $upgrader ) {
		global $wp_filesystem;

		if ( strstr( $source, '/co-cart-' ) ) {
			$corrected_source = trailingslashit( $remote_source ) . trailingslashit( $this->plugin_slug );

			if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
				return $corrected_source;
			} else {
				return new WP_Error();
			}
		}

		return $source;
	} // END upgrader_source_selection()

	/**
	 * Check whether or not the transients need to be overruled and
	 * API needs to be called for every single page load.
	 *
	 * @access public
	 *
	 * @return bool overrule or not.
	 */
	public function overrule_transients() {
		return defined( 'COCART_BETA_TESTER_FORCE_UPDATE' ) && COCART_BETA_TESTER_FORCE_UPDATE;
	} // END overrule_transients()

	/**
	 * Checks if a given version is a pre-release.
	 *
	 * @access public
	 *
	 * @param string $version Version to compare.
	 *
	 * @return bool
	 */
	public function is_prerelease( $version ) {
		return preg_match( '/(.*)?-(beta|nightly|rc)(.*)/', $version );
	} // END is_prerelease()

	/**
	 * Get latest channel release.
	 *
	 * @access public
	 *
	 * @return int $version the version number.
	 */
	public function get_latest_channel_release() {
		$tagged_version = get_site_transient( md5( $this->plugin_slug ) . '_latest_tag' );

		if ( $this->overrule_transients() || empty( $tagged_version ) ) {
			$versions = $this->get_data();
			$versions = $this->sort_release_order( $versions, true );
			$channel  = CoCart_Beta_Tester::get_settings()->channel;

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
				set_site_transient( md5( $this->plugin_slug ) . '_latest_tag', $tagged_version, HOUR_IN_SECONDS * 6 );
			}
		}

		return $tagged_version;
	} // END get_latest_channel_release()

	/**
	 * Sort releases in order of tag name.
	 *
	 * @access public
	 *
	 * @param array $versions      The releases unordered.
	 * @param bool  $reverse_order Returns the releases in reverse order is true.
	 *
	 * @return array $new_order The releases ordered by tag name.
	 */
	public function sort_release_order( $versions, $reverse_order = false ) {
		$new_order = array();

		foreach ( $versions as $key => $version ) {
			$new_order[ $version->tag_name ] = $version;
		}

		usort( $new_order, function ( $a, $b ) {
			return -1 * version_compare( $a->tag_name, $b->tag_name );
		});

		if ( $reverse_order ) {
			$new_order = array_reverse( $new_order );
		}

		return $new_order;
	} // END sort_release_order()

	/**
	 * Get Data from API URL.
	 *
	 * @access public
	 *
	 * @return array $plugin_data The data.
	 */
	public function get_data() {
		if ( ! empty( $this->plugin_data ) ) {
			return $this->plugin_data;
		}

		$plugin_data = get_site_transient( md5( $this->plugin_slug ) . '_plugin_data' );

		if ( $this->overrule_transients() || ( ! isset( $plugin_data ) || ! $plugin_data || '' === $plugin_data ) ) {
			$plugin_data = wp_remote_get( $this->api_url );

			if ( is_wp_error( $plugin_data ) ) {
				return false;
			}

			$plugin_data = json_decode( $plugin_data['body'] );

			// Refresh every 6 hours.
			set_site_transient( md5( $this->plugin_slug ) . '_plugin_data', $plugin_data, HOUR_IN_SECONDS * 6 );
		}

		// Store the data in this class instance for future calls.
		$this->plugin_data = $plugin_data;

		return $plugin_data;
	} // END get_data()

	/**
	 * Get plugin download URL.
	 *
	 * @access public
	 *
	 * @param string $version The version.
	 *
	 * @return string
	 */
	public function get_download_url( $version ) {
		$releases = $this->get_data();

		$download_url = '';

		foreach ( $releases as $release ) {
			if ( $version === $release->tag_name ) {
				$download_url = 'https://github.com/co-cart/co-cart/releases/download/' . $version . '/' . $this->plugin_slug . '-' . $version . '.zip';
				break;
			}
		}

		return $download_url;
	} // END get_download_url()

	/**
	 * Get release date.
	 *
	 * @access public
	 *
	 * @param string $version The version.
	 *
	 * @return string
	 */
	public function get_release_date( $version ) {
		$releases = $this->get_data();

		$release_date = '';

		foreach ( $releases as $release ) {
			if ( $version === $release->tag_name ) {
				$release_date = $release->created_at;
				break;
			}
		}

		return ! empty( $release_date ) ? date( 'Y-m-d h:i A e', strtotime( $release_date ) ) : false;
	} // END get_release_date()

	/**
	 * Get release changelog.
	 *
	 * @access public
	 *
	 * @param string $version The version.
	 *
	 * @return string
	 */
	public function get_release_changelog( $version ) {
		if ( ! class_exists( 'Parsedown' ) ) {
			include_once dirname( COCART_BETA_TESTER_FILE ) . '/parsedown.php';
		}
		$Parsedown = new Parsedown();

		if ( 'nightly' === $version ) {
			$response = wp_remote_get( 'https://raw.githubusercontent.com/co-cart/co-cart/dev/NEXT_CHANGELOG.md' );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			return $Parsedown->text( $response['body'] );
		}

		$releases = $this->get_data();

		$changelog = '';

		foreach ( $releases as $release ) {
			if ( $version === $release->tag_name ) {
				$changelog = $Parsedown->text( $release->body );
				break;
			}
		}

		return $changelog;
	} // END get_release_changelog()

	/**
	 * Get Plugin data.
	 *
	 * @access public
	 *
	 * @return object $data The data.
	 */
	public function get_plugin_data() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugin_data( WP_PLUGIN_DIR . '/' . $this->plugin_file );
	} // END get_plugin_data()

	/**
	 * Return true if version string is a nightly version.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $version_str Version string.
	 *
	 * @return bool
	 */
	public static function is_nightly_version( $version_str ) {
		return strpos( $version_str, 'nightly' ) !== false || strpos( $version_str, 'nb' ) !== false || strpos( $version_str, 'night-build' ) !== false;
	} // END is_nightly_version()

	/**
	 * Return true if version string is a beta version.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $version_str Version string.
	 *
	 * @return bool
	 */
	public static function is_beta_version( $version_str ) {
		return strpos( $version_str, 'beta' ) !== false;
	} // END is_beta_version()

	/**
	 * Return true if version string is a Release Candidate.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $version_str Version string.
	 *
	 * @return bool
	 */
	public static function is_rc_version( $version_str ) {
		return strpos( $version_str, 'rc' ) !== false || strpos( $version_str, 'RC' ) !== false;
	} // END is_rc_version()

	/**
	 * Return true if version string is a stable version.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $version_str Version string.
	 *
	 * @return bool
	 */
	public static function is_stable_version( $version_str ) {
		return ! self::is_beta_version( $version_str ) && ! self::is_nightly_version( $version_str ) && ! self::is_rc_version( $version_str );
	} // END is_stable_version()

	/**
	 * Return true if release's version string belongs to beta channel, i.e.
	 * if it's beta, rc or stable release.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $version_str Version string of the release.
	 *
	 * @return bool
	 */
	public static function is_in_beta_channel( $version_str ) {
		return self::is_beta_version( $version_str ) || self::is_rc_version( $version_str ) || self::is_stable_version( $version_str );
	} // END is_in_beta_channel()

	/**
	 * Return true if release's version string belongs to nightly channel.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $version_str Version string of the release.
	 *
	 * @return bool
	 */
	public static function is_in_nightly_channel( $version_str ) {
		return self::is_nightly_version( $version_str );
	} // END is_in_nightly_channel()

	/**
	 * Return true if release's version string belongs to release candidate channel, i.e.
	 * if it's rc or stable release.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $version_str Version string of the release.
	 *
	 * @return bool
	 */
	public static function is_in_rc_channel( $version_str ) {
		return self::is_rc_version( $version_str ) || self::is_stable_version( $version_str );
	} // END is_in_rc_channel()

	/**
	 * Return true if release's version string belongs to stable channel, i.e.
	 * if it's stable release and not a beta or rc.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $version_str Version string of the release.
	 *
	 * @return bool
	 */
	public static function is_in_stable_channel( $version_str ) {
		return self::is_stable_version( $version_str );
	} // END is_in_stable_channel()

	/**
	 * Return available versions from tags belonging to selected channel.
	 *
	 * @access public
	 *
	 * @param string $channel Filter versions by channel: all|beta|nightly|rc|stable.
	 *
	 * @return array(string)
	 */
	public function get_tags( $channel = 'all' ) {
		$releases = $this->get_data();
		$releases = $this->sort_release_order( $releases );

		$tags = array();

		foreach ( $releases as $tag ) {
			switch ( $channel ) {
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
} // END class
