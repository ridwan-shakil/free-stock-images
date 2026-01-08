<?php


namespace FreeStockImages\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * The main entry point and loader class for the Free Stock Images plugin.
 *
 * This class instantiates and manages the core functional classes
 * responsible for :.
 */
class Loader {

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
		// $this->activation = new Admin_Notes_Activation();
		// $this->cpt        = new Admin_Notes_CPT();
		// $this->admin      = new Admin_Notes_Admin();
		// $this->assets     = new Admin_Notes_Assets();
		// $this->ajax       = new Admin_Notes_Ajax();
	}


	/**
	 * Executes the core initialization routine for the plugin.
	 *
	 * This method calls the primary setup/init methods on each
	 * instantiated component class to register hooks and functionality.
	 * * @return void
	 */
	public function run() {
		// $this->activation->init();
		// $this->cpt->register(); // Changed from init() to register() for clarity.
		// $this->admin->init();
		// $this->assets->init();
		// $this->ajax->init();
	}
}