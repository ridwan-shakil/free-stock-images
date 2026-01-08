<?php
/**
 * This page is responsible for ajax handaling
 */

namespace FreeStockImages\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AjAX handler class for Free Stock Images plugin.
 */
class Free_stock_image_Ajax {
	/**
	 * Initialize AJAX actions
	 */
	public function init() {
		 // AJAX endpoints for authenticated users (admin)
        add_action('wp_ajax_fsi_search', [$this, 'ajax_search']);
        add_action('wp_ajax_fsi_import', [$this, 'ajax_import']);
	}


    /**
     * AJAX handler: search provider
     */
    public function ajax_search() {
        check_ajax_referer('fsi_nonce');

        if (! current_user_can('upload_files')) {
            wp_send_json_error(['message' => 'unauthorized'], 403);
        }

        $query   = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';
        $source  = isset($_POST['source']) ? sanitize_key(wp_unslash($_POST['source'])) : 'unsplash';
        $page    = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $perPage = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;

        if (empty($query)) {
            wp_send_json_success(['images' => [], 'page' => $page]);
        }

        try {
            $provider = $this->get_provider_instance($source);
            if (! $provider) {
                wp_send_json_error(['message' => 'invalid_source'], 400);
            }

            $filters = []; // extend later for orientation, color, etc.

            $images = $provider->search_images($query, $filters, $page, $perPage);

            wp_send_json_success(['images' => $images, 'page' => $page]);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => 'provider_error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX handler: import image by URL (click-to-import)
     */
    public function ajax_import() {
        check_ajax_referer('fsi_nonce');

        if (! current_user_can('upload_files')) {
            wp_send_json_error(['message' => 'unauthorized'], 403);
        }

        $image_url   = isset($_POST['image_url']) ? esc_url_raw(wp_unslash($_POST['image_url'])) : '';
        $title       = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $attribution = isset($_POST['attribution']) ? sanitize_text_field(wp_unslash($_POST['attribution'])) : '';

        if (empty($image_url)) {
            wp_send_json_error(['message' => 'missing_image_url'], 400);
        }

        $importer = new Importer();

        $result = $importer->import_from_url($image_url, $title, $attribution);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'import_failed', 'error' => $result->get_error_message()], 500);
        }

        // Success: $result is attachment ID
        wp_send_json_success(['attachment_id' => $result]);
    }



}