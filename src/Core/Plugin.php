<?php

namespace FreeStockImages\Core;

use FreeStockImages\Admin\MediaPage;
use FreeStockImages\Admin\SettingsPage;
use FreeStockImages\API\Pexels;
use FreeStockImages\API\Pixabay;
use FreeStockImages\API\Unsplash;
use FreeStockImages\API\ProviderInterface;
use FreeStockImages\Services\Importer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Final Plugin class.
 */
final class Plugin {
	const VERSION          = '1.1.0';
	const NONCE_ACTION     = 'fsi_nonce';
	const AJAX_SEARCH      = 'fsi_search';
	const AJAX_IMPORT      = 'fsi_import';
	const CAP_MANAGE       = 'manage_options';
	const CAP_UPLOAD_FILES = 'upload_files';

	/**
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * @var SettingsPage
	 */
	private $settings_page;

	/**
	 * @var MediaPage
	 */
	private $media_page;

	/**
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
	}

	/**
	 * Initialize plugin services and hooks.
	 *
	 * @return void
	 */
	public function init() {
		$this->settings_page = new SettingsPage();
		$this->media_page    = new MediaPage();

		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		$this->register_elementor_enqueue_hooks();

		add_action( 'wp_ajax_' . self::AJAX_SEARCH, array( $this, 'ajax_search' ) );
		add_action( 'wp_ajax_' . self::AJAX_IMPORT, array( $this, 'ajax_import' ) );
	}

	/**
	 * Register Elementor script enqueue hooks when Elementor is active.
	 *
	 * @return void
	 */
	private function register_elementor_enqueue_hooks() {
		if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_elementor_assets' ) );
		add_action( 'elementor/preview/enqueue_scripts', array( $this, 'enqueue_elementor_assets' ) );
	}

	/**
	 * @return void
	 */
	public function register_menus() {
		add_options_page(
			esc_html__( 'Free Stock Images', 'free-stock-images' ),
			esc_html__( 'Free Stock Images', 'free-stock-images' ),
			self::CAP_MANAGE,
			'fsi-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'upload.php',
			esc_html__( 'Free Stock Images', 'free-stock-images' ),
			esc_html__( 'Free Stock Images', 'free-stock-images' ),
			self::CAP_UPLOAD_FILES,
			'fsi-media-page',
			array( $this, 'render_media_page' )
		);
	}

	/**
	 * Load admin assets where media frame is used and on plugin media page.
	 *
	 * @param string $hook_suffix Current admin screen hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( ! $this->should_enqueue_assets( $hook_suffix ) ) {
			return;
		}

		$this->enqueue_shared_assets();
	}

	/**
	 * Enqueue scripts/styles for Elementor editor and preview.
	 *
	 * @return void
	 */
	public function enqueue_elementor_assets() {
		$this->enqueue_shared_assets();
	}

	/**
	 * Shared asset registration/localization for all supported admin contexts.
	 *
	 * @return void
	 */
	private function enqueue_shared_assets() {
		wp_enqueue_media();

		wp_enqueue_style(
			'fsi-admin-style',
			FSI_PLUGIN_URL . 'assets/css/styles.css',
			array(),
			self::VERSION
		);

		wp_enqueue_script(
			'fsi-modal',
			FSI_PLUGIN_URL . 'assets/js/modal.js',
			array( 'jquery', 'media-editor', 'media-views' ),
			self::VERSION,
			true
		);

		wp_localize_script(
			'fsi-modal',
			'fsi_ajax',
			$this->get_localized_config()
		);
	}

