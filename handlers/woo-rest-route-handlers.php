<?php
namespace Mcms\Api\WooRestRouteHandlers;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WC_Session_Handler;
use WP_REST_Request;
use WP_REST_Response;
use WC_Product_Query;
use WP_Query;
use function Mcms\Includes\Auth\validate_jwt;
use function \Mcms\Includes\Helpers\formatOptionValues;


add_action('init', 'Mcms\Api\WooRestRouteHandlers\check_customer');

function get_media(WP_REST_Request $request) {
	$req = new WP_REST_Request( 'GET', '/wp/v2/media' );
	$administrators = get_users(array('role' => 'administrator'));

	wp_set_current_user($administrators[0]->ID);
// Set parameters if needed.
	$params = $request->get_params();
	foreach ($params as $key => $value) {
		$req->set_param( $key, $value );
	}

	$req->set_param('status', 'inherit');

// Get a reference to the WP_REST_Server instance.
	$server = rest_get_server();

// Process the request.
	$response = $server->dispatch( $req );

// Get the response data.
	$data = $response->get_data();
	return new WP_REST_Response($data, $response->get_status(), $response->get_headers());
/*	$args = array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => 'inherit',
		'posts_per_page' => $limit,
		'paged'          => $page,
	);

	$query_images = new WP_Query($args);
	$images = array();
	foreach ($query_images->posts as $image) {
		// Get the full attachment object
		$attachment = get_post($image->ID);
		// Get ACF fields for the attachment
		$acf_fields = get_fields($image->ID);
		// Add the ACF fields to the attachment object
		$attachment->acf_fields = $acf_fields;
		$attachment->title = ['rendered' => $attachment->post_title];
		// Get additional fields for the attachment
		$attachment_fields = wp_prepare_attachment_for_js($image->ID);
		// Add the additional fields to the attachment object
		$attachment->alt_text = $attachment_fields['alt'];
		$attachment->media_details = $attachment_fields['media_details'];
		$attachment->media_title = $attachment_fields['title'];
		$attachment->caption = $attachment_fields['caption'];
		$attachment->description = $attachment_fields['description'];
		$attachment->source_url = wp_get_attachment_url($image->ID);
		// Add the attachment object to the images array
		$images[] = $attachment;
	}


	return $images;*/
}

function check_customer() {
	if (class_exists('WooCommerce')) {
		if (null === WC()->session) {
			WC()->session = new WC_Session_Handler();
			WC()->session->init();
		}
/*		if (null === WC()->customer) {
			error_log('Customer is not set');
		} else {
			error_log(print_r(WC()->customer, true));
		}*/
	}
}

function title_filter( $where, $wp_query ) {
	global $wpdb;
	if ( $search_term = $wp_query->get( 'post_title_like' ) ) {
		$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $wpdb->esc_like( $search_term ) ) . '%\'';
	}
	return $where;
}

function product_variations( WP_REST_Request $request ) {
	$args = array(
		'post_type' => 'product_variation',
		'posts_per_page' => -1,
	);

	$posts = get_posts($args);

	$posts_array = array();
	foreach ($posts as $post) {
		$posts_array[] = (array) $post;
	}

	return new WP_REST_Response($posts_array);
}

function get_product_attributes( WP_REST_Request $request ) {
	if (!function_exists('wc_get_attribute_taxonomies')) {
		return new WP_REST_Response([]);
	}

	$attribute_taxonomies = wc_get_attribute_taxonomies();
	$attributes = array();

	foreach ($attribute_taxonomies as $tax) {
		$taxonomy = wc_attribute_taxonomy_name($tax->attribute_name);
		$terms = get_terms($taxonomy, array('hide_empty' => false));

		$term_values = array();
		foreach ($terms as $term) {
			// Get ACF fields for the term
			$acf_fields = get_fields($term);
			$term_values[] = array_merge((array) $term, ['acf_fields' => $acf_fields]);
		}

		// Get ACF fields for the product attribute
		$acf_fields = get_fields($taxonomy);

		$attributes[] = array(
			'id' => $tax->attribute_id,
			'name' => $tax->attribute_name,
			'label' => $tax->attribute_label,
			'values' => $term_values,
			'acf_fields' => $acf_fields
		);
	}

	return $attributes;
}

