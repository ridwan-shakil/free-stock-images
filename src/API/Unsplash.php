<?php
/**
 * Unsplash API provider implementation.
 *
 * @package FreeStockImages
 * @since 1.0.0
 */

namespace FreeStockImages\API;

use FreeStockImages\Admin\SettingsPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unsplash API provider implementation.
 */
class Unsplash implements ProviderInterface {
	/**
	 * Search images on Unsplash.
	 *
	 * @param string $query Search term.
	 * @param array  $filters Optional filters.
	 * @param int    $page Page number.
	 * @param int    $per_page Results per page.
	 * @return array
	 * @throws \RuntimeException On API errors or invalid responses.
	 */
	public function search_images( string $query, array $filters = array(), int $page = 1, int $per_page = 20 ): array {
		$api_key = $this->get_api_key();
		if ( '' === $api_key ) {
			throw new \RuntimeException( esc_html__( 'Unsplash API key is required.', 'plugmint-stock-images' ) );
		}

		$params = array(
			'query'    => $query,
			'page'     => max( 1, $page ),
			'per_page' => min( 30, max( 1, $per_page ) ),
		);

		if ( ! empty( $filters['orientation'] ) ) {
			$orientation_map = array(
				'landscape' => 'landscape',
				'portrait'  => 'portrait',
				'square'    => 'squarish',
			);
			if ( isset( $orientation_map[ $filters['orientation'] ] ) ) {
				$params['orientation'] = $orientation_map[ $filters['orientation'] ];
			}
		}

		if ( ! empty( $filters['color'] ) ) {
			$params['color'] = sanitize_key( (string) $filters['color'] );
		}

		$url = add_query_arg( $params, 'https://api.unsplash.com/search/photos' );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Client-ID ' . $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( esc_html( $response->get_error_message() ) );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $status_code < 200 || $status_code >= 300 ) {
			throw new \RuntimeException( esc_html( sprintf( 'Unsplash API request failed (%d).', $status_code ) ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['results'] ) || ! is_array( $data['results'] ) ) {
			throw new \RuntimeException( esc_html__( 'Invalid response from Unsplash API.', 'plugmint-stock-images' ) );
		}

		$images = array();
		foreach ( $data['results'] as $item ) {
			if ( empty( $item['id'] ) || empty( $item['urls']['small'] ) || empty( $item['urls']['full'] ) ) {
				continue;
			}

			$user_name = isset( $item['user']['name'] ) ? (string) $item['user']['name'] : '';
			$images[]  = array(
				'id'          => (string) $item['id'],
				'thumbnail'   => esc_url_raw( (string) $item['urls']['small'] ),
				'full'        => esc_url_raw( (string) $item['urls']['full'] ),
				'width'       => isset( $item['width'] ) ? (int) $item['width'] : 0,
				'height'      => isset( $item['height'] ) ? (int) $item['height'] : 0,
				'author'      => $user_name,
				'author_url'  => isset( $item['user']['links']['html'] ) ? esc_url_raw( (string) $item['user']['links']['html'] ) : '',
				'source'      => 'unsplash',
				'title'       => isset( $item['description'] ) && '' !== $item['description'] ? (string) $item['description'] : ( isset( $item['alt_description'] ) ? (string) $item['alt_description'] : '' ),
				'attribution' => $user_name ? sprintf( 'Photo by %s on Unsplash', $user_name ) : 'Unsplash',
			);
		}

		return $images;
	}

	/**
	 * Return user key only. Unsplash has no fallback key policy.
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return trim( (string) get_option( SettingsPage::OPTION_UNSPLASH, '' ) );
	}
}
