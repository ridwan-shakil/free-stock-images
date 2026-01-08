<?php
/**
 * This page is responsible for loading and enqueuing scripts and styles (CSS/JS)
 * for the Free Stock Images plugin.
 * 
 * @package FreeStockImages\Core
 * @since 1.0.0
 * @author MD.Ridwan <ridwansweb@email.com>
 */

namespace FreeStockImages;

if (! defined('ABSPATH')) {
	exit;
}
/**
 * Class to manage enqueuing of scripts and styles for the Free Stock Images plugin.
 */
class Assets {
	/**
	 * Initialize asset enqueuing
	 */
	public function init() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
	}

    /**
     * Enqueue admin assets (modal + media page)
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Always enqueue on admin pages where media modal may appear, but you could restrict.
        wp_enqueue_style('fsi-admin-style', FSI_PLUGIN_DIR . 'assets/css/styles.css', [], FSI_PLUGIN_VERSION);
        wp_enqueue_script('fsi-modal', FSI_PLUGIN_DIR . 'assets/js/modal.js', ['jquery'], FSI_PLUGIN_VERSION, true);

        // Localize data for JS
        wp_localize_script(
            'fsi-modal',
            'fsi_ajax',
            [
                'ajax_url'  =>  admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('fsi_nonce'),
                'per_page'  => 20,
                'sources'   => [
                    'pixabay'   => 'pixabay',
                    'pexels'    => 'pexels',
                    'unsplash'  => 'unsplash'
                ]
            ]
        );
    }
		
}	