/**
 * Get all products with filters from woocormmerce
 * @param WP_REST_Request $request
 *
 * @return void
 */
function get_products( WP_REST_Request $request ) {
	$page = $request->get_param('page') ? $request->get_param('page') : 1;
	$limit = $request->get_param('limit') ? $request->get_param('limit') : 10;
	$title = $request->get_param('title');
	$category = $request->get_param('category');
	$price_from = $request->get_param('price_from');
	$price_to = $request->get_param('price_to');
	$withAggregations = $request->get_param('withAggregations');
	$status = $request->get_param('status') ?: 'publish';

	$args = array(
		'post_type' => 'product',
		'posts_per_page' => $limit,
		'paged' => $page,
		'post_status' => $status,
	);

	// Add title filter if provided
	if ($title) {
		$args['post_title_like'] = $title;
	}

	// Add category filter if provided
	if ($category) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $category,
			),
		);
	}

	if ($price_from || $price_to) {
		$args['meta_query'] = array('relation' => 'AND');

		if ($price_from) {
			$args['meta_query'][] = array(
				'key' => '_price',
				'value' => $price_from,
				'compare' => '>=',
				'type' => 'NUMERIC'
			);
		}

		if ($price_to) {
			$args['meta_query'][] = array(
				'key' => '_price',
				'value' => $price_to,
				'compare' => '<=',
				'type' => 'NUMERIC'
			);
		}
	}

	// Custom query to filter by title
	add_filter( 'posts_where', '\Mcms\Api\WooRestRouteHandlers\title_filter', 10, 2 );

	$query = new WP_Query($args);
	$products = $query->get_posts();

	// Create an array to hold the product data
	$product_data = array();

	// Loop through each product and add its data to the array
	foreach ($products as $product) {
		$product_obj = wc_get_product($product->ID);
		$data = $product_obj->get_data();
		$data['acf'] = get_fields($product_obj->get_id()); // Get ACF fields for the product
		// Get the featured image as an object
		$data['featured_image'] = get_the_post_thumbnail_url($product_obj->get_id());

//		$data['featured_image_id'] = get_post_thumbnail_id($product_obj->get_id());
		// Get product attributes
		$attributes = $product_obj->get_attributes();
		$data['attributes'] = array();
		foreach ($attributes as $attribute) {
			$data['attributes'][] = $attribute->get_data();
		}

		// Get product variations if it's a variable product
		if ($product_obj->is_type('variable')) {
			$data['variations'] = array();
			$variation_ids = $product_obj->get_children();
			foreach ($variation_ids as $variation_id) {
				$variationData = wc_get_product($variation_id);
				$variation = $variationData->get_data();
				$variation['featured_media'] = get_the_post_thumbnail_url($variation_id);
				$variation['featured_media_id'] = get_post_thumbnail_id($variation_id);
				$data['variations'][] = $variation;
			}
		}

		$data['related_products'] = wc_get_related_products($product_obj->get_id(), 4);
//		$data['upselling_products'] = $product_obj->get_upsell_ids();

		if (class_exists('WPSEO_Meta')) {
			$meta = \YoastSEO()->meta->for_post($product_obj->get_id());
			$data['yoast_head_json'] = $meta->get_head()->json;
		}

		$data['primary_category'] = ( class_exists( 'WPSEO_Meta' ) ) ?
			get_post_meta( $product->ID, '_yoast_wpseo_primary_product_cat', true ) :
			$product_obj->get_category_ids()[0];

		$product_data[] = $data;
	}


	if (!$withAggregations) {
		return new WP_REST_Response($product_data);
	}

	// Return the product data
	return new WP_REST_Response([
		'items' => $product_data,
		'aggregations' => [
			'categories' => get_category_aggregations($request)
		]
	]);
}

