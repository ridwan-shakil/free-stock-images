<?php
/**
 * Service for importing remote images into WordPress Media Library.
 *
 * @package FreeStockImages
 * @since 1.0.0
 */

namespace FreeStockImages\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Downloads a remote image and inserts it into WordPress Media Library.
 */
class Importer {
	/**
	 * Imports an image from a remote URL into the WordPress Media Library.
	 *
	 * @param string $url Remote image URL.
	 * @param array  $args Optional image metadata.
	 * @return int|\WP_Error
	 */
	public function import_from_url( string $url, array $args = array() ) {
		if ( '' === $url ) {
			return new \WP_Error( 'missing_url', __( 'No image URL provided.', 'plugmint-stock-images' ) );
		}

		$tmp_file = download_url( $url, 15 );
		if ( is_wp_error( $tmp_file ) ) {
			return new \WP_Error( 'download_failed', $tmp_file->get_error_message() );
		}

		$path     = (string) wp_parse_url( $url, PHP_URL_PATH );
		$basename = basename( $path );
		if ( '' === $basename || '.' === $basename || '/' === $basename ) {
			$basename = 'fsi-' . wp_generate_password( 12, false ) . '.jpg';
		}

		$file = array(
			'name'     => sanitize_file_name( $basename ),
			'tmp_name' => $tmp_file,
		);

		$results = wp_handle_sideload(
			$file,
			array(
				'test_form' => false,
			)
		);

		if ( ! empty( $results['error'] ) ) {
			@unlink( $tmp_file );
			return new \WP_Error( 'sideload_error', (string) $results['error'] );
		}

		$file_path = isset( $results['file'] ) ? (string) $results['file'] : '';
		$file_type = isset( $results['type'] ) ? (string) $results['type'] : '';

		if ( '' === $file_path ) {
			return new \WP_Error( 'missing_file_path', __( 'Could not determine uploaded file path.', 'plugmint-stock-images' ) );
		}

		$title = isset( $args['title'] ) ? sanitize_text_field( (string) $args['title'] ) : '';
		if ( '' === $title ) {
			$title = sanitize_file_name( pathinfo( $file_path, PATHINFO_FILENAME ) );
		}

		$attachment = array(
			'post_mime_type' => $file_type,
			'post_title'     => $title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $file_path );
		if ( is_wp_error( $attach_id ) || ! $attach_id ) {
			return new \WP_Error( 'attachment_insert_failed', __( 'Failed to insert attachment.', 'plugmint-stock-images' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attach_id, $file_path );
		if ( is_wp_error( $metadata ) ) {
			wp_update_attachment_metadata( $attach_id, array() );
		} else {
			wp_update_attachment_metadata( $attach_id, $metadata );
		}

		if ( ! empty( $args['attribution'] ) ) {
			update_post_meta( $attach_id, '_fsi_attribution', sanitize_text_field( (string) $args['attribution'] ) );
		}
		if ( ! empty( $args['source'] ) ) {
			update_post_meta( $attach_id, '_fsi_source', sanitize_key( (string) $args['source'] ) );
		}
		if ( ! empty( $args['remote_id'] ) ) {
			update_post_meta( $attach_id, '_fsi_remote_id', sanitize_text_field( (string) $args['remote_id'] ) );
		}

		return (int) $attach_id;
	}
}
