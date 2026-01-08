<?php
/**
 * Plugin Name: Free Stock Images
 * Description: Search and import free stock images (Unsplash, Pixabay, Pexels) directly from the Media modal or Media → Free Stock Images.
 * Version:     1.0.0
 * Author:      MD.Ridwan
 * Text Domain: free-stock-images
 * Domain Path: /languages
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @package FreeStockImages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants.
define( 'FSI_PLUGIN_VERSION', '1.0.0' );
define( 'FSI_PLUGIN_FILE', __FILE__ );
define( 'FSI_PLUGIN_DIR', plugin_dir_url( __FILE__ ) );

// Prefer Composer autoload, but fall back to a simple PSR-4 loader if not present.
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
} else {
	// Simple PSR-4 fallback autoloader for the FreeStockImages namespace
	spl_autoload_register(
		function ( $class ) {
			$prefix   = 'FreeStockImages\\';
			$base_dir = __DIR__ . '/src/';

			$len = strlen( $prefix );
			if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				// not our namespace
				return;
			}

			$relative_class = substr( $class, $len );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
	);
}

// Boot plugin
add_action(
	'plugins_loaded',
	function () {
		if ( class_exists( '\FreeStockImages\Core\Plugin' ) ) {
			\FreeStockImages\Core\Plugin::get_instance();
		}
	}
);
