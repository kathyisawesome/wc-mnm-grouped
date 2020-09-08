<?php
/**
 * Grouped Mix and Match Product
 *
 * Grouped products cannot be purchased - they are wrappers for other products.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product grouped class.
 */
class WC_Product_Grouped_MNM extends WC_Product_Grouped {

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'grouped-mnm';
	}

}
