<?php

namespace FreeStockImages\Services;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Importer service
 * Downloads a remote image and inserts it into WP Media Library.
 */
class Importer {

    /**
     * Import a remote image URL into the Media Library.
     *
     * @param string $url         Remote image URL
     * @param string $title       Optional attachment title
     * @param string $attribution Optional attribution string to store in attachment meta
     * @return int|\WP_Error      Attachment ID on success, WP_Error on failure
     */
    public function import_from_url(string $url, string $title = '', string $attribution = '') {
        if (empty($url)) {
            return new \WP_Error('missing_url', __('No URL provided', 'free-stock-images'));
        }

        // Download to temp file
        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            return new \WP_Error('download_failed', $tmp->get_error_message());
        }

        // Prepare an array similar to a PHP file upload.
        $file = [
            'name'     => basename(parse_url($url, PHP_URL_PATH)),
            'tmp_name' => $tmp,
        ];

        // Allow sideload and skip form test.
        $overrides = [
            'test_form' => false,
        ];

        // Move the temporary file into the uploads directory.
        $results = wp_handle_sideload($file, $overrides);

        if (! empty($results['error'])) {
            // Clean up temp file
            @unlink($tmp);
            return new \WP_Error('sideload_error', $results['error']);
        }

        $file_path = $results['file'];
        $file_type = $results['type'];

        // Prepare attachment data
        $attachment = [
            'post_mime_type' => $file_type,
            'post_title'     => ! empty($title) ? sanitize_text_field($title) : sanitize_file_name(pathinfo($file_path, PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        // Insert attachment into the Media Library
        $attach_id = wp_insert_attachment($attachment, $file_path);

        if (is_wp_error($attach_id) || ! $attach_id) {
            return new \WP_Error('attachment_insert_failed', __('Failed to insert attachment', 'free-stock-images'));
        }

        // Generate metadata (requires image.php)
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);

        if (is_wp_error($attach_data)) {
            // still update meta with empty array
            wp_update_attachment_metadata($attach_id, []);
        } else {
            wp_update_attachment_metadata($attach_id, $attach_data);
        }

        // Save attribution into attachment meta if provided
        if (! empty($attribution)) {
            update_post_meta($attach_id, '_fsi_attribution', sanitize_text_field($attribution));
        }

        return $attach_id;
    }
}
