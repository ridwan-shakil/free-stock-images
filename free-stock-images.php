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
			\FreeStockImages\Core\Plugin::get_instance()->init();
		}
	}
);





// =====================  test code ===================

// // add the tab
// add_filter('media_upload_tabs', 'my_upload_tab');
// function my_upload_tab($tabs) {
// $tabs['mytabname'] = "My Tab Name";
// return $tabs;
// }

// // call the new tab with wp_iframe
// add_action('media_upload_mytabname', 'add_my_new_form');
// function add_my_new_form() {
// wp_iframe('my_new_form');
// }

// // the tab content
// function my_new_form() {
// echo media_upload_header(); // This function is used for print media uploader headers etc.
// echo '<p>Example HTML content goes here.</p>';
// }
