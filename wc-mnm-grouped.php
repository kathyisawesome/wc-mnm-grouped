<?php
/**
 * Plugin Name: WooCommerce Mix and Match -  Grouped Containers
 * Plugin URI: http://www.woocommerce.com/products/wc-mnm-grouped/
 * Description: Group multiple Mix and Match products together to approximate variations
 * Version: 1.0.0-beta-2
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com/
 * Developer: Kathy Darling
 * Developer URI: http://kathyisawesome.com/
 * Text Domain: wc-mnm-grouped
 * Domain Path: /languages
 *
 * Copyright: © 2020 Kathy Darling
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace WC_MNM_Grouped;

define( 'WC_MNM_GROUPED_VERSION', '1.0.0-beta-2' );

/**
 * WC_MNM_Grouped Constructor
 *
 * @access 	public
 * @return 	WC_MNM_Grouped
 */
function init() {

	// Product class.
	require_once 'includes/class-wc-product-grouped-mnm.php';

	// Load translation files.
	add_action( 'init', __NAMESPACE__ . '\load_plugin_textdomain' );

	// Product type option.
	add_filter( 'product_type_selector', __NAMESPACE__ . '\product_selector_filter' );

	// Rewrite endpoint
	add_action( 'init', __NAMESPACE__ . '\rewrite_endpoint' );

	// Display fields.
	add_action( 'woocommerce_product_options_related', __NAMESPACE__ . '\product_options' );

	// Save data.
	add_action( 'woocommerce_admin_process_product_object', __NAMESPACE__ . '\process_meta', 20 );

	// Display the products on the front end.
	add_action( 'woocommerce_grouped-mnm_add_to_cart', __NAMESPACE__ . '\add_to_cart_template' );

	// Print custom styles.
	add_action( 'wp_print_styles', __NAMESPACE__ . '\print_styles' );

	// Register Scripts.
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_scripts' );

	// Display Scripts.
	add_action( 'woocommerce_grouped-mnm_add_to_cart', __NAMESPACE__ . '\load_scripts' );

	// Ajax callback
	add_action( 'wc_ajax_get_mix_and_match', __NAMESPACE__ . '\get_mix_and_match' );

	// QuickView support.
	add_action( 'wc_quick_view_enqueue_scripts', __NAMESPACE__ . '\load_scripts' );

}


/*-----------------------------------------------------------------------------------*/
/* Localization */
/*-----------------------------------------------------------------------------------*/


/**
 * Make the plugin translation ready
 *
 * @return void
 */
function load_plugin_textdomain() {
	\load_plugin_textdomain( 'wc-mnm-grouped' , false , dirname( plugin_basename( __FILE__ ) ) .  '/languages/' );
}


/*-----------------------------------------------------------------------------------*/
/* Product Type */
/*-----------------------------------------------------------------------------------*/


/**
 * Adds support for the 'grouped mix and match' product type.
 *
 * @param  array 	$options
 * @return array
 * @since  1.0.0
 */
function product_selector_filter( $options ) {
	$options[ 'grouped-mnm' ] = __( 'Grouped Mix and Match product', 'wc-mnm-grouped' );
	return $options;
}

/**
 * Add rewrite tags
 *
 * @link https://codex.wordpress.org/Rewrite_API/add_rewrite_tag
 */
function rewrite_endpoint() {
	add_rewrite_endpoint( 'mnm', EP_PERMALINK | EP_PAGES );
}


/*-----------------------------------------------------------------------------------*/
/* Admin */
/*-----------------------------------------------------------------------------------*/


/**
 * Related products.
 */
function product_options() {
	global $post;
	$product_object = $post->ID ? new \WC_Product_Grouped_MNM( $post->ID ) : new \WC_Product_Grouped_MNM();

	// Exclude all but Mix and Match products.
	$product_types = wc_get_product_types();
	unset( $product_types['mix-and-match'] );
	$product_types = array_keys( $product_types );

	?>
	<div class="options_group show_if_grouped-mnm">
		<p class="form-field">
			<label for="grouped_mnm_products"><?php esc_html_e( 'Grouped Mix and Match products', 'wc-mnm-grouped' ); ?></label>

			<select class="wc-product-search"
				multiple="multiple"
				style="width: 50%;"
				id="grouped_mnm_products"
				name="grouped_mnm_products[]"
				data-sortable="true"
				data-placeholder="<?php esc_attr_e( 'Search for a Mix and Match product&hellip;', 'wc-mnm-grouped' ); ?>"
				data-action="woocommerce_json_search_products"
				data-exclude="<?php echo intval( $post->ID ); ?>"
				data-exclude_type="<?php echo esc_attr( join( ",", $product_types ) );?>"
			>
				<?php
				$product_ids = $product_object->is_type( 'grouped-mnm' ) ? $product_object->get_children( 'edit' ) : array();

				foreach ( $product_ids as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( is_object( $product ) ) {
						echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . htmlspecialchars( wp_kses_post( $product->get_formatted_name() ) ) . '</option>';
					}
				}
				?>
			</select> <?php echo wc_help_tip( __( 'This lets you choose which Mix and Match products are part of this group.', 'wc-mnm-grouped' ) ); // WPCS: XSS ok. ?>
		</p>
	</div>

	<?php
}


