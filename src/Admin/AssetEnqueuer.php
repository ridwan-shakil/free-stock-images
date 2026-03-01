<?php

namespace FreeStockImages\Admin;

use FreeStockImages\API\SourcePolicy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers admin/Elementor assets and localizes runtime config.
 */
class AssetEnqueuer {
	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var string
	 */
	private $nonce_action;

	/**
	 * @var string
	 */
	private $search_action;

	/**
	 * @var string
	 */
	private $import_action;

	/**
	 * @var string
	 */
	private $upload_cap;

	/**
	 * @var SourcePolicy
	 */
	private $source_policy;

	/**
	 * @param string       $version Plugin version.
	 * @param string       $nonce_action Nonce action.
	 * @param string       $search_action AJAX search action.
	 * @param string       $import_action AJAX import action.
	 * @param string       $upload_cap Upload capability.
	 * @param SourcePolicy $source_policy Source policy service.
	 */
	public function __construct( $version, $nonce_action, $search_action, $import_action, $upload_cap, SourcePolicy $source_policy ) {
		$this->version       = $version;
		$this->nonce_action  = $nonce_action;
		$this->search_action = $search_action;
		$this->import_action = $import_action;
		$this->upload_cap    = $upload_cap;
		$this->source_policy = $source_policy;
	}

	/**
	 * @return void
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		$this->register_elementor_enqueue_hooks();
	}

	/**
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
	 * @return void
	 */
	public function enqueue_elementor_assets() {
		$this->enqueue_shared_assets();
	}

	/**
	 * @return void
	 */
	private function enqueue_shared_assets() {
		wp_enqueue_media();

		wp_enqueue_style(
			'fsi-admin-style',
			FSI_PLUGIN_URL . 'assets/css/styles.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			'fsi-modal',
			FSI_PLUGIN_URL . 'assets/js/modal.js',
			array( 'jquery', 'media-editor', 'media-views' ),
			$this->version,
			true
		);

		wp_localize_script(
			'fsi-modal',
			'fsi_ajax',
			$this->get_localized_config()
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_localized_config() {
		return array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( $this->nonce_action ),
			'searchAction' => $this->search_action,
			'importAction' => $this->import_action,
			'perPage'      => 20,
			'canUpload'    => current_user_can( $this->upload_cap ),
			'debug'        => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'sources'      => $this->source_policy->get_source_config(),
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
		// unset( $hook_suffix );
		// return is_admin();

		$modal_locations = array(
			'post.php',
			'post-new.php',
			'media_page_fsi-media-page',
		);

		if ( is_admin() && in_array( $hook_suffix, $modal_locations, true ) ) {
			return true;
		}
	}
}
