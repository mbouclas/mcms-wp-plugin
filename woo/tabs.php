<?php
add_filter('woocommerce_product_data_tabs', 'add_my_custom_product_data_tab' , 99 , 1 );
function add_my_custom_product_data_tab($product_data_tabs){
	$product_data_tabs['my_tab'] = array(
		'label' => 'My Tab',
		'target' => 'my_product_data',
	);
	return $product_data_tabs;
}

// Step 2: Display the tab content
add_action('woocommerce_product_data_panels', 'add_my_custom_product_data_content');
function add_my_custom_product_data_content(){
	global $post;
	acf_enqueue_scripts();
	?>
    <div id='my_product_data' class='panel woocommerce_options_panel'>
		<?php
		// Get all fields within the ACF group
		$acf_fields = acf_get_fields('group_6563c39cdc9e4'); // replace 'group_123456' with your ACF group ID

		// Check if any fields were retrieved
		if($acf_fields) {
			foreach($acf_fields as $field) {
				$field_value = get_field($field['name'], $post->ID);

				// Prepare field for rendering
				$field['value'] = $field_value;

				// Render the field
				acf_render_field($field);
			}
		}


		?>
    </div>
	<?php
}

add_action('acf/input/admin_enqueue_scripts', 'my_acf_admin_enqueue_scripts');
function my_acf_admin_enqueue_scripts() {
	?>
    <script type="text/javascript">
setTimeout(() => {
    console.log('will load')
    window.acf.doAction('load');
}, 3000)
    </script>
	<?php
}
// Step 3: Save the data
add_action('woocommerce_process_product_meta', 'save_my_custom_product_data', 10, 2);
function save_my_custom_product_data($post_id, $post){
	update_post_meta($post_id, '_my_custom_field', wc_clean($_POST['_my_custom_field']));
}