/**
 * Saves the new meta field.
 *
 * @param  WC_Product_Mix_and_Match  $product
 */
function process_meta( $product ) {

	if ( $product->is_type( 'grouped-mnm' ) ) {

		$children = isset( $_POST['grouped_mnm_products'] ) ? array_filter( array_map( 'intval', (array) $_POST['grouped_mnm_products'] ) ) : array();

		$product->set_children( $children );

	}

}


/*-----------------------------------------------------------------------------------*/
/* Front End Display */
/*-----------------------------------------------------------------------------------*/


/**
 * Display child options.
 */
function add_to_cart_template() {

	global $product;

	$selection     = get_query_var( 'mnm' );
	$has_selection = in_array( intval( $selection ), $product->get_children() );


	$transient_name    = 'wc_mnm_grouped_product_loop_' . md5( json_encode( $product->get_children() ) );
	$transient_version = \WC_Cache_Helper::get_transient_version( 'product_query' );
	$cache             = true;
	$transient_value   = $cache ? get_transient( $transient_name ) : false;

	$transient_value = array();

	if ( isset( $transient_value['value'], $transient_value['version'] ) && $transient_value['version'] === $transient_version ) {
		$products = $transient_value['value'];
	} else {
		$products = array_map( 'wc_get_product', $product->get_children() );


		if ( $cache ) {
			$transient_value = array(
				'version' => $transient_version,
				'value'   => $products,
			);
	//		set_transient( $transient_name, $transient_value, DAY_IN_SECONDS * 30 );
		}
	}

	// Set global loop values.
	wc_set_loop_prop( 'name', 'grouped-mnm' );
	wc_set_loop_prop( 'columns', apply_filters( 'wc_grouped_mnm_columns', 3 ) );

	if ( $products ) {

		add_filters();

		if ( $products ) {
			wc_get_template(
				'single-product/add-to-cart/grouped-mnm.php',
				array(
					'grouped_product'  => $product,
					'grouped_products' => $products,
					'selection'        => $selection,
					'has_selection'    => $has_selection,
				),
				'',
				get_plugin_path() . '/templates/'
			);
		}

		remove_filters();

	}

}


/**
 * Insert the opening anchor tag for products in the loop.
 */
function loop_product_link_open() {
	global $product;

	$link = apply_filters( 'woocommerce_loop_product_link', get_the_permalink(), $product );

	echo '<a href="' . esc_url( $link ) . '" data-product_id="' . esc_attr( $product->get_id() ) . '" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">';
}


/**
 * Filters the list of CSS class names for the current post.
 *
 * @since 2.7.0
 *
 * @param string[] $classes An array of post class names.
 * @param string[] $class   An array of additional class names added to the post.
 * @param int      $post_id The post ID.
 */
function selected_post_class( $classes, $class, $post_id ) {
	if ( intval( get_query_var( 'mnm' ) ) === $post_id ) {
		$classes[] = 'selected';
	}
	return $classes;
}


/**
 * Insert the opening anchor tag for products in the loop.
 */
function add_filters() {
		// Force visibility.
		add_filter( 'woocommerce_product_is_visible', '__return_true', 20 );

		// Add selected post_class
		add_filter( 'post_class', __NAMESPACE__ . '\selected_post_class', 10, 3 );

		// Swap open link template.
		remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open' );
		add_action( 'woocommerce_before_shop_loop_item', __NAMESPACE__ . '\loop_product_link_open' );
}

/**
 * Insert the opening anchor tag for products in the loop.
 */
function remove_filters() {
	remove_filter( 'woocommerce_product_is_visible', '__return_true', 20 );
	remove_filter( 'post_class', __NAMESPACE__ . '\selected_post_class', 10, 3 );
	add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open' );
	remove_action( 'woocommerce_before_shop_loop_item', __NAMESPACE__ . '\loop_product_link_open' );
}




/*-----------------------------------------------------------------------------------*/
/* Scripts and Styles */
/*-----------------------------------------------------------------------------------*/


/**
 * Print some very minimal styles.
 */
function print_styles() { ?>

	<style>
		.wc-grouped-mnm-wrapper.has-selection .wc-grouped-mnm-selector .product:not(.selected) {
			opacity: .5;
		}
	</style>

<?php

}


