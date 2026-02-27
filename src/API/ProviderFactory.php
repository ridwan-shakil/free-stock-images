<?php

namespace FreeStockImages\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provider factory for source -> provider instance mapping.
 */
class ProviderFactory {
	/**
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
