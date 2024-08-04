<?php
/**
 * Admin View: CoCart not installed or activated notice.
 *
 * @author  Sébastien Dumont
 * @package CoCar Beta Tester\Views
 * @since   2.0.0
 * @license GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="notice notice-warning">
	<p>
		<?php
		printf(
			/* translators: 1: CoCart Beta Tester, 4: CoCart */
			__( '%1$s requires %2$s%4$s%3$s to be installed and activated in order to serve updates from %2$sGitHub%3$s repository.', 'cocart-beta-tester' ),
			'CoCart Beta Tester',
			'<strong>',
			'</strong>',
			'CoCart'
		);
		?>
	</p>

	<p>
		<?php
		if ( ! is_plugin_active( 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php' ) && file_exists( WP_PLUGIN_DIR . '/cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php' ) ) :
			if ( current_user_can( 'activate_plugin', 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php' ) ) :
				printf( '<a href="%1$s" class="button button-primary" aria-label="%2$s">%2$s</a>', esc_url( wp_nonce_url( self_admin_url( add_query_arg( array(
					'action'        => 'activate',
					'plugin'        => 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php',
					'plugin_status' => 'active',
				), 'plugins.php' ) ), 'activate-plugin_cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php' ) ), sprintf( esc_html__( 'Activate %s', 'cocart-beta-tester' ), 'CoCart' ) );
			else :
				printf( esc_html__( 'As you do not have permission to activate a plugin. Please ask a site administrator to activate %s for you.', 'cocart-beta-tester' ), 'CoCart' );
			endif;
		else :
			if ( current_user_can( 'install_plugins' ) ) {
				$url = wp_nonce_url( self_admin_url( add_query_arg( array(
					'action' => 'install-plugin',
					'plugin' => 'cart-rest-api-for-woocommerce',
				), 'update.php' ) ), 'install-plugin_cart-rest-api-for-woocommerce' );
			} else {
				$url = 'https://wordpress.org/plugins/cart-rest-api-for-woocommerce/';
			}

			echo '<a href="' . esc_url( $url ) . '" class="button button-primary" aria-label="' . sprintf( esc_html__( 'Install %s', 'cocart-beta-tester' ), 'CoCart' ) . '">' . sprintf( esc_html__( 'Install %s', 'cocart-beta-tester' ), 'CoCart' ) . '</a>';
		endif;

		if ( current_user_can( 'deactivate_plugin', 'cocart-pro/cocart-pro.php' ) ) :
			printf(
				' <a href="%1$s" class="button button-secondary" aria-label="%2$s">%2$s</a>',
				esc_url( wp_nonce_url( self_admin_url( add_query_arg( array(
					'action' => 'deactivate',
					'plugin' => 'cocart-beta-tester/cocart-beta-tester.php',
				), 'plugins.php' ) ), 'deactivate-plugin_cocart-beta-tester/cocart-beta-tester.php' ) ),
				esc_html__( 'Turn off Beta Tester', 'cocart-beta-tester' )
			);
		endif;
		?>
	</p>
</div>