function add_to_cart( WP_REST_Request $request ) {
	$product_id = $request->get_param('product_id');
	$variant = $request->get_param('variant') ?: null;
	$variationId = $variant ? $variant['id'] : null;
	$variationData = $variant ? $variant['attributes'] : null;
	$quantity = $request->get_param('quantity') ? $request->get_param('quantity') : 1;
	WC()->frontend_includes();
	$productId = $variant ? $variant['id'] : $product_id;

	if (!$product_id) {
		return new WP_REST_Response(array('message' => 'Product ID is required'), 400);
	}

	$cart_id = WC()->cart->generate_cart_id($product_id, $variationId, $variationData);

	$cart_item_key = WC()->cart->find_product_in_cart($cart_id);

	error_log(print_r( '1  '.$product_id . ' = ' . $variationId, true));
	error_log(print_r( '2  '.$cart_item_key, true));
	if ( empty($cart_item_key) && is_array( WC()->cart->get_cart() ) && isset( WC()->cart->get_cart()[ $cart_id ] ) ) {
		$cart_item_key = WC()->cart->get_cart()[ $cart_id ]['key'];
	}
	error_log(print_r('3 ' . key_exists($cart_id, WC()->cart->get_cart()) , true));
	$cart_item_quantities = WC()->cart->get_cart_item_quantities();

	if (key_exists($cart_id, WC()->cart->get_cart())) {
		$quantity = $quantity + $cart_item_quantities[ $productId ];
		$added = WC()->cart->set_quantity( $cart_item_key, $quantity );
	}
	else {
		$added = WC()->cart->add_to_cart( $product_id, $quantity, $variationId, $variationData );
	}



	if ($added) {
		return new WP_REST_Response(
			get_cart($request)
			, 200);
	} else {
		return new WP_REST_Response(array('message' => 'Failed to add product to cart'), 500);
	}
}


function create_customer( WP_REST_Request $request ) {
	$email = $request->get_param('email');
	$username = $request->get_param('username');
	$password = $request->get_param('password');

	if (!$email) {
		return new WP_REST_Response(array('message' => 'Email is required'), 400);
	}

	$customer_id = wc_create_new_customer($email, $username, $password);

	if (is_wp_error($customer_id)) {
		return new WP_REST_Response(array('message' => 'Failed to create customer', 'error' => $customer_id->get_error_message()), 500);
	} else {
		return new WP_REST_Response(array('message' => 'Customer created', 'customer_id' => $customer_id), 200);
	}
}

function get_attributes() {
	$attributes = wc_get_attribute_taxonomies();

	$attribute_data = array();

	foreach ( $attributes as $attribute ) {
		// Get the terms of the attribute taxonomy
		$options = get_terms( 'pa_' . $attribute->attribute_name );

		// Create an array to hold the options
		$options_data = array();

		// Loop through each option and add its data to the array
		foreach ( $options as $option ) {
			$options_data[] = array(
				'id'   => $option->term_id,
				'name' => $option->name,
				'slug' => $option->slug,
			);
		}

		$attribute_data[] = array(
			'id'           => $attribute->attribute_id,
			'name'         => $attribute->attribute_name,
			'label'        => $attribute->attribute_label,
			'type'         => $attribute->attribute_type,
			'order_by'     => $attribute->attribute_orderby,
			'has_archives' => $attribute->attribute_public,
			'options'      => $options_data, // Add the options to the attribute data
		);
	}

	return new WP_REST_Response($attribute_data);
}

function get_category_aggregations( WP_REST_Request $request ) {
	$price_from = $request->get_param('price_from');
	$price_to = $request->get_param('price_to');


	$categories = get_terms( 'product_cat' );

	$category_data = array();

	foreach ( $categories as $category ) {
		$args = array(
			'post_type' => 'product',
			'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $category->slug,
				),
			),
		);

		if ($price_from || $price_to) {
			$args['meta_query'] = array('relation' => 'AND');

			if ($price_from) {
				$args['meta_query'][] = array(
					'key' => '_price',
					'value' => $price_from,
					'compare' => '>=',
					'type' => 'NUMERIC'
				);
			}

			if ($price_to) {
				$args['meta_query'][] = array(
					'key' => '_price',
					'value' => $price_to,
					'compare' => '<=',
					'type' => 'NUMERIC'
				);
			}
		}

		$query = new WP_Query($args);
		$count = $query->found_posts;

		if ($count === 0) {
			continue;
		}

		$category_data[] = array(
			'name'  => $category->name,
			'slug'  => $category->slug,
			'count' => $count,
		);
	}

	return $category_data;
}

