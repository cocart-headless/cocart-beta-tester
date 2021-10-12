<?php
/**
 * Beta Tester plugin Version Picker class
 *
 * @package CoCart_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings Class.
 */
class CoCart_Beta_Tester_Version_Picker {

	/**
	 * Currently installed version of CoCart plugin.
	 *
	 * @var string
	 */
	protected $current_version = '';

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_to_menus' ) );
		add_action( 'admin_init', array( $this, 'handle_version_switch' ) );
	}

	/**
	 * Handler for the version switch button.
	 *
	 * @access public
	 * @throws Exception On update error.
	 */
	public function handle_version_switch() {
		if ( ! isset( $_GET['ccbt_switch_to_version'], $_GET['_wpnonce'] ) ) { // WPCS: Input var ok.
			return;
		}

		if ( ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'ccbt_switch_version_nonce' ) ) { // WPCS: Input var ok, sanitization ok.
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'cocart-beta-tester' ) );
		}

		$version = isset( $_GET['ccbt_switch_to_version'] ) ? sanitize_text_field( wp_unslash( $_GET['ccbt_switch_to_version'] ) ) : ''; // WPCS: Input var ok, sanitization ok.

		if ( empty( $version ) ) {
			return;
		}

		try {
			include dirname( __FILE__ ) . '/class-cocart-beta-tester-plugin-upgrader.php';

			$plugin_name = 'cart-rest-api-for-woocommerce';
			$plugin      = 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php';
			$skin_args   = array(
				'type'    => 'web',
				'url'     => 'plugins.php?page=cocart-beta-tester-version-picker',
				'title'   => __( 'Version switch result', 'cocart-beta-tester' ),
				'plugin'  => $plugin_name,
				'version' => $version,
				'nonce'   => wp_unslash( $_GET['_wpnonce'] ), // WPCS: Input var ok, sanitization ok.
			);

			$skin     = new Automatic_Upgrader_Skin( $skin_args );
			$upgrader = new CoCart_Beta_Tester_Plugin_Upgrader( $skin );
			$result   = $upgrader->switch_version( $plugin );

			// Try to reactivate.
			activate_plugin( $plugin, '', is_network_admin(), true );

			if ( is_wp_error( $skin->result ) ) {
				throw new Exception( $skin->result->get_error_message() );
			} elseif ( false === $result ) {
				throw new Exception( __( 'Update failed', 'cocart-beta-tester' ) );
			}

			wp_safe_redirect( admin_url( 'plugins.php?page=cocart-beta-tester-version-picker&switched-cocart-version=' . rawurlencode( $version ) ) );
			exit;
		} catch ( Exception $e ) {
			if ( class_exists( 'CoCart_Admin_Notices' ) ) {
				CoCart_Admin_Notices::add_custom_notice(
					$plugin . '_update_error',
					sprintf(
						// translators: 1: plugin name, 2: error message.
						__( '%1$s could not be updated (%2$s).', 'cocart-beta-tester' ),
						$plugin,
						$e->getMessage()
					)
				);
				wp_safe_redirect( admin_url( 'plugins.php?page=cocart-beta-tester-version-picker' ) );
				exit;
			} else {
				wp_die( esc_html( $e->getMessage() ) );
			}
		}
	}

	/**
	 * Add options page to menu.
	 *
	 * @return void
	 */
	public function add_to_menus() {
		add_submenu_page(
			'plugins.php',
			__( 'CoCart Version Switch', 'cocart-beta-tester' ),
			__( 'CoCart Version Switch', 'cocart-beta-tester' ),
			'install_plugins',
			'cocart-beta-tester-version-picker',
			array( $this, 'select_versions_form_html' )
		);
	}

	/**
	 * Return HTML code representation of list of CoCart versions for the selected channel.
	 *
	 * @param string $channel Filter versions by channel: all|beta|rc|stable.
	 * @return string
	 */
	public function get_versions_html( $channel ) {
		$tags = CoCart_Beta_Tester::instance()->get_tags( $channel );

		if ( ! $tags ) {
			return '';
		}

		$versions_html = '';

		if ( ! empty( $_GET['switched-cocart-version'] ) ) { // WPCS: input var ok, CSRF ok.
			/* translators: %s: CoCart version  */
			$versions_html .= '<div class="notice notice-success"><p>' . sprintf( esc_html__( 'Successfully switched version to %s.', 'cocart-beta-tester' ), esc_html( sanitize_text_field( wp_unslash( $_GET['switched-cocart-version'] ) ) ) ) . '</p></div>';
		}

		$plugin_data           = CoCart_Beta_Tester::instance()->get_plugin_data();
		$this->current_version = $plugin_data['Version'];

		if ( 'nightly' !== $channel ) {
			$versions_html        .= '<ul class="ccbt-version-list">';

			// Loop through versions and output in a radio list.
			foreach ( $tags as $tag ) {
				$tag_version = str_replace( 'v', '', $tag->tag_name );

				$versions_html .= '<li class="ccbt-version-li">';
				$versions_html .= '<label><input type="radio" ' . checked( $tag_version, $this->current_version, false ) . ' value="' . esc_attr( $tag->tag_name ) . '" name="ccbt_switch_to_version">' . $tag->tag_name;

				// Is this the current version?
				if ( $tag_version === $this->current_version ) {
					$versions_html .= '<span class="ccbt-current-version">' . esc_html__( '&nbsp;Installed Version', 'cocart-beta-tester' ) . '</span>';
				}

				$versions_html .= '</label>';
				$versions_html .= '</li>';
			}

			$versions_html .= '</ul>';
		} else {
			echo '<p>' . esc_html__( 'You are currently only able to install nightly builds of CoCart which does not have any version specified.', 'cocart-beta-tester' ) . '</p>';
		}

		return $versions_html;
	}

	/**
	 * Echo HTML form to switch CoCart versions, filtered for the selected channel.
	 */
	public function select_versions_form_html() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$settings = CoCart_Beta_Tester::get_settings();
		$channel  = $settings->channel;
		?>
		<style type="text/css">
		.plugins_page_cocart-beta-tester-version-picker .wc-backbone-modal-main .wc-backbone-modal-header h1 {
			margin: 0 35px 0 0;
		}
		</style>
		<div class="wrap">
			<div class="ccbt-content-wrap">
				<h1><?php esc_html_e( 'Available CoCart Releases', 'cocart-beta-tester' ); ?></h1>

				<form name="ccbt-select-version" class="ccbt-select-version-form" action="<?php echo esc_attr( admin_url( '/plugins.php' ) ); ?>">
					<div class="ccbt-versions-wrap">
						<?php echo $this->get_versions_html( $channel ); ?>
					</div>

					<?php if ( 'nightly' !== $channel ) { ?>
					<div class="ccbt-submit-wrap">
						<a href="#ccbt-modal-version-switch-confirm" class="button-primary" id="ccbt-modal-version-switch-confirm"><?php esc_html_e( 'Switch version', 'cocart-beta-tester' ); ?></a>
					</div>
					<?php } ?>
					<?php wp_nonce_field( 'ccbt_switch_version_nonce' ); ?>
					<input type="hidden" name="noheader" value="1">
					<input type="hidden" name="page" value="cocart-beta-tester-version-picker">

					<script type="text/template" id="tmpl-ccbt-version-switch-confirm">
						<div class="wc-backbone-modal wc-backbone-modal-beta-tester-version-info">
							<div class="wc-backbone-modal-content">
								<section class="wc-backbone-modal-main" role="main">
									<header class="wc-backbone-modal-header">
										<h1>
											<?php
											esc_html_e( 'Are you sure you want to switch the version of CoCart plugin?', 'cocart-beta-tester' );
											?>
										</h1>
										<button class="modal-close modal-close-link dashicons dashicons-no-alt">
											<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'cocart-beta-tester' ); ?></span>
										</button>
									</header>
									<article>

										<table class="ccbt-widefat widefat">
											<tbody>
											<tr class="alternate">
												<td class="row-title">
													<label for="tablecell"><?php esc_html_e( 'Installed Version:', 'cocart-beta-tester' ); ?></label>
												</td>
												<td><span class="ccbt-installed-version"><?php echo esc_html( $this->current_version ); ?></span></td>
											</tr>
											<tr>
												<td class="row-title">
													<label for="tablecell"><?php esc_html_e( 'New Version:', 'cocart-beta-tester' ); ?></label>
												</td>
												<td><span class="ccbt-new-version">{{ data.new_version }}</span></td>
											</tr>
											</tbody>
										</table>

										<p class="ccbt-notice"><?php esc_html_e( 'Notice: We strongly recommend you perform the test on a staging site and create a complete backup of your WordPress files and database prior to performing a version switch. We are not responsible for any misuse, deletions, white screens, fatal errors, or any other issue arising from using this plugin.', 'cocart-beta-tester' ); ?></p>
									</article>
									<footer>
										<input type="submit" value="<?php esc_attr_e( 'Switch version', 'cocart-beta-tester' ); ?>" class="button-primary ccbt-go" id="ccbt-submit-version-switch"/>&nbsp;
										<a href="#" class="modal-close modal-close-link"><?php esc_attr_e( 'Cancel', 'cocart-beta-tester' ); ?></a>
									</footer>
								</section>
							</div>
						</div>
						<div class="wc-backbone-modal-backdrop modal-close"></div>
					</script>

				</form>
			</div>
		</div>
		<?php
	}
}

new CoCart_Beta_Tester_Version_Picker();