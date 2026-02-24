<?php

namespace FreeStockImages\API;

use FreeStockImages\Admin\SettingsPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pixabay implements ProviderInterface {
	/**
	 * Pixabay fallback demo key for mixed key policy.
	 *
	 * @var string
	 */
	private $demo_key = '52201740-f9a6eab0da31331dc8be46c99';

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
			throw new \RuntimeException( __( 'Pixabay API key is missing.', 'free-stock-images' ) );
		}

		$params = array(
			'key'        => $api_key,
			'q'          => $query,
			'page'       => max( 1, $page ),
			'per_page'   => min( 50, max( 1, $perPage ) ),
			'image_type' => 'photo',
		);

		if ( ! empty( $filters['orientation'] ) ) {
			$orientation_map = array(
				'landscape' => 'horizontal',
				'portrait'  => 'vertical',
			);
			if ( isset( $orientation_map[ $filters['orientation'] ] ) ) {
				$params['orientation'] = $orientation_map[ $filters['orientation'] ];
			}
		}

		if ( ! empty( $filters['color'] ) ) {
			$params['colors'] = sanitize_key( (string) $filters['color'] );
		}

		$url = add_query_arg( $params, 'https://pixabay.com/api/' );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( $response->get_error_message() );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $status_code < 200 || $status_code >= 300 ) {
			throw new \RuntimeException( sprintf( 'Pixabay API request failed (%d).', $status_code ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			throw new \RuntimeException( __( 'Invalid response from Pixabay API.', 'free-stock-images' ) );
		}

		if ( ! empty( $data['error'] ) ) {
			throw new \RuntimeException( (string) $data['error'] );
		}

		if ( empty( $data['hits'] ) || ! is_array( $data['hits'] ) ) {
			return array();
		}

		$images = array();
		foreach ( $data['hits'] as $item ) {
			if ( empty( $item['id'] ) || empty( $item['previewURL'] ) || empty( $item['largeImageURL'] ) ) {
				continue;
			}

			$user_name = isset( $item['user'] ) ? (string) $item['user'] : '';
			$images[]  = array(
				'id'          => (string) $item['id'],
				'thumbnail'   => esc_url_raw( (string) $item['previewURL'] ),
				'full'        => esc_url_raw( (string) $item['largeImageURL'] ),
				'width'       => isset( $item['imageWidth'] ) ? (int) $item['imageWidth'] : 0,
				'height'      => isset( $item['imageHeight'] ) ? (int) $item['imageHeight'] : 0,
				'author'      => $user_name,
				'author_url'  => '',
				'source'      => 'pixabay',
				'title'       => isset( $item['tags'] ) ? (string) $item['tags'] : '',
				'attribution' => $user_name ? sprintf( 'Photo by %s on Pixabay', $user_name ) : 'Pixabay',
			);
		}

		return $images;
	}

	/**
	 * @return string
	 */
	public function get_api_key(): string {
		$user_key = trim( (string) get_option( SettingsPage::OPTION_PIXABAY, '' ) );
		return '' !== $user_key ? $user_key : $this->demo_key;
	}
}