function get_cart(WP_REST_Request $request) {
	if (!class_exists('WooCommerce')) {
		return new WP_REST_Response(array('message' => 'WooCommerce is not active'), 500);
	}


	$selectedShippingMethod = WC()->session->get('chosen_shipping_methods');
	$customerDetails = get_customer($request)->data['customer'];
	// required to set the shipping zone
	$customer = new \WC_Customer($customerDetails['id']); // Create a new customer object
	WC()->customer = $customer; // Set the customer for the cart


	WC()->cart->calculate_shipping();
	WC()->cart->calculate_totals();
//	print_r(WC()->session->get('chosen_shipping_methods'));

	$applied_coupons = WC()->cart->get_applied_coupons();
	$coupon_details = array();
	foreach ($applied_coupons as $coupon_code) {
		// Get the WC_Coupon object
		$coupon = new \WC_Coupon($coupon_code);

		// Get the coupon data
		$data = $coupon->get_data();

		// Add the coupon data to the array
		$coupon_details[] = $data;
	}

	$cart = [];
	$productOptions = [];


	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
		$product = $cart_item['data'];
		$isVariant = false;
		if ($product instanceof \WC_Product_Variation) {
			$isVariant = true;
		}

		if (class_exists('YayExtra\Classes\ProductPage')) {
			$productOptions = \YayExtra\Classes\ProductPage::get_instance()->get_option_set_of_product($isVariant ? $product->get_parent_id() : $product->get_id(), \YayExtra\Helper\Utils::get_settings());
		}

		$cart[] = [
			'key' => $cart_item_key,
			'product_id' => $product->get_id(),
			'parent_id' => $product->get_parent_id(),
			'quantity' => $cart_item['quantity'],
			'price' => $product->get_price(),
			'name' => $product->get_name(),
			'total' => $product->get_price() * $cart_item['quantity'],
			'attributes' => $product->get_attributes(),
			'featured_image' => wp_get_attachment_url($product->get_image_id()),
			'permalink' => get_permalink($product->get_id()),
			'acf' => get_fields($product->get_id()),
			'shipping_class' => $product->get_shipping_class(),
			'isVariant' => $isVariant,
			'options' => $productOptions
		];
	}

	$chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');


	return new WP_REST_Response([
		'items' => $cart,
		'totalItems' => WC()->cart->get_cart_contents_count(),
		'totals' => WC()->cart->get_totals(),
		'applied_discounts' => $coupon_details,
		'selectedShippingMethod' => is_array($chosen_shipping_methods) && !empty($chosen_shipping_methods) ? $chosen_shipping_methods[0] : null,

	]);
}


function clear_cart(WP_REST_Request $request) {
	if (!class_exists('WooCommerce')) {
		return new WP_REST_Response(array('message' => 'WooCommerce is not active'), 500);
	}

	WC()->frontend_includes();

	if (null === WC()->session) {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
	}

	if (null === WC()->cart) {
		WC()->initialize_cart();
	}

	WC()->cart->empty_cart();

	return new WP_REST_Response(get_cart($request));
}


function checkout(WP_REST_Request $request) {
	if (!class_exists('WooCommerce')) {
		return new WP_REST_Response(array('message' => 'WooCommerce is not active'), 500);
	}

	WC()->frontend_includes();

	if (null === WC()->session) {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
	}

	if (null === WC()->cart) {
		WC()->initialize_cart();
	}

	$checkout = WC()->checkout();

	if (!is_user_logged_in()) {
		// Get guest details from request
		$billing_details = $request->get_param('billing');
		$shipping_details = $request->get_param('shipping');

		// Set guest details
/*		$checkout->set_data(array(
			'billing' => $billing_details,
			'shipping' => $shipping_details,
		));*/
	}


/*	try {
		$checkout->process_checkout();
	} catch (\Exception $e) {
		return new WP_REST_Response(array('message' => $e->getMessage()), 500);
	}*/


	return new WP_REST_Response($checkout->get_checkout_fields());
//	return new WP_REST_Response(get_cart());
}

