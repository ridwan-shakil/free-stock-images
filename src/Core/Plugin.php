<?php
/**
 * Main plugin class responsible for initializing services and orchestrating plugin functionality.
 *
 * @package PlugmintStockImages
 * @since 1.0.0
 */

namespace PlugmintStockImages\Core;

use PlugmintStockImages\Admin\AssetEnqueuer;
use PlugmintStockImages\Admin\MenuRegistrar;
use PlugmintStockImages\Admin\MediaPage;
use PlugmintStockImages\Admin\SettingsPage;
use PlugmintStockImages\Ajax\AjaxController;
use PlugmintStockImages\API\ProviderFactory;
use PlugmintStockImages\API\SourcePolicy;
use PlugmintStockImages\Services\Importer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin plugin orchestrator.
 *
 * Legacy MediaTab remains intentionally inactive; active modal integration is
 * handled by AssetEnqueuer + assets/js/modal.js.
 */
final class Plugin {
	const VERSION          = '1.0.0';
	const NONCE_ACTION     = 'fsimgs_nonce';
	const AJAX_SEARCH      = 'fsimgs_search';
	const AJAX_IMPORT      = 'fsimgs_import';
	const CAP_MANAGE       = 'manage_options';
	const CAP_UPLOAD_FILES = 'upload_files';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Menu registrar service.
	 *
	 * @var MenuRegistrar|null
	 */
	private $menu_registrar;

	/**
	 * Asset enqueuer service.
	 *
	 * @var AssetEnqueuer|null
	 */
	private $asset_enqueuer;

	/**
	 * AJAX controller service.
	 *
	 * @var AjaxController|null
	 */
	private $ajax_controller;

	/**
	 * Get the singleton instance of the plugin.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct() {
	}

	/**
	 * Initialize plugin services and hooks.
	 *
	 * @return void
	 */
	public function init() {
		$settings_page = new SettingsPage();
		$media_page    = new MediaPage();
		$source_policy = new SourcePolicy();

		$this->menu_registrar = new MenuRegistrar(
			$settings_page,
			$media_page,
			self::CAP_MANAGE,
			self::CAP_UPLOAD_FILES
		);

		$this->asset_enqueuer = new AssetEnqueuer(
			self::VERSION,
			self::NONCE_ACTION,
			self::AJAX_SEARCH,
			self::AJAX_IMPORT,
			self::CAP_UPLOAD_FILES,
			$source_policy
		);

		$this->ajax_controller = new AjaxController(
			self::NONCE_ACTION,
			self::AJAX_SEARCH,
			self::AJAX_IMPORT,
			self::CAP_UPLOAD_FILES,
			new ProviderFactory(),
			$source_policy,
			new Importer()
		);

		$this->menu_registrar->register();
		$this->asset_enqueuer->register();
		$this->ajax_controller->register();
	}
}
