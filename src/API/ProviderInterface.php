<?php
/**
 * Provider interface defining required methods for all API providers.
 *
 * @package FreeStockImages
 * @since 1.0.0
 */

namespace FreeStockImages\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provider interface defining required methods for all API providers.
 */
interface ProviderInterface {
	/**
	 * Search images
	 *
	 * @param string $query   Search term.
	 * @param array  $filters Optional filters (orientation, color, etc.).
	 * @param int    $page    Page number.
	 * @param int    $per_page Results per page.
	 * @return array          Array of normalized image objects.
	 */
	public function search_images( string $query, array $filters = array(), int $page = 1, int $per_page = 20 ): array;

	/**
	 * Get API key to use (user key or demo key)
	 *
	 * @return string
	 */
	public function get_api_key(): string;
}
