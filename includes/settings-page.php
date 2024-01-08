<?php
namespace Mcms\Includes;
// register an options page

	add_action(
		'acf/init',
		function () {
			// Add the top-level page.
			acf_add_options_page(
				[
					'page_title'  => 'Sync Settings',
					'menu_slug'   => 'sync-settings',
					'parent_slug' => 'mcms',
					'redirect'   => false,
				]
			);


			snycSettingsOptions();
		});
function snycSettingsOptions() {
	acf_add_local_field_group([
		'key'      => 'group_sync_settings',
		'title'    => 'Sync Settings',
		'fields' => [
			[
				'key'           => 'field_primary_tab',
				'label'         => 'General',
				'name'          => 'general',
				'type'          => 'tab',
			],
			[
				'key'           => 'field_site_url',
				'label'         => 'Site URL',
				'name'          => 'site-url',
				'type'          => 'url',
				'placeholder'   => 'The site url',
				'default_value' => '',
				'required'      => 1,
			],
			[
				'key'           => 'field_astro_folder',
				'label'         => 'Astro Folder',
				'name'          => 'astro_folder',
				'type'          => 'text',
				'placeholder'   => 'The Astro folder location',
				'default_value' => '',
			],
			[
				'key'           => 'field_astro_key',
				'label'         => 'Astro Key',
				'name'          => 'astro_key',
				'type'          => 'text',
				'placeholder'   => 'Add the Astro key',
				'default_value' => '',
			],
			[
				'key'           => 'field_preview_site_url',
				'label'         => 'Preview Site URL',
				'name'          => 'preview-site-url',
				'type'          => 'url',
				'placeholder'   => 'The preview site url',
				'default_value' => '',
				'required'      => 1,
			],
			[
				'key'           => 'field_cloudflare_project_name',
				'label'         => 'Cloudflare Project Name',
				'name'          => 'cloudflare_project_name',
				'type'          => 'text',
				'placeholder'   => 'The Cloudflare Project Name',
				'default_value' => '',
				'required'      => 1,
			],
			[
				'key'           => 'field_cloudflare_account_id',
				'label'         => 'Cloudflare Account ID',
				'name'          => 'cloudflare_account_id',
				'type'          => 'text',
				'placeholder'   => 'The Cloudflare Account ID',
				'default_value' => '',
				'required'      => 1,
			],
			[
				'key'           => 'field_cloudflare_api_key',
				'label'         => 'Cloudflare API Key',
				'name'          => 'cloudflare_api_key',
				'type'          => 'text',
				'placeholder'   => 'The Cloudflare API Key',
				'default_value' => '',
				'required'      => 1,
			],
			[
				'key'           => 'field_cloudflare_deploy_hook',
				'label'         => 'Cloudflare Deploy Hook',
				'name'          => 'cloudflare_deploy_hook',
				'type'          => 'url',
				'placeholder'   => 'The Cloudflare Deploy Hook',
				'default_value' => '',
				'required'      => 1,
			],
			[
				'key'           => 'field_second_tab',
				'label'         => 'Export Settings',
				'name'          => 'export-settings',
				'type'          => 'tab',
			],
			[
				'key'           => 'field_posts_per_page',
				'label'         => 'Posts per page',
				'name'          => 'posts-per-page',
				'type'          => 'number',
				'placeholder'   => 'Posts per page',
				'default_value' => 10,
			],
		],
		'location' => [
			[
				[
					'param'    => 'options_page',
					'operator' => '==',
					'value'    => 'sync-settings',
				]
			]
		]
	]);

}