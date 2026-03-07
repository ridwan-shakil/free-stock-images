<?php
/**
 * Encapsulates source enablement and source config for UI localization.
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
 * Encapsulates source enablement and source config for UI localization.
 */
class SourcePolicy {
	/**
	 * Determines if a source is enabled based on the plugin settings. Unsplash requires an API key, while Pixabay and Pexels are always enabled.
	 *
	 * @param string $source Provider key.
	 * @return bool
	 */
	public function is_enabled( $source ) {
		switch ( $source ) {
			case 'unsplash':
				return '' !== trim( (string) get_option( SettingsPage::OPTION_UNSPLASH, '' ) );
			case 'pixabay':
			case 'pexels':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Gets the source configuration for all providers, including label and enablement status. This is used for UI localization and conditional display.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_source_config() {
		return array(
			'unsplash' => array(
				'label'   => 'Unsplash',
				'enabled' => $this->is_enabled( 'unsplash' ),
			),
			'pixabay'  => array(
				'label'   => 'Pixabay',
				'enabled' => $this->is_enabled( 'pixabay' ),
			),
			'pexels'   => array(
				'label'   => 'Pexels',
				'enabled' => $this->is_enabled( 'pexels' ),
			),
		);
	}
}
