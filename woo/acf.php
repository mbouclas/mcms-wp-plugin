<?php
// Render acf inside the variations tab. Javascript fields are not working.
add_action( 'woocommerce_product_after_variable_attributes', function( $loop, $variation_data, $variation ) {
	global $abcdefgh_i; // Custom global variable to monitor index
	$abcdefgh_i = $loop;
	acf_enqueue_scripts();

	// Add filter to update field name
//	add_filter( 'acf/prepare_field', 'acf_prepare_field_update_field_name' );

	// Loop through all field groups
	$acf_field_groups = acf_get_field_groups();
	ob_start();
	foreach( $acf_field_groups as $acf_field_group ) {
		foreach( $acf_field_group['location'] as $group_locations ) {
			foreach( $group_locations as $rule ) {

				// See if field Group has at least one post_type = Variations rule - does not validate other rules
//                print_r($rule);
				if( $rule['param'] == 'post_type' && $rule['operator'] == '==' && $rule['value'] == 'product_variation' ) {
					// Render field Group

					acf_render_fields( $variation->ID, acf_get_fields( $acf_field_group ) );
					break 2;
				}
			}
		}
	}
$html =  ob_get_contents();
	ob_end_clean();

	echo $html;
	// Remove filter
//	remove_filter( 'acf/prepare_field', 'acf_prepare_field_update_field_name' );

	?>
	<script type="text/javascript">
        jQuery(document).ready(function() {
            window.acf.doAction('load');
        });
	</script>
	<?php
}, 10, 3 );

function  acf_prepare_field_update_field_name( $field ) {
	global $abcdefgh_i;
	error_log(print_r('------------- ' . $field['name'], true));
	if(strpos($field['name'], '[acfcloneindex]') !== false) {
		error_log(print_r('**** ' . $field['name'], true));
        preg_match('/acf\[(\d+)\]\[(field_\w+)\]\[acfcloneindex\]\[(field_\w+)\]/', $field['name'], $matches);

		$field['name'] = "acf[$matches[2]][row-$matches[1]][$matches[3]]";

		error_log(print_r('+++++  ' . $field['name'], true));
		error_log(print_r($matches, true));
	} else {
		$field['name'] = preg_replace( '/^acf\[/', "acf[$abcdefgh_i][", $field['name'] );
	}


    return $field;
}

// Save variation data
add_action( 'woocommerce_save_product_variation', function( $variation_id, $i = -1 ) {
	// Update all fields for the current variation
	if ( ! empty( $_POST['acf'] ) && is_array( $_POST['acf'] ) && array_key_exists( $i, $_POST['acf'] ) && is_array( ( $fields = $_POST['acf'][ $i ] ) ) ) {
		foreach ( $fields as $key => $val ) {
			update_field( $key, $val, $variation_id );
		}
	}
}, 10, 2 );


// Renders non javascript acf fields in the order details page
add_action( 'woocommerce_admin_order_data_after_order_details', function( $order ) {
	//show all acf fields with post_type order
	$acf_field_groups = acf_get_field_groups();
	foreach( $acf_field_groups as $acf_field_group ) {
		foreach( $acf_field_group['location'] as $group_locations ) {
			foreach( $group_locations as $rule ) {
				if( $rule['param'] == 'post_type' && $rule['operator'] == '==' && $rule['value'] == 'shop_order' ) {
					acf_render_fields( $order->get_id(), acf_get_fields( $acf_field_group ) );
					break 2;
				}
			}
		}
	}
}, 10, 3 );