<?php
namespace Mcms\Api\RestRouteHandlers\Overrides;
use Cocur\Slugify\Slugify;

function add_related_languages_to_item($response, $post, $request) {
	$type = apply_filters( 'wpml_element_type', get_post_type( $post->ID ) );
	$trid = apply_filters( 'wpml_element_trid', false, $post->ID, $type );
	$translations = apply_filters( 'wpml_get_element_translations', array(), $trid, $type );
	$t = [];
	$requestLang = $request->get_param('lang');

	foreach ( $translations as $lang => $translation ) {
		/**
		 *     [translation_id] => 94
		[language_code] => enr
		[element_id] => 667
		[source_language_code] =>
		[element_type] => post_post
		[original] => 1
		[post_title] => Test post
		[post_status] => publish
		 */
		if ($requestLang === $lang) {
			continue;
		}

		$t[] = [
			'lang' => $lang,
			'id' => $post->ID,
			'status' => $translation->post_status,
			'original' => $translation->original == '1' ? true : false,
			'element_id' => $translation->element_id,
			'title' => $translation->post_title,
			'source_language_code' => $translation->source_language_code,
			'element_type' => $translation->element_type,
			'translation_id' => $translation->translation_id,
		];
	}

	$response->data['translations'] = $t;

	return $response;
}

function add_cloudinary_url_to_media_query($response, $post, $request) {
	// Check if the request is for the /media endpoint
	if ($request->get_route() === '/wp/v2/media') {
		// get acf for this object
		$response->data['acf'] = get_fields($response->data['id']);
		$media_metadata = get_post_meta($response->data['id'], '_cloudinary', true);
		if (!empty($media_metadata) && isset($media_metadata['_cloudinary_url'])) {
			$response->data['cloudinary'] = [
				'url' => $media_metadata['_cloudinary_url'],
				'public_id' => $media_metadata['_public_id'],
			];
		}
	}

	if ( preg_match( '#^/wp/v2/media/\d+$#', $request->get_route() ) ) {

		$media_metadata = get_post_meta($post->ID, '_cloudinary', true);
		$response->data['acf'] = get_fields($response->data['id']);
		if (!empty($media_metadata) && $media_metadata['_cloudinary_url']) {
			$response->data['cloudinary'] = [
				'url' => $media_metadata['_cloudinary_url'],
				'public_id' => $media_metadata['_public_id'],
			];
		}
	}

	return $response;
}

function sanitize_post_slug($data, $postarr) {
	$slugify = new Slugify(['lowercase' => true]);
	$slugify->activateRuleSet('greek');

	if (empty($data['post_name']) || strpos($data['post_name'], 'auto-draft') === 0) {
		$data['post_name'] = $slugify->slugify($data['post_title']);
	}


	return $data;
}


function sanitize_term_slug($data, $term_id, $taxonomy) {
	$slugify = new Slugify(['lowercase' => true]);
	$slugify->activateRuleSet('greek');
	$slugify->activateRuleSet('russian');
	if (empty($data['slug'])) {
		$data['slug'] = $slugify->slugify($data['name']);
	}

	return $data;
}
