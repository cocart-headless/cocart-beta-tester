<?php
/**
 * Plugin Name: CoCart - Beta Tester
 * Plugin URI:  https://github.com/co-cart/cocart-beta-tester
 * Description: Easily update to prerelease versions of CoCart for testing and development purposes.
 * Author:      CoCart
 * Author URI:  https://cocart.xyz
 * Version:     2.2.0
 * Text Domain: cocart-beta-tester
 * Domain Path: /languages/
 * Requires at least: 5.5
 * Requires PHP: 7.4
 * GitHub Plugin URI: https://github.com/co-cart/cocart-beta-tester
 *
 * @package CoCart_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'COCART_BETA_TESTER_FILE' ) ) {
	define( 'COCART_BETA_TESTER_FILE', __FILE__ );
}

if ( ! defined( 'COCART_BETA_TESTER_VERSION' ) ) {
	define( 'COCART_BETA_TESTER_VERSION', '2.2.0' );
}

/**
 * Load text domain before all other code.
 *
 * @since 2.0.0
 */
function _ccbt_load_text_domain() {
	load_plugin_textdomain( 'cocart-beta-tester', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', '_ccbt_load_text_domain' );

/**
 * Returns the main instance of CoCart Beta Tester and only runs if it does not already exists.
 *
 * @since 2.0.0
 *
 * @return CoCart_Beta_Tester
 */
if ( ! function_exists( 'cocart_beta_tester' ) ) {
	/**
	 * Initialize CoCart Beta Tester.
	 */
	function cocart_beta_tester() {
		if ( ! defined( 'COCART_VERSION' ) ) {
			add_action( 'admin_notices', function () {
				include_once untrailingslashit( plugin_dir_path( COCART_BETA_TESTER_FILE ) ) . '/includes/views/html-admin-notice-missing-cocart.php';
			} );
		} elseif ( ! class_exists( 'CoCart_Beta_Tester' ) ) {
			include_once untrailingslashit( plugin_dir_path( COCART_BETA_TESTER_FILE ) ) . '/includes/class-cocart-beta-tester.php';

			CoCart_Beta_Tester::instance();
		}
	}

	add_action( 'plugins_loaded', function () {
		cocart_beta_tester();
	}, 99 );
}