/**
 * Register scripts
 *
 * @return void
 */
function register_scripts() {
	wp_register_script( 'wc-mnm-grouped', get_plugin_url() . '/assets/js/wc-mnm-grouped.js', array( 'wc-add-to-cart-mnm', 'jquery-blockui' ), WC_MNM_GROUPED_VERSION, true );
}

/**
 * Load the script anywhere the MNN add to cart button is displayed
 * @return void
 */
function load_scripts() {
	wp_enqueue_script( 'jquery-blockui' );
	wp_enqueue_script( 'wc-add-to-cart-mnm' );
	wp_enqueue_script( 'wc-mnm-grouped' );

	$l10n = array( 'wc_ajax_url' => \WC_AJAX::get_endpoint( '%%endpoint%%' ) );

	wp_localize_script( 'wc-mnm-grouped', 'WC_MNM_GROUPED_PARAMS', $l10n );

}


/**
 * Return the MNM template via ajax.
 * 
 * @return void
 */
function get_mix_and_match() {

	check_ajax_referer( 'get_mix_and_match', 'security' );

	$response = array( 'result' => 'fail' );

	if ( isset( $_POST['product_id'] ) ) {

		$html = get_mix_and_match_template_html( intval( $_POST['product_id'] ) );

		$response = array(
					'result' => 'success',
					'html'   => $html,
				);

	}
	
	wp_send_json( $response );
}


/**
 * Return the specific MNM template
 *
 * @param  int $product_id
 * @return string
 */
function get_mix_and_match_template_html( $product_id ) {

	$html = '';

	global $product;
	$backup_product = $product;

	$product = wc_get_product( intval( $product_id ) );

	if ( $product && $product->is_type( 'mix-and-match' ) ) {

		add_filter( 'woocommerce_get_product_add_to_cart_form_location', __NAMESPACE__ . '\force_form_location' );
		
		ob_start();
		echo '<h2>' . esc_html__( 'Select options', 'wc-grouped-mnm' ) . '</h2>';
		do_action( 'woocommerce_mix-and-match_add_to_cart' );
		$html = ob_get_clean();

		add_filter( 'woocommerce_get_product_add_to_cart_form_location', __NAMESPACE__ . '\force_form_location' );

	}

	// Restore product object.
	$product = $backup_product;
	
	return $html;
}

/**
 * Force form location to be default
 *
 * @param  string $value
 * @return string
 */
function force_form_location( $value ) {
	return 'default';
}


/*-----------------------------------------------------------------------------------*/
/* Cart item names */
/*-----------------------------------------------------------------------------------*/

/**
 * Force form location to be default
 *
 * @param  string $value
 * @param  int    $product_id
 * @return string
 */
function cart_item_name_in_quotes( $name, $product_id ) {
	if( isset( $_REQUEST['grouped_mnm_id'] ) ) {
		/* translators: %1$s grouped parent product name %2$s: product name  */
		$name = sprintf( _x( '&ldquo;%1$s &mdash; %2$s&rdquo;', 'Item name in quotes', 'wc-grouped-mnm' ), strip_tags( get_the_title( intval( $_REQUEST['grouped_mnm_id'] ) ) ), strip_tags( get_the_title( $product_id ) ) );
	}
	return $name;
}



/*-----------------------------------------------------------------------------------*/
/* Helpers */
/*-----------------------------------------------------------------------------------*/

/**
 * Plugin URL.
 *
 * @return string
 */
function get_plugin_url() {
	return plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename(__FILE__) );
}

/**
 * Plugin path.
 *
 * @return string
 */
function get_plugin_path() {
	return untrailingslashit( plugin_dir_path( __FILE__ ) );
}


/*-----------------------------------------------------------------------------------*/
/* Launch the whole plugin. */
/*-----------------------------------------------------------------------------------*/
add_action( 'woocommerce_mnm_loaded', __NAMESPACE__ . '\init' );


register_activation_hook( __FILE__, __NAMESPACE__ . '\plugin_activate' );
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\plugin_uninstall' );


/**
 * Flush rewrite rules on activation.
 */
function plugin_activate() {

	// If grouped type does not exist, create it.
	if ( is_null( get_term_by( 'slug', 'grouped-mnm', 'product_type' ) ) ) {
		wp_insert_term( __( 'Grouped Mix and Match product', 'wc-mnm-grouped' ), 'product_type', array( 'slug' => 'grouped-mnm' ) );
	}
	rewrite_endpoint();
	flush_rewrite_rules();
}

/**
 * Flush rewrite rules on uninstall.
 */
function plugin_uninstall() {
	flush_rewrite_rules();
}