function getCheckoutFields() {
	if (!class_exists('WooCommerce')) {
		return new WP_REST_Response(array('message' => 'WooCommerce is not active'), 500);
	}

	WC()->frontend_includes();

	if (null === WC()->session) {
		WC()->session = new WC_Session_Handler();
	}

	if (null === WC()->cart) {
		WC()->initialize_cart();
	}

	$checkout = WC()->checkout();
	$customer = new \WC_Customer();

	return new WP_REST_Response($checkout->get_checkout_fields());
}

function setCustomerDetails(WP_REST_Request $request) {
	if (!class_exists('WooCommerce')) {
		return new WP_REST_Response(array('message' => 'WooCommerce is not active'), 500);
	}

	WC()->frontend_includes();

	if (null === WC()->session) {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
	}

	if (null === WC()->cart) {
		WC()->initialize_cart();
	}

	$checkout = WC()->checkout();
	$customer = new \WC_Customer();

	$billing_details = $request->get_param('billing');
	$shipping_details = $request->get_param('shipping');

	foreach ($billing_details as $key => $value) {
		$customer->{"set_billing_$key"}($value);
	}
	foreach ($shipping_details as $key => $value) {
		$customer->{"set_shipping_$key"}($value);
	}
	WC()->customer = $customer;
	return new WP_REST_Response($checkout->get_checkout_fields());
}

function frontEndInit(WP_REST_Request $request) {
	$customer = \Mcms\Api\WooRestRouteHandlers\get_customer($request)->data['customer'];
	$cart = \Mcms\Api\WooRestRouteHandlers\get_cart($request)->data;
	if ($customer['id'] === 0) {
		return new WP_REST_Response([
			'customer' => null,
			'cart' => $cart,
		]);
	}


	$customerData = [
		'email' => $customer['email'],
		'firstName' => $customer['first_name'],
		'lastName' => $customer['last_name'],
		'billing' => $customer['billing'],
		'shipping' => $customer['shipping'],
		'isLoggedIn' => true,
	];

	return new WP_REST_Response([
		'customer' => $customerData,
		'cart' => $cart,
	]);
}

function get_customer(WP_REST_Request $request) {
	if (!class_exists('WooCommerce')) {
		return new WP_REST_Response(array('message' => 'WooCommerce is not active'), 500);
	}



	$auth_header = $request->get_header('Authorization');
	$decoded = \Mcms\Includes\Auth\validate_jwt(substr($auth_header, 7));

	// Invalid JWT
	if (is_object($decoded) && property_exists($decoded, 'error')) {
		return new WP_REST_Response([
			'message' => 'invalid_token',
		], 401);
	}

	$user_id = $decoded;
	$customer = new \WC_Customer($user_id);


	return new WP_REST_Response([
		'customer' => $customer->get_data(),
		'user_id' => $user_id,
	]);
}

function login_user(WP_REST_Request $request) {
	$creds = array(
		'user_login'    => $request->get_param('username'),
		'user_password' => $request->get_param('password'),
		'remember'      => (bool) $request->get_param( 'remember' ),
	);

	WC()->frontend_includes();

	if (null === WC()->session) {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
	}

	$loginIsValid = wp_authenticate($creds['user_login'], $creds['user_password']);


	if (is_wp_error($loginIsValid)) {
		return new WP_REST_Response(array('message' => 'failed_to_login',
		                                  'error' => $loginIsValid->get_error_codes()), 401);
	}

	$user = wp_signon($creds);
	wp_set_current_user( $user->ID );
	WC()->session->set('user_id', $user->ID);

	if (is_wp_error($user)) {
		return new WP_REST_Response(array('message' => 'failed_to_login',
		                                  'error' => $loginIsValid->get_error_codes()), 401);
	}
//	$user = get_user_by('email', $creds['user_login']);
	$jwt = \Mcms\Includes\Auth\generate_jwt($user->ID);
//	wp_set_current_user($user->ID);

	return new WP_REST_Response([
		'message' => 'log_in_success',
		'user' => [
			'firstName' => get_user_meta($user->ID, 'first_name', true),
			'lastName' => get_user_meta($user->ID, 'last_name', true),
			'email' => $user->user_email,
			'nicename' => $user->user_nicename,
			'nickname' => get_user_meta($user->ID, 'nickname', true)
		],
		'token' => $jwt,
	]);
}

