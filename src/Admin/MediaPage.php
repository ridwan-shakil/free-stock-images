<?php
/**
 * Renders the standalone Media -> Free Stock Images admin page.
 *
 * @package PlugmintStockImages
 * @since 1.0.0
 */

namespace PlugmintStockImages\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MediaPage
 *
 * Renders the standalone Media -> Free Stock Images admin page.
 * Keeps page rendering separated from Plugin core for clarity.
 */
class MediaPage {

	/**
	 * Hook into constructor if you need to enqueue assets specifically for this page.
	 * By default, asset enqueuing is centralized in Core\Plugin.
	 */
	public function __construct() {
	}

	/**
	 * Render the standalone page. The plugin core should call this method
	 * when rendering the submenu page registered under upload.php.
	 */
	public function render_page() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Free Stock Images', 'plugmint-stock-images' ); ?></h1>

			<div id="fsimgs-standalone-app" class="fsimgs-standalone">
				<div class="fsimgs-ui-root"></div>
			</div>

			<p class="description" style="margin-top:18px;">
				<?php esc_html_e( 'Search and import free stock images from Unsplash, Pixabay, and Pexels. Click any image to import it into the Media Library.', 'plugmint-stock-images' ); ?>
			</p>
		</div>
		<?php
	}
}
