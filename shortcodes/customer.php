<?php
function createResetLinkUrlShortCode(  $shortcode_list, $yaymail_informations, $args = array() ) {
	//It only runs when tested with real order


//	error_log(print_r($args, true));
	error_log(print_r($args, true));
	error_log(print_r('************************************', true));

//	error_log(print_r($yaymail_informations, true));
/*	$link_reset = add_query_arg(
		array(
			'key' => $args['reset_key'],
			'id'  => $user->ID,
		),
		wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) )
	);*/



	return wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) );
}

add_filter(
	'yaymail_customs_shortcode',
	function ( $shortcode_list, $yaymail_informations, $args = array() ) {
		$shortcode_list['[yaymail_custom_reset_password_link]'] = createResetLinkUrlShortCode( $shortcode_list, $yaymail_informations, $args );

		return $shortcode_list;
	},
	10,
	3
);


