<?php

namespace FreeStockImages\Core;

use FreeStockImages\Admin\SettingsPage;
use FreeStockImages\Admin\MediaTab;
use FreeStockImages\Admin\MediaPage;
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

    /** @var MediaPage */
    private $media_tab;
    private $media_page;

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
        // Media tab + page
        $this->media_tab = new MediaTab();
        $this->media_page = new MediaPage();

        // Menus
        add_action('admin_menu', [$this, 'register_menus']);

        // Assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

       
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
        $this->media_page->render_page();
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
