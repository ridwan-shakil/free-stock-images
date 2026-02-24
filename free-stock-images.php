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

// Plugin constants.
define( 'FSI_PLUGIN_FILE', __FILE__ );
define( 'FSI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FSI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
// Backward compatibility for existing code paths.
define( 'FSI_PLUGIN_DIR', FSI_PLUGIN_URL );

// Simple PSR-4 fallback autoloader for the FreeStockImages namespace.
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'FreeStockImages\\';
		$base   = __DIR__ . '/src/';

		$length = strlen( $prefix );
		if ( strncmp( $prefix, $class, $length ) !== 0 ) {
			return;
		}

		$relative = substr( $class, $length );
		$file     = $base . str_replace( '\\', '/', $relative ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

// Prefer Composer autoload when available.
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
}

// Boot plugin.
add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( '\FreeStockImages\Core\Plugin' ) ) {
			\FreeStockImages\Core\Plugin::get_instance()->init();
		}
	}
);
