<?php

namespace FreeStockImages\Admin;

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
	 * For now, asset enqueuing is handled by MediaTab (or Plugin). If you'd like to enqueue
	 * only for this page, add an admin_enqueue_scripts handler here and check the $hook.
	 */
	public function __construct() {
		// Optional: add_action('admin_enqueue_scripts', [ $this, 'enqueue_assets' ]);
	}

	/**
	 * Render the standalone page. The plugin core should call$this method
	 * when rendering the submenu page registered under upload.php.
	 */
	public function render_page() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Free Stock Images', 'free-stock-images' ); ?></h1>

			<div id="fsi-standalone-app" class="fsi-standalone">
				<div class="fsi-ui-root"></div>
			</div>

			<p class="description" style="margin-top:18px;">
				<?php esc_html_e( 'Search and import free stock images from Unsplash, Pixabay, and Pexels. Click any image to import it into the Media Library.', 'free-stock-images' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Optional: enqueue assets only for this submenu page (hooked by Plugin with $hook suffix)
	 *
	 * public function enqueue_assets($hook) {
	 *     if ( $hook !== 'upload.php' ) return;
	 *     // enqueue assets...
	 * }
	 */
}
