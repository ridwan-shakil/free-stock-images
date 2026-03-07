<?php
require '/wp-phpunit/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () {

		require __DIR__ . '/../free-stock-images.php';
	}
);
require '/wp-phpunit/includes/bootstrap.php';
