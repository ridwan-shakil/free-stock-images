<?php

namespace FreeStockImages\API;

use FreeStockImages\Admin\SettingsPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pexels implements ProviderInterface {
	/**
	 * Pexels fallback demo key for mixed key policy.
	 *
	 * @var string
	 */
	private $demo_key = 'iyHCPNGUtD3m5G2mIQ6oSbg6p6FkZcMOTwKSbHvLQJfY7V2UIOdNV4Fd';

	/**
	 * @param string $query Search term.
	 * @param array  $filters Optional filters.
	 * @param int    $page Page number.
	 * @param int    $perPage Results per page.
	 * @return array
	 */
	public function search_images( string $query, array $filters = array(), int $page = 1, int $perPage = 20 ): array {
		$api_key = $this->get_api_key();
		if ( '' === $api_key ) {
			throw new \RuntimeException( __( 'Pexels API key is missing.', 'free-stock-images' ) );
		}

		$url = add_query_arg(
			array(
				'query'    => $query,
				'page'     => max( 1, $page ),
				'per_page' => min( 80, max( 1, $perPage ) ),
			),
			'https://api.pexels.com/v1/search'
		);

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
			throw new \RuntimeException( $response->get_error_message() );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $status_code < 200 || $status_code >= 300 ) {
			throw new \RuntimeException( sprintf( 'Pexels API request failed (%d).', $status_code ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			throw new \RuntimeException( __( 'Invalid response from Pexels API.', 'free-stock-images' ) );
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
	 * @return string
	 */
	public function get_api_key(): string {
		$user_key = trim( (string) get_option( SettingsPage::OPTION_PEXELS, '' ) );
		return '' !== $user_key ? $user_key : $this->demo_key;
	}
}
