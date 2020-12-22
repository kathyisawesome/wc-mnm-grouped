<?php
/**
 * Grouped Mix and Match Product Add to Cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/grouped-mnm.php.
 *
 * HOWEVER, on occasion WooCommerce Mix and Match will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  Kathy Darling
 * @package WooCommerce Mix and Match/Templates
 * @since   1.0.0
 * @version 1.8.0
 */

namespace WC_MNM_Grouped;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ){
	exit;
}
 
global $product;

/**
 * woocommerce_before_add_to_cart_form hook.
 */
do_action( 'woocommerce_before_add_to_cart_form' ); 
?>


<div data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" class="wc-grouped-mnm-wrapper <?php echo esc_attr( $has_selection ? 'has-selection' : '' );?>" data-security="<?php echo esc_attr( wp_create_nonce( 'get_mix_and_match') ); ?>">
	
	<?php if ( ! empty( $grouped_products ) ) : ?> 

		<div class="wc-grouped-mnm-selector">

		<?php woocommerce_product_loop_start(); ?>

		<?php foreach ( $grouped_products as $grouped_product ) : ?>
			<?php

				$post_object = get_post( $grouped_product->get_id() );

				setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found

				wc_get_template_part( 'content', 'product' );
				?>

		<?php endforeach; ?>

		<?php woocommerce_product_loop_end(); ?>

		</div>

		<?php wp_reset_postdata(); ?>

		<div class="wc-grouped-mnm-result">
			<?php echo get_mix_and_match_template_html( $selection ); ?>
		</div>

	<?php else: ?>

		<div class="mnm_message woocommerce-info">
			 <?php esc_html_e( 'No related Mix and Match products', 'wc-grouped-mnm' ); ?>
		</div>

	<?php endif; ?>


	</div>
</div>

<?php 
/**
 * woocommerce_after_add_to_cart_form hook.
 */
do_action( 'woocommerce_after_add_to_cart_form' ); 
?>