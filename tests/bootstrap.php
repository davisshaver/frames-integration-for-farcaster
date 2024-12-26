<?php
/**
 * PHPUnit bootstrap file for setting up WordPress testing.
 *
 * @package Farcaster_WP
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	// phpcs:ignore
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?";
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/farcaster-wp.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Set the option flags required for testing signature verification.
 */
function _update_required_options() {
	update_option( 'farcaster_wp', [ 'rpc_url' => 'https://optimism-mainnet.infura.io/v3/ef5de06be90e40c29efc35534f46a5dd' ] );
}
tests_add_filter( 'init', '_update_required_options' );

define( 'IS_TEST_ENV', 1 );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
