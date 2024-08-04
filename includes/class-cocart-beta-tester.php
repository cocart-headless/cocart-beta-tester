<?php
/**
 * CoCart Beta Tester plugin main class
 *
 * @package CoCart_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * CoCart_Beta_Tester Main Class.
 */
class CoCart_Beta_Tester {

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
	 *
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
		register_activation_hook( COCART_BETA_TESTER_FILE, array( $this, 'activate' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( COCART_BETA_TESTER_FILE ), array( $this, 'plugin_action_links' ), 10, 1 );

		$this->includes();
	} // END__construct()

	/**
	 * Ran on activation to flush update cache.
	 *
	 * @access public
	 *
	 * @static
	 */
	public static function activate() {
		delete_site_transient( 'update_plugins' );
	} // END activate()

	/**
	 * Include any classes we need within admin.
	 *
	 * @access public
	 */
	public function includes() {
		include_once __DIR__ . '/class-cocart-beta-tester-admin-assets.php';
		include_once __DIR__ . '/class-cocart-beta-tester-admin-menus.php';
		include_once __DIR__ . '/class-cocart-beta-tester-channel.php';
		include_once __DIR__ . '/class-cocart-beta-tester-plugin-update.php';
		include_once __DIR__ . '/class-cocart-beta-tester-core.php';
		include_once __DIR__ . '/class-cocart-beta-tester-version-picker.php';
	} // END includes()

	/**
	 * Get the plugin url.
	 *
	 * @access public
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', COCART_BETA_TESTER_FILE ) );
	} // END plugin_url()

	/**
	 * Show action links on the plugin screen.
	 *
	 * @access public
	 *
	 * @param mixed $links Plugin Action links.
	 *
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

	/**
	 * Get plugin settings.
	 *
	 * @access public
	 *
	 * @static
	 *
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
} // END class
