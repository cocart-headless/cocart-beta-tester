<?php
/**
 * Plugin Name: CoCart - Beta Tester
 * Plugin URI: https://github.com/co-cart/cocart-beta-tester
 * Version: 1.0.0
 * Description: Run bleeding edge versions of CoCart from the GitHub repo. This will replace your installed version of CoCart with the latest tagged prerelease on GitHub - use with caution, and not on production sites. You have been warned.
 * Author: CoCart
 * Author URI: https://cocart.xyz
 * Developer: Sébastien Dumont
 * Developer URI: https://sebastiendumont.com
 * GitHub Plugin URI: https://github.com/co-cart/cocart-beta-tester
 *
 * Text Domain: cocart-beta-tester
 * Domain Path: /languages/
 *
 * Requires at least: 5.0
 * Tested up to: 5.5
 *
 * Copyright: © 2020 Sébastien Dumont
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! class_exists( 'CoCart_Beta_Tester' ) ) {

	class CoCart_Beta_Tester {

		/**
		 * Plugin Configuration
		 *
		 * @access private
		 */
		private $config = array();

		/**
		 * GitHub Data
		 *
		 * @access protected
		 * @static
		 */
		protected static $_instance = null;

		/**
		 * Plugin Version
		 *
		 * @access private
		 * @static
		 */
		private static $version = '1.0.0';

		/**
		 * Main Instance
		 *
		 * @access public
		 * @static
		 * @return CoCart_Beta_Tester - Main instance
		 */
		public static function instance() {
			return self::$_instance = is_null( self::$_instance ) ? new self() : self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @access public
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cloning this object is forbidden.', 'cocart-beta-tester' ), self::$version );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @access public
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'cocart-beta-tester' ), self::$version );
		}

		/**
		 * Constructor
		 *
		 * @access  public
		 * @static
		 */
		public function __construct() {
			$this->config = array(
				'plugin_file'        => 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php',
				'slug'               => 'cart-rest-api-for-woocommerce',
				'proper_folder_name' => 'cart-rest-api-for-woocommerce',
				'api_url'            => 'https://api.github.com/repos/co-cart/co-cart',
				'requires'           => '5.0',
				'tested'             => '5.5',
				'requires_php'       => '7.0'
			);

			add_action( 'plugin_loaded', array( $this, 'flush_update_cache' ) );
			add_action( 'init', array( $this, 'check_cocart_installed' ) );
			add_action( 'init', array( $this, 'load_text_domain' ), 0 );
		} // END __construct()

		/**
		 * Run these filters once CoCart is installed and active.
		 *
		 * @access public
		 * @return void
		 */
		public function cocart_active() {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
			add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 3 );

			// Auto update CoCart.
			add_filter( 'auto_update_plugin', array( $this, 'auto_update_cocart' ), 100, 2 );
		} // END cocart_active()

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
		 * Run once the plugin has loaded to flush the update cache.
		 *
		 * @access public
		 * @static
		 */
		public static function flush_update_cache() {
			delete_site_transient( 'update_plugins' ); // Clear all plugin update data
		} // END flush_update_cache()

		/**
		 * Checks if CoCart is installed before running filters for the WordPress updater.
		 *
		 * @access public
		 * @return bool|void
		 */
		public function check_cocart_installed() {
			if ( ! defined( 'COCART_VERSION' ) ) {
				add_action( 'admin_notices', array( $this, 'cocart_not_installed' ) );
				return false;
			}

			// CoCart is active.
			$this->cocart_active();
		} // END check_cocart_installed()

		/**
		 * CoCart is Not Installed Notice.
		 *
		 * @access public
		 * @global string $pagenow
		 * @return void
		 */
		public function cocart_not_installed() {
			global $pagenow;

			if ( $pagenow == 'update.php' ) {
				return false;
			}

			echo '<div class="notice notice-error">';

				echo '<p>' . sprintf( __( '%1$s requires %2$s%3$s%4$s to be installed and activated in order to serve updates from GitHub.', 'cocart-beta-tester' ), esc_html__( 'CoCart - Beta Tester', 'cocart-beta-tester' ), '<strong>', '</strong>', esc_html__( 'CoCart', 'cocart-beta-tester' ) ) . '</p>';

				echo '<p>';

				if ( ! is_plugin_active( $this->config['plugin_file'] ) && current_user_can( 'activate_plugin', $this->config['plugin_file'] ) ) :

					echo '<a href="' . esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . $this->config['plugin_file'] . '&plugin_status=active' ), 'activate-plugin_' . $this->config['plugin_file'] ) ) . '" class="button button-primary">' . sprintf( esc_html__( 'Activate %s', 'cocart-beta-tester' ), esc_html__( 'CoCart', 'cocart-beta-tester' ) ) . '</a> ';

				else :

					if ( current_user_can( 'install_plugins' ) ) {
						$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=cart-rest-api-for-woocommerce' ), 'install-plugin_cart-rest-api-for-woocommerce' );
					} else {
						$url = 'https://wordpress.org/plugins/cart-rest-api-for-woocommerce/';
					}

					echo '<a href="' . esc_url( $url ) . '" class="button button-primary">' . sprintf( esc_html__( 'Install %s', 'cocart-beta-tester' ), esc_html__( 'CoCart', 'cocart-beta-tester' ) ) . '</a> ';

				endif;

				if ( current_user_can( 'deactivate_plugin', 'cocart-beta-tester/cocart-beta-tester.php' ) ) :
					echo '<a href="' . esc_url( wp_nonce_url( 'plugins.php?action=deactivate&plugin=cocart-beta-tester/cocart-beta-tester.php&plugin_status=inactive', 'deactivate-plugin_cocart-beta-tester/cocart-beta-tester.php' ) ) . '" class="button button-secondary">' . sprintf( esc_html__( 'Turn off %s plugin', 'cocart-beta-tester' ), esc_html__( 'CoCart - Beta Tester', 'cocart-beta-tester' ) ) . '</a>';
				endif;

				echo '</p>';

			echo '</div>';
		} // END cocart_not_installed()

		/**
		 * Enable auto updates for CoCart.
		 *
		 * @access public
		 * @param  bool   $update Should this auto update.
		 * @param  object $plugin Plugin being checked.
		 * @return bool
		 */
		public function auto_update_cocart( $update, $plugin ) {
			if ( 'cart-rest-api-for-woocommerce' === $plugin->slug ) {
				return true;
			} else {
				return $update;
			}
		} // END auto_update_cocart()

		/**
		 * Update the required plugin data arguments.
		 *
		 * @access public
		 * @return array
		 */
		public function set_update_args() {
			$plugin_data                  = $this->get_plugin_data();
			$latest_prerelease            = $this->get_latest_prerelease();

			$this->config['plugin_name']  = 'CoCart Lite ' . $latest_prerelease;
			$this->config['description']  = $this->get_description();
			$this->config['author']       = $plugin_data['Author'];
			$this->config['homepage']     = $plugin_data['PluginURI'];
			$this->config['new_version']  = str_replace( 'v', '', $latest_prerelease );
			$this->config['last_updated'] = $this->get_date();
			$this->config['changelog']    = $this->get_changelog();
			$this->config['zip_name']     = $latest_prerelease;
			$this->config['zip_url']      = 'https://github.com/co-cart/co-cart/archive/' . $latest_prerelease . '.zip';
		} // END set_update_args()

		/**
		 * Check whether or not the transients need to be overruled
		 * and API needs to be called for every single page load.
		 *
		 * @access public
		 * @return bool overrule or not
		 */
		public function overrule_transients() {
			return ( defined( 'COCART_BETA_TESTER_FORCE_UPDATE' ) && COCART_BETA_TESTER_FORCE_UPDATE );
		} // END overrule_transients()

		/**
		 * Get New Version from GitHub.
		 *
		 * @access public
		 * @return int $tagged_version the version number
		 */
		public function get_latest_prerelease() {
			$tagged_version = get_site_transient( md5( $this->config['slug'] ) . '_latest_tag' );

			if ( $this->overrule_transients() || empty( $tagged_version ) ) {

				$raw_response = wp_remote_get( trailingslashit( $this->config['api_url'] ) . 'releases' );

				if ( is_wp_error( $raw_response ) ) {
					return false;
				}

				$releases       = json_decode( $raw_response['body'] );
				$tagged_version = false;

				if ( is_array( $releases ) ) {
					foreach ( $releases as $release ) {

						// If the release is a pre-release then return the tagged version.
						if ( $release->prerelease ) {
							$tagged_version = $release->tag_name;
							break;
						}
					}
				}

				// Refresh every 6 hours.
				if ( ! empty( $tagged_version ) ) {
					set_site_transient( md5( $this->config['slug'] ) . '_latest_tag', $tagged_version, 60*60*6 );
				}
			}

			return $tagged_version;
		} // END get_latest_prerelease()

		/**
		 * Get Published date of New Version from GitHub.
		 *
		 * @access public
		 * @return string $published_date of the latest prerelease
		 */
		public function get_latest_prerelease_date() {
			$published_date = get_site_transient( md5( $this->config['slug'] ) . '_latest_published_date' );

			if ( $this->overrule_transients() || empty( $published_date ) ) {

				$raw_response = wp_remote_get( trailingslashit( $this->config['api_url'] ) . 'releases' );

				if ( is_wp_error( $raw_response ) ) {
					return false;
				}

				$releases       = json_decode( $raw_response['body'] );
				$published_date = false;

				if ( is_array( $releases ) ) {
					foreach ( $releases as $release ) {

						// If the release is a pre-release then return the published date.
						if ( $release->prerelease ) {
							$published_date = $release->published_at;
							break;
						}
					}
				}

				// Refresh every 6 hours.
				if ( ! empty( $published_date ) ) {
					set_site_transient( md5( $this->config['slug'] ) . '_latest_published_date', $published_date, 60 * 60 * 6 );
				}
			}

			return $published_date;
		} // END get_latest_prerelease_date()

		/**
		 * Get Changelog of New Version from GitHub.
		 *
		 * @access public
		 * @return string $changelog of the latest prerelease
		 */
		public function get_latest_prerelease_changelog() {
			$changelog = get_site_transient( md5( $this->config['slug'] ) . '_latest_changelog' );

			if ( $this->overrule_transients() || empty( $changelog ) ) {

				$raw_response = wp_remote_get( trailingslashit( $this->config['api_url'] ) . 'releases' );

				if ( is_wp_error( $raw_response ) ) {
					return false;
				}

				$releases  = json_decode( $raw_response['body'] );
				$changelog = false;

				if ( is_array( $releases ) ) {
					foreach ( $releases as $release ) {

						// If the release is a pre-release then return the body.
						if ( $release->prerelease ) {
							if ( ! class_exists( 'Parsedown' ) ) {
								include_once( 'parsedown.php' );
							}
							$Parsedown = new Parsedown();

							$changelog = $Parsedown->text( $release->body );
							break;
						}
					}
				}

				// Refresh every 6 hours.
				if ( ! empty( $changelog ) ) {
					set_site_transient( md5( $this->config['slug'] ) . '_latest_changelog', $changelog, 60*60*6 );
				}
			}

			return $changelog;
		} // END get_latest_prerelease_changelog()

		/**
		 * Get GitHub Data from the specified repository.
		 *
		 * @access public
		 * @return array $github_data the data
		 */
		public function get_github_data() {
			if ( ! empty( $this->github_data ) ) {
				$github_data = $this->github_data;
			} else {
				$github_data = get_site_transient( md5( $this->config['slug'] ) . '_github_data' );

				if ( $this->overrule_transients() || ( ! isset( $github_data ) || ! $github_data || '' == $github_data ) ) {
					$github_data = wp_remote_get( $this->config['api_url'] );

					if ( is_wp_error( $github_data ) ) {
						return false;
					}

					$github_data = json_decode( $github_data['body'] );

					// refresh every 6 hours
					set_site_transient( md5( $this->config['slug'] ) . '_github_data', $github_data, 60*60*6 );
				}

				// Store the data in this class instance for future calls
				$this->github_data = $github_data;
			}

			return $github_data;
		} // END get_github_data()

		/**
		 * Get update date.
		 *
		 * @access public
		 * @return string $_date the date
		 */
		public function get_date() {
			$_date = $this->get_latest_prerelease_date();
			return ! empty( $_date ) ? date( 'Y-m-d', strtotime( $_date ) ) : false;
		} // END get_date()

		/**
		 * Get plugin description.
		 *
		 * @access public
		 * @return string $_description the description
		 */
		public function get_description() {
			$_description = $this->get_github_data();
			return ! empty( $_description->description ) ? $_description->description : false;
		} // END get_description()

		/**
		 * Get plugin changelog.
		 *
		 * @access public
		 * @return string $_changelog the changelog of the release
		 */
		public function get_changelog() {
			$_changelog = $this->get_latest_prerelease_changelog();
			return ! empty( $_changelog ) ? $_changelog : false;
		} // END get_changelog()

		/**
		 * Get Plugin data.
		 *
		 * @access public
		 * @return object $data the data
		 */
		public function get_plugin_data() {
			return get_plugin_data( WP_PLUGIN_DIR . '/' . $this->config['plugin_file'] );
		} // END get_plugin_data()

		/**
		 * Hook into the plugin update check and connect to GitHub.
		 *
		 * @access public
		 * @param  object $transient the plugin data transient
		 * @return object $transient updated plugin data transient
		 */
		public function api_check( $transient ) {
			/**
			 * Clear our transient if we have debug enabled and overruled the transients.
			 * This will allow the API to check fresh every time.
			 *
			 * DEV NOTE: If api checked to many times in a short amount of time, 
			 * GitHub will block you from accessing the API for 1 hour.
			 */
			if ( WP_DEBUG && $this->overrule_transients() ) {
				delete_site_transient( md5( $this->config['slug'] ) . '_latest_tag' );
				delete_site_transient( md5( $this->config['slug'] ) . '_latest_changelog' );
			}

			// Get plugin data from the currently installed version of Gutenberg.
			$plugin_data = $this->get_plugin_data();
			$version     = $plugin_data['Version'];

			// Update tags.
			$this->set_update_args();

			// Check the version and decide if it's new.
			$update = version_compare( $this->config['new_version'], $version, '>' );

			// If the version is not newer then return default.
			if ( ! $update ) {
				return $transient;
			}

			// Filename.
			$filename = $this->config['plugin_file'];

			$data = array(
				'id'             => $this->config['slug'],
				'slug'           => $this->config['slug'],
				'plugin'         => $filename,
				'new_version'    => $this->config['new_version'],
				'tested'         => $this->config['tested'],
				'requires_php'   => $this->config['requires_php'],
				'url'            => $this->config['homepage'],
				'icons'          => array(
					'2x' => esc_url( 'https://raw.githubusercontent.com/co-cart/co-cart/master/.wordpress-org/icon-256x256.jpg' ),
					'1x' => esc_url( 'https://raw.githubusercontent.com/co-cart/co-cart/master/.wordpress-org/icon-128x128.jpg' ),
				),
				'banners'        => array(
					'low'  => esc_url( 'https://raw.githubusercontent.com/co-cart/co-cart/master/.wordpress-org/banner-772x250.jpg' ),
					'high' => esc_url( 'https://raw.githubusercontent.com/co-cart/co-cart/master/.wordpress-org/banner-1544x500.jpg' )
				),
				'package'        => $this->config['zip_url'],
				'zip_url'        => $this->config['zip_url']
			);

			// Check if its a beta release or a release candidate.
			$is_beta_rc = ( $this->is_beta_version( $this->config['new_version'] ) || $this->is_rc_version( $this->config['new_version'] ) );

			// Only set the updater to download if its a beta or pre-release version and is newer.
			if ( $is_beta_rc ) {
				$transient->response[ $filename ] = (object) $data;
				unset( $transient->no_update[ $filename ] );
			} else {
				$transient->no_update[ $filename ] = (object) $data;
				unset( $transient->response[ $filename ] );
			}

			return $transient;
		} // END api_check()

		/**
		 * Filters the Plugin Installation API response results.
		 *
		 * @access public
		 * @param  object|WP_Error $response Response object or WP_Error.
		 * @param  string          $action   The type of information being requested from the Plugin Installation API.
		 * @param  object          $args     Plugin API arguments.
		 * @return object          $response The plugin results.
		 */
		public function get_plugin_info( $response, $action, $args ) {
			// Check that we are getting plugin information.
			if ( 'plugin_information' !== $action ) {
				return $response;
			}

			// Check if this call for the API is for the right plugin.
			if ( ! isset( $args->slug ) || $args->slug != $this->config['slug'] ) {
				return $response;
			}

			// Update tags
			$this->set_update_args();

			// New Version
			$new_version = $this->config['new_version'];

			// If the new version is no different than the one installed then just return response.
			if ( version_compare( $this->config['version'], $new_version, '=' ) ) {
				return $response;
			}

			// Prepare warning!
			$warning = '';

			if ( $this->is_stable_version( $new_version ) ) {
				$warning = sprintf( __( '%1$s%3$sThis is a stable release%3$s%2$s', 'cocart-beta-tester' ), '<h1>', '</h1>', '<span>&#9888;</span>' );
			}

			if ( $this->is_beta_version( $new_version ) ) {
				$warning = sprintf( __( '%1$s%3$sThis is a beta release%3$s%2$s', 'cocart-beta-tester' ), '<h1>', '</h1>', '<span>&#9888;</span>' );
			}

			if ( $this->is_rc_version( $new_version ) ) {
				$warning = sprintf( __( '%1$s%3$sThis is a pre-release%3$s%2$s', 'cocart-beta-tester' ), '<h1>', '</h1>', '<span>&#9888;</span>' );
			}

			// Update the results to return.
			$response->name            = $this->config['plugin_name'];
			$response->plugin_name     = $this->config['plugin_name'];
			$response->version         = $new_version;
			$response->author          = $this->config['author'];
			$response->author_homepage = 'https://cocart.xyz';
			$response->homepage        = $this->config['homepage'];
			$response->requires        = $this->config['requires'];
			$response->tested          = $this->config['tested'];
			$response->requires_php    = $this->config['requires_php'];
			$response->last_updated    = $this->config['last_updated'];
			$response->slug            = $this->config['slug'];
			$response->plugin          = $this->config['slug'];

			// Sections
			$response->sections        = array(
				'description' => $this->config['description'],
				'changelog'   => $this->config['changelog']
			);
			$response->download_link   = $this->config['zip_url'];

			$download_counter = wp_remote_get( 'https://download.cocart.xyz/counter/dl-counter.php' );
			if ( ! is_wp_error( $download_counter ) ) {
				$response->downloaded = wp_remote_retrieve_body( $download_counter );
			}

			$response->contributors = array(
				'cocart' => array(
					'display_name' => 'CoCart',
					'profile'      => esc_url( 'https://cocart.xyz' ),
					'avatar'       => get_avatar_url( 'hello@cocart.xyz', array(
						'size' => '36',
					) ),
				),
				'sebd86' => array(
					'display_name' => 'Sébastien Dumont',
					'profile'      => esc_url( 'https://sebastiendumont.com' ),
					'avatar'       => get_avatar_url( 'mailme@sebastiendumont.com', array(
						'size' => '36',
					) ),
				),
			);

			// Add WordPress dot org banners for recognition.
			$response->banners = array(
				'low'  => 'https://raw.githubusercontent.com/co-cart/co-cart/master/.wordpress-org/banner-772x250.jpg',
				'high' => 'https://raw.githubusercontent.com/co-cart/co-cart/master/.wordpress-org/banner-1544x500.jpg'
			);

			// Apply warning to all sections if any.
			foreach ( $response->sections as $key => $section ) {
				$response->sections[ $key ] = $warning . $section;
			}

			return $response;
		} // END get_plugin_info()

		/**
		 * Rename the downloaded zip file.
		 *
		 * @access public
		 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
		 * @param  string             $source        File source location.
		 * @param  string             $remote_source Remote file source location.
		 * @param  WP_Upgrader        $upgrader      WordPress Upgrader instance.
		 * @return string
		 */
		public function upgrader_source_selection( $source, $remote_source, $upgrader ) {
			global $wp_filesystem;

			if ( strstr( $source, '/co-cart-' ) ) {
				$corrected_source = trailingslashit( $remote_source ) . trailingslashit( $this->config[ 'proper_folder_name' ] );

				if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
					return $corrected_source;
				} else {
					return new WP_Error( 'cocart_updater_source_selection', __( 'Unable to move source of update.', 'cocart-beta-tester' ), 500 );
				}
			}

			return $source;
		} // END upgrader_source_selection()

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
			return ! self::is_beta_version( $version_str ) && ! self::is_rc_version( $version_str );
		} // END is_stable_version()

	} // END class

} // END if class exists

return CoCart_Beta_Tester::instance();
