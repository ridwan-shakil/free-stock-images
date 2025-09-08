<?php

namespace FreeStockImages\Core;

use FreeStockImages\Admin\SettingsPage;

if (! defined('ABSPATH')) {
    exit;
}

/*
 * Main plugin class (singleton)
 */
class Plugin {
    /**
     * Settings page instance
     * @var SettingsPage
     */
    private $settings_page;

    /**
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Plugin version
     * @var string
     */
    private $version = '0.1.0';

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
     * Private constructor (singleton)
     */
    private function __construct() {
        // reserved for future use
    }

    /**
     * Initialize hooks — called from bootstrap
     */
    public function init() {
        // Admin menu + media submenu
        add_action('admin_menu', [$this, 'register_menus']);

        // Enqueue admin assets (modal, settings page, media page)
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX endpoints (placeholders for now)
        add_action('wp_ajax_fsi_search', [$this, 'ajax_search']);
        add_action('wp_ajax_fsi_import', [$this, 'ajax_import']);

        // TODO: load textdomain, other early init tasks
    }

    /**
     * Register settings menu and submenu under Media
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
        /**
         * Initialize settings page
         */
        $this->settings_page = new SettingsPage();
        // Note: settings fields are registered in SettingsPage constructor


        // Media -> Free Stock Images (submenu)
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
     * Enqueue admin CSS/JS
     *
     * For Phase 1 we enqueue placeholders so you can confirm assets load.
     */
    public function enqueue_admin_assets($hook_suffix) {
        // You can later restrict by $hook_suffix for performance.
        wp_enqueue_style('fsi-admin-style', FSI_PLUGIN_DIR . 'assets/css/styles.css', [], $this->version);
        wp_enqueue_script('fsi-modal', FSI_PLUGIN_DIR . 'assets/js/modal.js', ['jquery'], $this->version, true);

        // Provide AJAX URL and nonce to JS
        wp_localize_script(
            'fsi-modal',
            'fsi_ajax',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('fsi_nonce'),
            ]
        );
    }

    /**
     * Render settings page (callback for add_options_page)
     */
    public function render_settings_page() {
        $this->settings_page->render_page();
    }


    /**
     * Media page (submenu) — placeholder content for Phase 1
     */
    public function render_media_page() {
        if (! current_user_can('upload_files')) {
            return;
        }
?>
<div class="wrap">
    <h1><?php esc_html_e('Free Stock Images — Media Page (placeholder)', 'free-stock-images'); ?></h1>
    <div id="fsi-app">
        <p><?php esc_html_e('UI will be rendered here (same as the media modal tab).', 'free-stock-images'); ?></p>
    </div>
</div>
<?php
    }

    /**
     * AJAX handler: search (placeholder)
     */
    public function ajax_search() {
        // Basic security check
        check_ajax_referer('fsi_nonce');

        $query = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';

        // For Phase 1, just return a small dummy payload so JS can confirm AJAX works.
        wp_send_json_success([
            'message' => 'Search endpoint working (Phase 1 placeholder)',
            'query'   => $query,
        ]);
    }

    /**
     * AJAX handler: import (placeholder)
     */
    public function ajax_import() {
        check_ajax_referer('fsi_nonce');

        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';

        // Phase 1: return placeholder response
        wp_send_json_success([
            'message' => 'Import endpoint working (Phase 1 placeholder)',
            'url'     => $url,
        ]);
    }
}