<?php


namespace FreeStockImages\Core;

use FreeStockImages\Assets;
use FreeStockImages\Free_stock_image_Ajax;
use FreeStockImages\Admin\SettingsPage;
use FreeStockImages\Admin\MediaTab;
use FreeStockImages\Admin\MediaPage;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Loader class lods all the core component classes.
 * responsible for :.
 */
class Loader {


    /** @var SettingsPage */
    private $settings_page;

    /** @var MediaPage */
    private $media_tab;
    private $media_page;


	/**
	 * Holds the Admin_Notes_Activation instance.
	 *
	 * Handles plugin activation and deactivation hooks.
	 *
	 * @var Admin_Notes_Activation
	 */
	protected $activation;

	/**
	 * Holds the Admin_Notes_Assets instance.
	 *
	 * Manages the enqueuing of scripts and styles (CSS/JS).
	 *
	 * @var Admin_Notes_Assets
	 */
	protected $assets;

	/**
	 * Holds the Admin_Notes_Ajax instance.
	 *
	 * Handles all AJAX-related operations and endpoints.
	 *
	 * @var Free_stock_image_Ajax
	 */
	protected $ajax;

	/**
	 * Initializes all core component classes.
	 *
	 * The constructor instantiates all dependent functional classes
	 * and assigns them to their respective properties.
	 * * @return void
	 */
	public function __construct() {

		 // Settings page object
        $this->settings_page = new SettingsPage();
        // Media tab + page
        $this->media_tab = new MediaTab();
        $this->media_page = new MediaPage();
		$this->ajax = new Free_stock_image_Ajax();
		$this->ajax->init();
		$this->assets = new Assets();
		$this->assets->init();

		add_action ('admin_menu', [ $this, 'register_menus' ]);
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


}