function getStoreSettings() {
	$options = wp_load_alloptions();
	$settings = [];
	foreach ($options as $name => $value) {
		if (strpos($name, 'woocommerce_') === 0) {
			$settings[str_replace('woocommerce_', '', $name)] = formatOptionValues($value);
		}
	}

	$payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();

	$enabled_gateways = [];

	foreach ($payment_gateways as $gateway) {
/*		$enabled_gateways[] = [
			'id' => $gateway->id,
			'title' => $gateway->title,
			'description' => $gateway->description,
			'enabled' => $gateway->enabled,
			'instructions' => $gateway->get_option('instructions'),
			'default' => $gateway->is_default(),
			'account_details' => $gateway->get_option('account_details'),
		];*/
		$enabled_gateways[] = (array)$gateway;
	}

	return new WP_REST_Response([
		'settings' => $settings,
		'paymentMethods' => $enabled_gateways,
	]);
}

function getShippingMethods(WP_REST_Request $request) {

	$methods = [];

$zone = getCustomerShippingZone($request);
//$zone = WC_Shipping_Zone::get_zone($zone_id);

// Get the shipping methods for the zone
	$shipping_methods = $zone->get_shipping_methods(true, 'json');

// Loop through each shipping method
	foreach ($shipping_methods as $shipping_method) {
		if (!$shipping_method->enabled || $shipping_method->enabled === 'no') {
			continue;
		}
//print_r($shipping_method->instance_settings);
		$methods[] = [
			'id' => $shipping_method->id . ':' . $shipping_method->instance_id,
			'slug' => $shipping_method->id,
			'title' => $shipping_method->title,
			'settings' => $shipping_method->instance_settings,
			'baseCost' => !empty($shipping_method->cost) ? $shipping_method->cost : null,
		];
	}


	return new WP_REST_Response($methods);
}

function setShippingMethod(WP_REST_Request $request) {
	$id = $request->get_param('id');

	WC()->session->set('chosen_shipping_methods', [$id]);

	return new WP_REST_Response(get_cart($request)->data);
}

function getCustomerShippingZone(WP_REST_Request $request) {
	$customer = get_customer($request)->data['customer'];

// Create a package array
	$package = array(
		'destination' => array(
			'country'  => $customer['shipping']['country'],
			'state'    => $customer['shipping']['state'],
			'postcode' => $customer['shipping']['postcode'],
		),
	);

// Get the shipping zone that matches the package
	return \WC_Shipping_Zones::get_zone_matching_package($package);
}

function getPaymentSettings(WP_REST_Request $request) {
	$gateway_id = $request->get_param('id');

	$payment_gateways = WC()->payment_gateways()->payment_gateways();
	$gateway = $payment_gateways[$gateway_id];

	if (strpos($gateway->id, 'stripe') !== false) {
		$stripe = new \Mcms\Api\StripeRestRouteHandlers\StripeRestRouteHandlers();

		return new WP_REST_Response([
			'publishable_key' => $stripe->publicKey,
			'id' => 'stripe'
		]);
	}


	return new WP_REST_Response($payment_gateways[$gateway_id]);

}

function getAvailablePaymentMethods() {
	$payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();

	$enabled_gateways = [];

	foreach ($payment_gateways as $gateway) {
		if (empty($gateway->title)) {
			continue;
		}

		$enabled_gateways[] = [
			'id' => $gateway->id,
			'title' => $gateway->title,
			'description' => $gateway->description,
			'enabled' => $gateway->enabled,
			'instructions' => $gateway->get_option('instructions'),
//			'default' => $gateway->is_default(),
			'account_details' => $gateway->get_option('account_details'),
		];
	}

	return $enabled_gateways;
}


