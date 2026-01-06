<?php

namespace FreeStockImages\Admin;

if (!defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MediaTab
 *
 * Responsible for injecting the media modal template (Underscore template)
 * and ensuring modal assets are available when the WordPress media modal is used.
 */
class MediaTab {

	/**
	 * Constructor: hooks
	 */
	public function __construct() {
		// Print underscore template into the page so modal.js can mount into it.
		add_action( 'print_media_templates', array( $this, 'print_media_template' ) );

		// Ensure assets are available on admin pages where the media modal may be used.
		// You can narrow this to specific $hook_suffix values if you want.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Output an Underscore template used by modal.js to mount the UI inside the media modal.
	 * This template is printed in the admin footer (wp.media templates area).
	 */
	public function print_media_template() {
		?>
		<script type="text/template" id="tmpl-fsi-media-tab">
			<div class="fsi-tab" style="display:none;">
				<div class="fsi-ui-root"></div>
			</div>
		</script>
		<?php
	}

	/**
	 * Enqueue assets required for the media tab.
	 * We keep this light and rely on your existing modal.js and styles.css files.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		// We enqueue for common admin pages where media modal is expected.
		// This avoids loading the assets on unrelated admin pages.
		$allowed_hooks = array(
			'post.php',
			'post-new.php',
			'term.php',
			'edit-tags.php',
			'upload.php',      // Media list.
			'media-new.php',
			'widgets.php',
			'customize.php',
		);

		// Always safe to enqueue on upload.php (media screen) and post screens.
		// If you prefer to always enqueue, remove the conditional.
		if ( ! in_array( $hook_suffix, $allowed_hooks, true ) ) {
			// Still allow the modal to show in some editors that use ajax. If you want to be aggressive,
			// return early here. For now, we enqueue on many hooks to be safe.
			return;
		}

		// Use the plugin URL constant defined in bootstrap
		if ( defined( 'FSI_PLUGIN_DIR' ) ) {
			// CSS
			wp_enqueue_style( 'fsi-admin-style', FSI_PLUGIN_DIR . 'assets/css/styles.css', array(), \FreeStockImages\Core\Plugin::VERSION ?? null );

			// JS
			wp_enqueue_script( 'fsi-modal', FSI_PLUGIN_DIR . 'assets/js/modal.js', array( 'jquery' ), \FreeStockImages\Core\Plugin::VERSION ?? null, true );

			// Localization (keep nonce & ajax_url available).
			wp_localize_script(
				'fsi-modal',
				'fsi_ajax',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'fsi_nonce' ),
					'per_page' => 20,
					'sources'  => array(
						'unsplash' => 'Unsplash',
						'pixabay'  => 'Pixabay',
						'pexels'   => 'Pexels',
					),
				)
			);
		}
	}
}
