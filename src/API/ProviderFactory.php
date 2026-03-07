<?php
/**
 * Provider factory for source -> provider instance mapping.
 *
 * @package FreeStockImages
 * @since 1.0.0
 */

namespace FreeStockImages\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provider factory for source -> provider instance mapping.
 */
class ProviderFactory {
	/**
	 * Creates a provider instance based on the source key.
	 *
	 * @param string $source Provider key.
	 * @return ProviderInterface|null
	 */
	public function make( $source ) {
		switch ( $source ) {
			case 'unsplash':
				return new Unsplash();
			case 'pexels':
				return new Pexels();
			case 'pixabay':
				return new Pixabay();
			default:
				return null;
		}
	}
}
