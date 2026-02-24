<?php

class Test_Plugin_Bootstrap extends WP_UnitTestCase {
	public function test_plugin_core_class_is_available() {
		$this->assertTrue( class_exists( '\FreeStockImages\Core\Plugin' ) );
	}

	public function test_settings_options_registered() {
		do_action( 'admin_init' );

		$this->assertNotFalse( get_registered_settings()['fsi_unsplash_key'] ?? false );
		$this->assertNotFalse( get_registered_settings()['fsi_pixabay_key'] ?? false );
		$this->assertNotFalse( get_registered_settings()['fsi_pexels_key'] ?? false );
	}
}