	/**
	 * Return localized config consumed by assets/js/modal.js.
	 *
	 * @return array<string, mixed>
	 */
	private function get_localized_config() {
		return array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( self::NONCE_ACTION ),
			'searchAction' => self::AJAX_SEARCH,
			'importAction' => self::AJAX_IMPORT,
			'perPage'      => 20,
			'canUpload'    => current_user_can( self::CAP_UPLOAD_FILES ),
			'debug'        => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'sources'      => $this->get_source_config(),
			'i18n'         => array(
				'title'             => __( 'Free Stock Images', 'free-stock-images' ),
				'searchPlaceholder' => __( 'Search free stock images...', 'free-stock-images' ),
				'search'            => __( 'Search', 'free-stock-images' ),
				'loading'           => __( 'Loading...', 'free-stock-images' ),
				'importing'         => __( 'Importing...', 'free-stock-images' ),
				'inserted'          => __( 'Inserted', 'free-stock-images' ),
				'imported'          => __( 'Imported', 'free-stock-images' ),
				'error'             => __( 'Something went wrong.', 'free-stock-images' ),
				'noResults'         => __( 'No images found.', 'free-stock-images' ),
				'needsKey'          => __( 'API key is required for this source.', 'free-stock-images' ),
			),
		);
	}

	/**
	 * @param string $hook_suffix Current admin screen hook.
	 * @return bool
	 */
	private function should_enqueue_assets( $hook_suffix ) {
		unset( $hook_suffix );
		return is_admin();
	}

	/**
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( self::CAP_MANAGE ) ) {
			return;
		}

		$this->settings_page->render_page();
	}

	/**
	 * @return void
	 */
	public function render_media_page() {
		if ( ! current_user_can( self::CAP_UPLOAD_FILES ) ) {
			return;
		}

		$this->media_page->render_page();
	}

	/**
	 * AJAX search endpoint.
	 *
	 * @return void
	 */
	public function ajax_search() {
		check_ajax_referer( self::NONCE_ACTION );

		if ( ! current_user_can( self::CAP_UPLOAD_FILES ) ) {
			wp_send_json_error(
				array(
					'error_code' => 'unauthorized',
					'message'    => __( 'You are not allowed to search images.', 'free-stock-images' ),
					'images'     => array(),
				),
				403
			);
		}

		$query    = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		$source   = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : 'pixabay';
		$page     = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? max( 1, min( 50, absint( $_POST['per_page'] ) ) ) : 20;
		$orientation = isset( $_POST['orientation'] ) ? sanitize_key( wp_unslash( $_POST['orientation'] ) ) : '';
		$color       = isset( $_POST['color'] ) ? sanitize_key( wp_unslash( $_POST['color'] ) ) : '';

		if ( ! in_array( $orientation, array( '', 'landscape', 'portrait', 'square' ), true ) ) {
			$orientation = '';
		}

		$allowed_colors = array( '', 'grayscale', 'transparent', 'red', 'orange', 'yellow', 'green', 'turquoise', 'blue', 'lilac', 'pink', 'white', 'gray', 'black', 'brown' );
		if ( ! in_array( $color, $allowed_colors, true ) ) {
			$color = '';
		}

		if ( '' === $query ) {
			wp_send_json_success(
				array(
					'images' => array(),
					'page'   => $page,
				)
			);
		}

		if ( ! $this->is_source_enabled( $source ) ) {
			wp_send_json_error(
				array(
					'error_code' => 'source_disabled',
					'message'    => __( 'This source is disabled until a valid API key is configured.', 'free-stock-images' ),
					'images'     => array(),
				),
				400
			);
		}

		$provider = $this->get_provider_instance( $source );
		if ( ! $provider ) {
			wp_send_json_error(
				array(
					'error_code' => 'invalid_source',
					'message'    => __( 'Invalid image source selected.', 'free-stock-images' ),
					'images'     => array(),
				),
				400
			);
		}

		try {
			$filters = array(
				'orientation' => $orientation,
				'color'       => $color,
			);
			$images  = $provider->search_images( $query, $filters, $page, $per_page );
			wp_send_json_success(
				array(
					'images' => $images,
					'page'   => $page,
				)
			);
		} catch ( \Throwable $exception ) {
			wp_send_json_error(
				array(
					'error_code' => 'provider_error',
					'message'    => $exception->getMessage(),
					'images'     => array(),
				),
				500
			);
		}
	}

	/**
	 * AJAX import endpoint.
	 *
	 * @return void
	 */
	public function ajax_import() {
		check_ajax_referer( self::NONCE_ACTION );

		if ( ! current_user_can( self::CAP_UPLOAD_FILES ) ) {
			wp_send_json_error(
				array(
					'error_code' => 'unauthorized',
					'message'    => __( 'You are not allowed to import images.', 'free-stock-images' ),
				),
				403
			);
		}

		$image_url   = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$attribution = isset( $_POST['attribution'] ) ? sanitize_text_field( wp_unslash( $_POST['attribution'] ) ) : '';
		$source      = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : '';
		$remote_id   = isset( $_POST['remote_id'] ) ? sanitize_text_field( wp_unslash( $_POST['remote_id'] ) ) : '';

		if ( '' === $image_url ) {
			wp_send_json_error(
				array(
					'error_code' => 'missing_image_url',
					'message'    => __( 'Image URL is required.', 'free-stock-images' ),
				),
				400
			);
		}

		$importer = new Importer();
		$result   = $importer->import_from_url(
			$image_url,
			array(
				'title'       => $title,
				'attribution' => $attribution,
				'source'      => $source,
				'remote_id'   => $remote_id,
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'error_code' => 'import_failed',
					'message'    => $result->get_error_message(),
				),
				500
			);
		}

		$attachment_url = wp_get_attachment_url( $result );
		$mime_type      = get_post_mime_type( $result );
		$post           = get_post( $result );

		wp_send_json_success(
			array(
				'attachment_id' => $result,
				'url'           => $attachment_url ? $attachment_url : '',
				'title'         => $post ? get_the_title( $post ) : '',
				'mime'          => $mime_type ? $mime_type : '',
			)
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function get_source_config() {
		return array(
			'unsplash' => array(
				'label'   => 'Unsplash',
				'enabled' => $this->is_source_enabled( 'unsplash' ),
			),
			'pixabay'  => array(
				'label'   => 'Pixabay',
				'enabled' => $this->is_source_enabled( 'pixabay' ),
			),
			'pexels'   => array(
				'label'   => 'Pexels',
				'enabled' => $this->is_source_enabled( 'pexels' ),
			),
		);
	}

	/**
	 * @param string $source Provider key.
	 * @return bool
	 */
	private function is_source_enabled( $source ) {
		switch ( $source ) {
			case 'unsplash':
				return '' !== trim( (string) get_option( SettingsPage::OPTION_UNSPLASH, '' ) );
			case 'pixabay':
			case 'pexels':
				return true;
			default:
				return false;
		}
	}

	/**
	 * @param string $source Provider key.
	 * @return ProviderInterface|null
	 */
	protected function get_provider_instance( $source ) {
		switch ( $source ) {
			case 'unsplash':
				return new Unsplash();
			case 'pexels':
				return new Pexels();
			case 'pixabay':
				return new Pixabay();
			default:
				return null;
		}
	}
}
