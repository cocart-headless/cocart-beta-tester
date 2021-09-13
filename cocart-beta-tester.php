<?php
/**
 * Plugin Name: CoCart - Beta Tester
 * Plugin URI:  https://github.com/co-cart/cocart-beta-tester
 * Description: A <strong>RESTful API</strong> made for <strong>WooCommerce</strong>, focusing on <strong>the front-end</strong> of the store helping you to manage shopping carts and allows developers to build a <strong>headless store</strong>.
 * Author: CoCart
 * Author URI: https://cocart.xyz
 * Version:     2.0.0
 * Text Domain: cocart-beta-tester
 * Domain Path: /languages/
 * Requires at least: 5.5
 * Requires PHP: 7.3
 * GitHub Plugin URI: https://github.com/co-cart/cocart-beta-tester
 *
 * @package CoCart_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'COCART_TESTER_FILE' ) ) {
	define( 'COCART_TESTER_FILE', __FILE__ );
}

/**
 * Returns the main instance of CoCart Beta Tester and only runs if it does not already exists.
 *
 * @since  2.0.0
 * @return CoCart_Beta_Tester
 */
if ( ! function_exists( 'CoCart_Beta_Tester' ) ) {
	/**
	 * Initialize CoCart Beta Tester.
	 */
	function CoCart_Beta_Tester() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		if ( ! defined( 'COCART_VERSION' ) ) {
			add_action( 'admin_notices', function() {
				include_once untrailingslashit( plugin_dir_path( COCART_TESTER_FILE ) ) . '/includes/views/html-admin-notice-missing-cocart.php';
			});
		} else if ( ! class_exists( 'CoCart_Beta_Tester', false ) ) {
			include_once untrailingslashit( plugin_dir_path( COCART_TESTER_FILE ) ) . '/includes/class-cocart-beta-tester.php';

			CoCart_Beta_Tester::instance();
		}
	}

	CoCart_Beta_Tester();
}
