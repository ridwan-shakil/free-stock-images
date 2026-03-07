<?php
/**
 * Implements the Pexels API provider.
 *
 * @package PlugmintStockImages
 * @since 1.0.0
 */

namespace PlugmintStockImages\API;

use PlugmintStockImages\Admin\SettingsPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements the Pexels API provider.
 */
class Pexels implements ProviderInterface {
	/**
	 * Pexels fallback demo key for mixed key policy.
	 *
	 * @var string
	 */
	private $demo_key = 'iyHCPNGUtD3m5G2mIQ6oSbg6p6FkZcMOTwKSbHvLQJfY7V2UIOdNV4Fd';

	/**
	 * Searches images on Pexels.
	 *
	 * @param string $query Search term.
	 * @param array  $filters Optional filters.
	 * @param int    $page Page number.
	 * @param int    $per_page Results per page.
	 * @throws \RuntimeException On API errors or invalid responses.
	 * @return array
	 */
	public function search_images( string $query, array $filters = array(), int $page = 1, int $per_page = 20 ): array {
		$api_key = $this->get_api_key();
		if ( '' === $api_key ) {
			throw new \RuntimeException( esc_html__( 'Pexels API key is missing.', 'plugmint-stock-images' ) );
		}

		$params = array(
			'query'    => $query,
			'page'     => max( 1, $page ),
			'per_page' => min( 80, max( 1, $per_page ) ),
		);

		if ( ! empty( $filters['orientation'] ) ) {
			$orientation_map = array(
				'landscape' => 'landscape',
				'portrait'  => 'portrait',
				'square'    => 'square',
			);
			if ( isset( $orientation_map[ $filters['orientation'] ] ) ) {
				$params['orientation'] = $orientation_map[ $filters['orientation'] ];
			}
		}

		$url = add_query_arg( $params, 'https://api.pexels.com/v1/search' );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( esc_html( $response->get_error_message() ) );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $status_code < 200 || $status_code >= 300 ) {
			throw new \RuntimeException( esc_html( sprintf( 'Pexels API request failed (%d).', $status_code ) ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			throw new \RuntimeException( esc_html__( 'Invalid response from Pexels API.', 'plugmint-stock-images' ) );
		}

		if ( empty( $data['photos'] ) || ! is_array( $data['photos'] ) ) {
			return array();
		}

		$images = array();
		foreach ( $data['photos'] as $item ) {
			if ( empty( $item['id'] ) || empty( $item['src']['medium'] ) || empty( $item['src']['original'] ) ) {
				continue;
			}

			$photographer = isset( $item['photographer'] ) ? (string) $item['photographer'] : '';
			$images[]     = array(
				'id'          => (string) $item['id'],
				'thumbnail'   => esc_url_raw( (string) $item['src']['medium'] ),
				'full'        => esc_url_raw( (string) $item['src']['original'] ),
				'width'       => isset( $item['width'] ) ? (int) $item['width'] : 0,
				'height'      => isset( $item['height'] ) ? (int) $item['height'] : 0,
				'author'      => $photographer,
				'author_url'  => isset( $item['photographer_url'] ) ? esc_url_raw( (string) $item['photographer_url'] ) : '',
				'source'      => 'pexels',
				'title'       => '',
				'attribution' => $photographer ? sprintf( 'Photo by %s on Pexels', $photographer ) : 'Pexels',
			);
		}

		return $images;
	}

	/**
	 * Gets the API key to use for requests. Respects the mixed key policy by returning the user key if set, or the demo key otherwise.
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		$user_key = trim( (string) get_option( SettingsPage::OPTION_PEXELS, '' ) );
		return '' !== $user_key ? $user_key : $this->demo_key;
	}
}
