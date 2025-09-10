<?php

namespace FreeStockImages\Core;

use FreeStockImages\Admin\SettingsPage;
use FreeStockImages\API\Unsplash;
use FreeStockImages\API\Pixabay;
use FreeStockImages\API\Pexels;
use FreeStockImages\Services\Importer;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin {
    /**
     * @var Plugin|null
     */
    private static $instance = null;

    const VERSION = '0.2.0';

    /** @var SettingsPage */
    private $settings_page;

    /**
     * Get singleton instance
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor
     */
    private function __construct() {
        // reserved
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Settings page object
        $this->settings_page = new SettingsPage();

        // Menus
        add_action('admin_menu', [$this, 'register_menus']);

        // Assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX endpoints for authenticated users (admin)
        add_action('wp_ajax_fsi_search', [$this, 'ajax_search']);
        add_action('wp_ajax_fsi_import', [$this, 'ajax_import']);
    }

    /**
     * Register Settings and Media submenu
     */
    public function register_menus() {
        // Settings -> Free Stock Images
        add_options_page(
            esc_html__('Free Stock Images', 'free-stock-images'),
            esc_html__('Free Stock Images', 'free-stock-images'),
            'manage_options',
            'fsi-settings',
            [$this, 'render_settings_page']
        );

        // Media -> Free Stock Images
        add_submenu_page(
            'upload.php',
            esc_html__('Free Stock Images', 'free-stock-images'),
            esc_html__('Free Stock Images', 'free-stock-images'),
            'upload_files',
            'fsi-media-page',
            [$this, 'render_media_page']
        );
    }

    /**
     * Enqueue admin assets (modal + media page)
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Always enqueue on admin pages where media modal may appear, but you could restrict.
        wp_enqueue_style('fsi-admin-style', FSI_PLUGIN_DIR . 'assets/css/styles.css', [], self::VERSION);
        wp_enqueue_script('fsi-modal', FSI_PLUGIN_DIR . 'assets/js/modal.js', ['jquery'], self::VERSION, true);

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

    /**
     * Render settings page by delegating to SettingsPage (admin > settings > free stock images)
     */
    public function render_settings_page() {
        if (! current_user_can('manage_options')) {
            return;
        }
        $this->settings_page->render_page();
    }

    /**
     * Render the standalone media page (admin > media > Fre stock images)
     */
    public function render_media_page() {
        if (! current_user_can('upload_files')) {
            return;
        }
?>
        <div class="wrap">
            <h1><?php //esc_html_e('Free Stock Images', 'free-stock-images'); 
                ?></h1>
            <div id="fsi-standalone-app" class="fsi-standalone">
                <!-- modal.js will render UI here (same structure used for modal tab) -->
                <div class="fsi-ui-root"></div>
            </div>
        </div>
<?php
    }

    /**
     * AJAX handler: search provider
     */
    public function ajax_search() {
        check_ajax_referer('fsi_nonce');

        if (! current_user_can('upload_files')) {
            wp_send_json_error(['message' => 'unauthorized'], 403);
        }

        $query   = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';
        $source  = isset($_POST['source']) ? sanitize_key(wp_unslash($_POST['source'])) : 'unsplash';
        $page    = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $perPage = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;

        if (empty($query)) {
            wp_send_json_success(['images' => [], 'page' => $page]);
        }

        try {
            $provider = $this->get_provider_instance($source);
            if (! $provider) {
                wp_send_json_error(['message' => 'invalid_source'], 400);
            }

            $filters = []; // extend later for orientation, color, etc.

            $images = $provider->search_images($query, $filters, $page, $perPage);

            wp_send_json_success(['images' => $images, 'page' => $page]);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => 'provider_error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX handler: import image by URL (click-to-import)
     */
    public function ajax_import() {
        check_ajax_referer('fsi_nonce');

        if (! current_user_can('upload_files')) {
            wp_send_json_error(['message' => 'unauthorized'], 403);
        }

        $image_url   = isset($_POST['image_url']) ? esc_url_raw(wp_unslash($_POST['image_url'])) : '';
        $title       = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $attribution = isset($_POST['attribution']) ? sanitize_text_field(wp_unslash($_POST['attribution'])) : '';

        if (empty($image_url)) {
            wp_send_json_error(['message' => 'missing_image_url'], 400);
        }

        $importer = new Importer();

        $result = $importer->import_from_url($image_url, $title, $attribution);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'import_failed', 'error' => $result->get_error_message()], 500);
        }

        // Success: $result is attachment ID
        wp_send_json_success(['attachment_id' => $result]);
    }

    /**
     * Map source name to provider instance
     *
     * @param string $source
     * @return \FreeStockImages\API\ProviderInterface|null
     */
    protected function get_provider_instance(string $source) {
        switch ($source) {
            case 'unsplash':
                return new Unsplash();
            case 'pexels':
                return new Pexels();
            case 'pixabay':
            default:
                return new Pixabay();
        }
    }
}
