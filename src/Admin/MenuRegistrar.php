<?php
/**
 * Registers plugin admin menus and renders delegated pages.
 *
 * @package FreeStockImages
 * @since 1.0.0
 */

namespace FreeStockImages\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers plugin admin menus and renders delegated pages.
 */
class MenuRegistrar {
	/**
	 * Settings page renderer.
	 *
	 * @var SettingsPage
	 */
	private $settings_page;

	/**
	 * Media page renderer.
	 *
	 * @var MediaPage
	 */
	private $media_page;

	/**
	 * Manage capability required to access the settings page.
	 * Typically 'manage_options'.
	 *
	 * @var string
	 */
	private $manage_cap;

	/**
	 * Upload capability required to access the media page.
	 * Typically 'upload_files'.
	 *
	 * @var string
	 */
	private $upload_cap;

	/**
	 * Constructor.
	 *
	 * @param SettingsPage $settings_page Settings page renderer.
	 * @param MediaPage    $media_page Media page renderer.
	 * @param string       $manage_cap Manage capability.
	 * @param string       $upload_cap Upload capability.
	 */
	public function __construct( SettingsPage $settings_page, MediaPage $media_page, $manage_cap, $upload_cap ) {
		$this->settings_page = $settings_page;
		$this->media_page    = $media_page;
		$this->manage_cap    = $manage_cap;
		$this->upload_cap    = $upload_cap;
	}

	/**
	 * Registers the admin menus. Should be called from the plugin core during initialization.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
	}

	/**
	 * Registers the admin menus and their callbacks.
	 * This method is hooked into 'admin_menu' action.
	 *
	 * @return void
	 */
	public function register_menus() {
		add_options_page(
			esc_html__( 'Free Stock Images', 'plugmint-stock-images' ),
			esc_html__( 'Free Stock Images', 'plugmint-stock-images' ),
			$this->manage_cap,
			'fsimgs-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'upload.php',
			esc_html__( 'Free Stock Images', 'plugmint-stock-images' ),
			esc_html__( 'Free Stock Images', 'plugmint-stock-images' ),
			$this->upload_cap,
			'fsimgs-media-page',
			array( $this, 'render_media_page' )
		);
	}

	/**
	 * Renders the settings page. This method is called as a callback when the settings submenu is accessed.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( $this->manage_cap ) ) {
			return;
		}

		$this->settings_page->render_page();
	}

	/**
	 * Renders the media page. This method is called as a callback when the media submenu is accessed.
	 *
	 * @return void
	 */
	public function render_media_page() {
		if ( ! current_user_can( $this->upload_cap ) ) {
			return;
		}

		$this->media_page->render_page();
	}
}
