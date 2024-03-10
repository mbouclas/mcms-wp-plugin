<?php
namespace Mcms\Api\RestRouteHandlers;
use WP_REST_Request;
use WP_REST_Response;
use WC_Product_Query;
use WP_Query;

function title_filter( $where, $wp_query ) {
	global $wpdb;
	if ( $search_term = $wp_query->get( 'post_title_like' ) ) {
		$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $wpdb->esc_like( $search_term ) ) . '%\'';
	}
	return $where;
}

/**
 * Get all products with filters from woocormmerce
 * @param WP_REST_Request $request
 *
 * @return void
 */
function get_products( WP_REST_Request $request ) {
	$page = $request->get_param('page') ? $request->get_param('page') : 1;
	$title = $request->get_param('title');
	$category = $request->get_param('category');
	$price_from = $request->get_param('price_from');
	$price_to = $request->get_param('price_to');
	$withAggregations = $request->get_param('withAggregations');

	$args = array(
		'post_type' => 'product',
		'posts_per_page' => 10,
		'paged' => $page,
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
	add_filter( 'posts_where', '\Mcms\Api\RestRouteHandlers\title_filter', 10, 2 );

	$query = new WP_Query($args);
	$products = $query->get_posts();

	// Create an array to hold the product data
	$product_data = array();

	// Loop through each product and add its data to the array
	foreach ($products as $product) {
		$product_obj = wc_get_product($product->ID);
		$data = $product_obj->get_data();
		$data['acf'] = get_fields($product_obj->get_id()); // Get ACF fields for the product

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
				$variation = wc_get_product($variation_id);
				$data['variations'][] = $variation->get_data();
			}
		}

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
	$quantity = $request->get_param('quantity') ? $request->get_param('quantity') : 1;

	if (!$product_id) {
		return new WP_REST_Response(array('message' => 'Product ID is required'), 400);
	}

	$added = WC()->cart->add_to_cart($product_id, $quantity);

	if ($added) {
		return new WP_REST_Response(array('message' => 'Product added to cart'), 200);
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