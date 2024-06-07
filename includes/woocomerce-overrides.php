<?php

add_action( 'rest_api_init', function() {
	if (!class_exists('WooCommerce')) {
		return;
	}
	//Add featured image
	register_rest_field(
		'product_cat', // Name of the post type. I am using 'product_cat' for WooCommerce categories
		'featured_media', // Name of the custom field. You can name this anything
		array(
			'get_callback'    => function ( $object, $field_name, $request ) {

				// Return the image src for the rest field
				return get_term_meta( $object[ 'id' ], 'thumbnail_id', true );
			},
			'update_callback' => null,
			'schema'          => null,
		)
	);
} );