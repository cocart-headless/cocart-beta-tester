<?php
/**
 * CoCart Beta Tester - Admin Menus class.
 *
 * @package CoCart_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * CoCart_Beta_Tester_Admin_Menus Main Class.
 */
class CoCart_Beta_Tester_Admin_Menus {

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
		if ( class_exists( 'CoCart' ) ) {
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menus' ), 50 );
		}

		add_action( 'admin_head', array( $this, 'hide_from_menus' ) );
	} // END __construct()

	/**
	 * Add the "CoCart Beta Tester" link in admin bar main menu.
	 *
	 * @access public
	 * @param  WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function admin_bar_menus( $wp_admin_bar ) {
		if ( ! is_admin() || ! is_user_logged_in() ) {
			return;
		}

		// Show only when the user is a member of this site, or they're a super admin.
		if ( ! is_user_member_of_blog() && ! is_super_admin() ) {
			return;
		}

		// Add the beta tester root node.
		$wp_admin_bar->add_node(
			array(
				'parent' => 0,
				'id'     => 'cocart-beta-tester',
				'title'  => __( 'CoCart Beta Tester', 'cocart-beta-tester' ),
			)
		);

		$settings = CoCart_Beta_Tester::get_settings();
		switch ( $settings->channel ) {
			case 'nightly':
				$current_channel = __( 'Nightly Build', 'cocart-beta-tester' );
				break;
			case 'beta':
				$current_channel = __( 'Beta', 'cocart-beta-tester' );
				break;
			case 'rc':
				$current_channel = __( 'Release Candidate', 'cocart-beta-tester' );
				break;
			default:
				$current_channel = __( 'Stable', 'cocart-beta-tester' );
				break;
		}

		$wp_admin_bar->add_node(
			array(
				'parent' => 'cocart-beta-tester',
				'id'     => 'cocart-update-channel',
				/* translators: %s: current channel */
				'title'  => sprintf( __( 'Channel: %s', 'cocart-beta-tester' ), $current_channel ),
				'href'   => admin_url( 'plugins.php?page=cocart-beta-tester' ),
			)
		);

		$wp_admin_bar->add_group(
			array(
				'parent' => 'cocart-beta-tester',
				'id'     => 'cocart-beta-tester-internal',
				'meta'   => array(
					'class' => 'ab-sub-secondary',
				),
			)
		);
	
		$nodes = array(
			array(
				'parent' => 'cocart-beta-tester-internal',
				'id'     => 'cocart-show-version-info',
				/* translators: %s: current version */
				'title'  => sprintf( __( 'Release %s information', 'cocart-beta-tester' ), esc_attr( strstr( COCART_VERSION, '-', true ) ) ) . ' <span class="dashicons dashicons-external" style="font-family: dashicons !important;"></span>',
				'href'   => '#',
				'meta'   => array(
					// We can't simply use the href here since WP core calls esc_url on it which strips some parts.
					'onclick' => 'javascript:window.open( "' . esc_js( esc_url( 'https://github.com/co-cart/co-cart/blob/v' . COCART_VERSION . '/CHANGELOG.md' ) ) . '" );',
				),
			),
			array(
				'parent' => 'cocart-beta-tester-internal',
				'id'     => 'cocart-switch-version',
				'title'  => __( 'Switch versions', 'cocart-beta-tester' ),
				'href'   => admin_url( 'plugins.php?page=cocart-beta-tester-version-picker' ),
			),
			array(
				'parent' => 'cocart-beta-tester-internal',
				'id'     => 'cocart-submit-gh-ticket',
				'title'  => __( 'Submit bug report', 'cocart-beta-tester' ) . ' <span class="dashicons dashicons-external" style="font-family: dashicons !important;"></span>',
				'href'   => '#',
				'meta'   => array(
					// We can't simply use the href here since WP core calls esc_url on it which strips some parts.
					'onclick' => 'javascript:window.open( "' . esc_js( 'https://github.com/co-cart/co-cart/issues/new?assignees=&labels=priority%3Alow&template=bug_report.md&&title=[' . COCART_VERSION . ']: Title of the issue' ) . '" );',
				),
			),
		);

		foreach ( $nodes as $node ) {
			$wp_admin_bar->add_node( $node );
		}
	} // END admin_bar_menus()

	/**
	 * Hide menu items from view so the pages exist, but the menu items do not.
	 *
	 * @access public
	 * @global $submenu
	 */
	public function hide_from_menus() {
		global $submenu;

		$items_to_remove = array( 'cocart-beta-tester-settings', 'cocart-beta-tester-version-picker', 'cocart-beta-tester' );

		if ( isset( $submenu['plugins.php'] ) ) {
			foreach ( $submenu['plugins.php'] as $key => $menu ) {
				if ( in_array( $menu[2], $items_to_remove ) ) {
					unset( $submenu['plugins.php'][ $key ] );
				}
			}
		}
	} // END hide_from_menus()

} // END class

return new CoCart_Beta_Tester_Admin_Menus();