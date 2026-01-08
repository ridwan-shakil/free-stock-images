<?php
/**
 * This files responsiblity is to create a singletone instense for the free stock images plugin
 * 
 */
namespace FreeStockImages\Core;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * This is the entry point of free stock images plugin
 */
final class Plugin {
    /**
     * @var Plugin|null
     */
    private static $instance = null;

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
        $this->init();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        new Loader();
    }

}
