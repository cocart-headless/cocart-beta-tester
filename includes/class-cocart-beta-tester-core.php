<?php
/**
 * CoCart Beta Tester.
 *
 * Checks for any beta updates available for the core of CoCart.
 *
 * @package CoCart_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * CoCart_Beta_Tester_Core Class.
 */
class CoCart_Beta_Tester_Core extends CoCart_Beta_Tester_Plugin_Update {

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->plugin_file = 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php';
		$this->plugin_slug = 'cart-rest-api-for-woocommerce';

		parent::__construct( $this->plugin_file, $this->plugin_slug );
	} // END__construct()

} // END class

return new CoCart_Beta_Tester_Core();
