<?php
/**
 * CoCart Beta Tester plugin settings class.
 *
 * @package CoCart_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings Class.
 */
class CoCart_Beta_Tester_Settings {

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'admin_menu', array( $this, 'add_to_menus' ) );
	} // END __construct()

	/**
	 * Initialise settings.
	 *
	 * @access public
	 */
	public function settings_init() {
		register_setting( 'cocart-beta-tester', 'cocart_beta_tester_options' );

		add_settings_section(
			'cocart-beta-tester-update',
			__( 'Settings', 'cocart-beta-tester' ),
			array( $this, 'update_section_html' ),
			'cocart-beta-tester'
		);

		add_settings_field(
			'cocart-beta-tester-channel',
			__( 'Release Channel', 'cocart-beta-tester' ),
			array( $this, 'version_select_html' ),
			'cocart-beta-tester',
			'cocart-beta-tester-update',
			array(
				'label_for' => 'channel',
			)
		);

		add_settings_field(
			'cocart-beta-tester-auto-update',
			__( 'Automatic Updates', 'cocart-beta-tester' ),
			array( $this, 'automatic_update_checkbox_html' ),
			'cocart-beta-tester',
			'cocart-beta-tester-update',
			array(
				'label_for' => 'auto_update',
			)
		);
	} // END settings_init()

	/**
	 * Update section HTML output.
	 *
	 * @access public
	 * @param  array $args Arguments.
	 */
	public function update_section_html( $args ) {
		?>
		<p><?php esc_html_e( 'The following settings allow you to choose which CoCart updates to receive on this site, including beta and RC versions not quite ready for production deployment.', 'cocart-beta-tester' ); ?></p>

		<p><?php esc_html_e( 'By their nature, if not using the stable channel, these releases are unstable and should not be used anyplace where your data is important. We strongly recommend you perform the test on a staging site and create a complete backup of your WordPress files and database prior to performing a version switch. We are not responsible for any misuse, deletions, white screens, fatal errors, or any other issue arising from using this plugin.', 'cocart-beta-tester' ); ?></p>

		<p><?php echo sprintf( __( 'In order to hear about the latest beta releases, your best bet is to watch the %1$sdevelopment blog%2$s.', 'cocart-beta-tester' ), '<a href="https://cocart.dev/news/" target="_blank">', '</a>' ); ?></p>
		<?php
	} // END update_section_html()

	/**
	 * Version select markup output.
	 *
	 * @access public
	 * @param  array $args Arguments.
	 */
	public function version_select_html( $args ) {
		$settings = CoCart_Beta_Tester::get_settings();

		$channels = array(
			/*'nightly'   => array(
				'name'        => __( 'Nightly Releases', 'cocart-beta-tester' ),
				'description' => __( 'Nightly releases contain experimental functionality for testing purposes only. This channel will include features that are unstable and may not move forward into a beta release.', 'cocart-beta-tester' ),
			),*/
			'beta'   => array(
				'name'        => __( 'Beta Releases', 'cocart-beta-tester' ),
				'description' => __( 'Beta releases contain upcoming features for testing purposes only. This channel will also include RC and stable releases if more current.', 'cocart-beta-tester' ),
			),
			'rc'     => array(
				'name'        => __( 'Release Candidates', 'cocart-beta-tester' ),
				'description' => __( 'Release candidates are released to ensure any critical problems have not gone undetected. This channel will also include stable releases if more current.', 'cocart-beta-tester' ),
			),
			'stable' => array(
				'name'        => __( 'Stable Releases', 'cocart-beta-tester' ),
				'description' => __( 'This is the default behavior in WordPress.', 'cocart-beta-tester' ),
			),
		);

		echo '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Update Channel', 'cocart-beta-tester' ) . '</span></legend>';

		foreach ( $channels as $channel_id => $channel ) {
			?>
			<label>
				<input type="radio" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="cocart_beta_tester_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo esc_attr( $channel_id ); ?>" <?php checked( $settings->{ $args['label_for'] }, $channel_id ); ?> />
				<?php echo esc_html( $channel['name'] ); ?>
				<p class="description">
					<?php echo esc_html( $channel['description'] ); ?>
				</p>
			</label>
			<br>
			<?php
		}
		echo '</fieldset>';
	} // END version_select_html()

	/**
	 * Auto updates checkbox markup output.
	 *
	 * @access public
	 * @param  array $args Arguments.
	 */
	public function automatic_update_checkbox_html( $args ) {
		$settings = CoCart_Beta_Tester::get_settings();
		?>
		<label for="<?php echo esc_attr( $args['label_for'] ); ?>">
			<input type="checkbox" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="cocart_beta_tester_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="1" <?php checked( $settings->{ $args['label_for'] }, true ); ?> />
			<?php echo esc_html__( 'If enabled, CoCart will update to the latest release in the background. Use with caution; we do not recommend using this on production stores!', 'cocart-beta-tester' ); ?>
		</label>
		<?php
	} // END automatic_update_checkbox_html()

	/**
	 * Add options page to menu.
	 *
	 * @access public
	 */
	public function add_to_menus() {
		add_submenu_page( 'plugins.php', __( 'CoCart Beta Tester', 'cocart-beta-tester' ), __( 'CoCart Beta Tester', 'cocart-beta-tester' ), 'install_plugins', 'cocart-beta-tester', array( $this, 'settings_page_html' ) );
	} // END add_to_menus()

	/**
	 * Output settings HTML.
	 *
	 * @access public
	 */
	public function settings_page_html() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( 'cocart-beta-tester-messages', 'cocart-beta-tester-message', __( 'Settings Saved', 'cocart-beta-tester' ), 'updated' );
		}

		// Show error/update messages.
		settings_errors( 'cocart-beta-tester-messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
		<?php
		settings_fields( 'cocart-beta-tester' );
		do_settings_sections( 'cocart-beta-tester' );
		submit_button();
		?>
			</form>
		</div>
		<?php
	} // END settings_page_html()

} // END class

new CoCart_Beta_Tester_Settings();
