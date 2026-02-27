<?php

namespace FreeStockImages\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers plugin admin menus and renders delegated pages.
 */
class MenuRegistrar {
	/**
	 * @var SettingsPage
	 */
	private $settings_page;

	/**
	 * @var MediaPage
	 */
	private $media_page;

	/**
	 * @var string
	 */
	private $manage_cap;

	/**
	 * @var string
	 */
	private $upload_cap;

	/**
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
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
	}

	/**
	 * @return void
	 */
	public function register_menus() {
		add_options_page(
			esc_html__( 'Free Stock Images', 'free-stock-images' ),
			esc_html__( 'Free Stock Images', 'free-stock-images' ),
			$this->manage_cap,
			'fsi-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'upload.php',
			esc_html__( 'Free Stock Images', 'free-stock-images' ),
			esc_html__( 'Free Stock Images', 'free-stock-images' ),
			$this->upload_cap,
			'fsi-media-page',
			array( $this, 'render_media_page' )
		);
	}

	/**
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( $this->manage_cap ) ) {
			return;
		}

		$this->settings_page->render_page();
	}

	/**
	 * @return void
	 */
	public function render_media_page() {
		if ( ! current_user_can( $this->upload_cap ) ) {
			return;
		}

		$this->media_page->render_page();
	}
}
