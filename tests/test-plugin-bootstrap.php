<?php

class Test_Plugin_Bootstrap extends WP_UnitTestCase {
	public function test_plugin_core_class_is_available() {
		$this->assertTrue( class_exists( '\FreeStockImages\Core\Plugin' ) );
		$this->assertTrue( class_exists( '\FreeStockImages\Admin\MenuRegistrar' ) );
		$this->assertTrue( class_exists( '\FreeStockImages\Admin\AssetEnqueuer' ) );
		$this->assertTrue( class_exists( '\FreeStockImages\Ajax\AjaxController' ) );
		$this->assertTrue( class_exists( '\FreeStockImages\API\ProviderFactory' ) );
		$this->assertTrue( class_exists( '\FreeStockImages\API\SourcePolicy' ) );
	}

	public function test_settings_options_registered() {
		do_action( 'admin_init' );

		$this->assertNotFalse( get_registered_settings()['fsi_unsplash_key'] ?? false );
		$this->assertNotFalse( get_registered_settings()['fsi_pixabay_key'] ?? false );
		$this->assertNotFalse( get_registered_settings()['fsi_pexels_key'] ?? false );
	}

	public function test_core_hooks_are_registered() {
		$this->assertNotFalse( has_action( 'admin_menu' ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts' ) );
		$this->assertNotFalse( has_action( 'wp_ajax_fsi_search' ) );
		$this->assertNotFalse( has_action( 'wp_ajax_fsi_import' ) );
	}

	public function test_source_policy_and_provider_factory_contract() {
		$policy = new \FreeStockImages\API\SourcePolicy();
		$this->assertFalse( $policy->is_enabled( 'unsplash' ) );
		$this->assertTrue( $policy->is_enabled( 'pixabay' ) );
		$this->assertTrue( $policy->is_enabled( 'pexels' ) );

		$config = $policy->get_source_config();
		$this->assertArrayHasKey( 'unsplash', $config );
		$this->assertArrayHasKey( 'pixabay', $config );
		$this->assertArrayHasKey( 'pexels', $config );

		$factory = new \FreeStockImages\API\ProviderFactory();
		$this->assertInstanceOf( '\FreeStockImages\API\Unsplash', $factory->make( 'unsplash' ) );
		$this->assertInstanceOf( '\FreeStockImages\API\Pixabay', $factory->make( 'pixabay' ) );
		$this->assertInstanceOf( '\FreeStockImages\API\Pexels', $factory->make( 'pexels' ) );
		$this->assertNull( $factory->make( 'unknown' ) );
	}

	public function test_localized_script_contract_is_present() {
		do_action( 'admin_enqueue_scripts', 'upload.php' );

		global $wp_scripts;
		$this->assertNotNull( $wp_scripts );

		$script_data = $wp_scripts->get_data( 'fsi-modal', 'data' );
		$this->assertIsString( $script_data );
		$this->assertStringContainsString( 'fsi_ajax', $script_data );
		$this->assertStringContainsString( 'ajaxUrl', $script_data );
		$this->assertStringContainsString( 'searchAction', $script_data );
		$this->assertStringContainsString( 'importAction', $script_data );
	}
}
