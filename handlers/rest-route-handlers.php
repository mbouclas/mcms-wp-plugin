<?php
namespace Mcms\Handlers;
use \WP_REST_Request;
use \WP_REST_Response;
use \WP_Error;
use acf_options_page;

function handle_get_all_post_types() {
	$post_types = get_post_types([], 'objects');
	return $post_types;
}


function handle_get_acf_group_fields_request( WP_REST_Request $request ) {
	// get the fields from acf. The id is passed as a query param ?id=123
	$groupId = $request->get_param( 'id' );
	if (empty($groupId)) {
	    
	    return new WP_REST_Response(getFieldsForAllGroups());
	}

	$fields = acf_get_fields("group_$groupId");

	return new WP_REST_Response( $fields );

}

function handle_get_menus_request(WP_REST_Request $request) {
	$menuId = $request->get_param( 'id' );
	if (!empty($menuId)) {
		$menus = wp_get_nav_menus([
			'slug' => $menuId,
		]);
	} else {
		$menus = wp_get_nav_menus();
	}

	foreach ($menus as $key => $menu) {
		$menu_items = wp_get_nav_menu_items($menu->term_id);


		$menus[$key]->items = [];
		foreach ($menu_items as $item_key => $item) {
			$fields = get_fields($item->ID);
			if ($fields) {
				$menu_items[$item_key]->acf_fields = $fields;
				$menu_items[$item_key]->acf = $fields;
			}
		}

		$menus[$key]->items = buildNestedMenu($menu_items);
	}
	return new WP_REST_Response( $menus );

}

function buildNestedMenu($menu_items) {
	// Initialize an array to hold the nested menu
	$nested_menu = [];

	// Create an associative array to hold references to all items by their ID
	$items_by_id = [];

	// First pass: Initialize the items_by_id array
	foreach ($menu_items as $item) {
		$items_by_id[$item->ID] = $item;
		$item->children = []; // Initialize the children array
	}

	// Second pass: Build the nested structure
	foreach ($menu_items as $item) {
		if ($item->menu_item_parent == 0) {
			// This is a root item, add it to the nested menu
			$nested_menu[] = $item;
		} else {
			// This is a child item, add it to its parent's children array
			if (isset($items_by_id[$item->menu_item_parent])) {
				$items_by_id[$item->menu_item_parent]->children[] = $item;
			} else {
				// Handle the case where the parent ID does not exist
				error_log("Parent ID {$item->menu_item_parent} not found for item ID {$item->ID}");
			}
		}
	}

	return $nested_menu;
}

function getFieldsForAllGroups() {
    $field_groups = acf_get_field_groups();
    $all = [];
    
    foreach ($field_groups as $field_group) {
        $all[] = array_merge($field_group, ['fields' => acf_get_fields($field_group['key'])]);

    }
    
    return $all;
}

/**
 * Return array if no $id param provided. Object otherwise
 * @param WP_REST_Request $request
 * @return array[]|array
 */
function handle_get_acf_options_pages_request(WP_REST_Request $request) {
    $pageId = $request->get_param( 'id' );
    
    if (empty($pageId)) {
        return new WP_REST_Response(getOptionsPageValues());
    }
    
    $ret = [];
    
    foreach (getOptionsPageValues() as $page) {
        if ($page['title'] == $pageId) {
            $ret = $page;
            break;
        }
    }
    
    return new WP_REST_Response($ret);
    
}

function getOptionsPageValues() {
    // Get all field groups
    $groups = acf_get_field_groups();
    $all = [];
    // Loop through each group
    foreach ($groups as $group) {
        // Check if the field group is for the options page
        if (strpos($group['location'][0][0]['param'], 'options_page') !== false) {
            // Get all fields for this group
            $fields = acf_get_fields($group['key']);
            foreach ($fields as $idx => $field) {
                $fields[$idx]['value'] = get_field($field['name'], 'options');
            } 
            
            $all[] = array_merge($group, ['fields' => $fields] );
            // Loop through each field and get its value

        }
    }
    
    return $all;
}

function handle_contact_request(WP_REST_Request $request) {

}

