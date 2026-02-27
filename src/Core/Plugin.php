<?php

namespace FreeStockImages\Core;

use FreeStockImages\Admin\AssetEnqueuer;
use FreeStockImages\Admin\MenuRegistrar;
use FreeStockImages\Admin\MediaPage;
use FreeStockImages\Admin\SettingsPage;
use FreeStockImages\Ajax\AjaxController;
use FreeStockImages\API\ProviderFactory;
use FreeStockImages\API\SourcePolicy;
use FreeStockImages\Services\Importer;

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
	 * @var MenuRegistrar|null
	 */
	private $menu_registrar;

	/**
	 * @var AssetEnqueuer|null
	 */
	private $asset_enqueuer;

	/**
	 * @var AjaxController|null
	 */
	private $ajax_controller;

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
