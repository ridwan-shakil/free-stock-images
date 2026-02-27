<?php

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
