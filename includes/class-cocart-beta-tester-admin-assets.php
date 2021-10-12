<?php
/**
 * CoCart Beta Tester - Admin Assets class
 *
 * @package CoCart_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * CoCart_Beta_Tester_Admin_Assets Class.
 */
class CoCart_Beta_Tester_Admin_Assets {

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	} // END __construct()

	/**
	 * Enqueue scripts.
	 *
	 * @access public
	 */
	public function admin_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Need admin styles for the modal.
		wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );

		// Register scripts.
		wp_register_script( 'cc-beta-tester-version-picker', CoCart_Beta_Tester::instance()->plugin_url() . '/assets/js/version-picker' . $suffix . '.js', array( 'wc-backbone-modal' ), COCART_BETA_TESTER_VERSION );


		wp_localize_script(
			'cc-beta-tester-version-picker',
			'cc_beta_tester_version_picker_params',
			array(
				'i18n_pick_version' => __( 'Please pick a CoCart version.', 'cocart-beta-tester' ),
			)
		);

		if ( in_array( $screen_id, array( 'plugins_page_cocart-beta-tester', 'plugins_page_cocart-beta-tester-version-picker' ) ) ) {
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_script( 'cc-beta-tester-version-picker' );
		}
	} // END admin_scripts()

} // END class

return new CoCart_Beta_Tester_Admin_Assets();
