<?php
/**
 * PHPUnit bootstrap file for setting up WordPress testing.
 *
 * @package Farcaster_WP
 */

use Farcaster_WP\Logger;

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
 *
 * But first set the option flags required for signature verification and notifications.
 */
function _manually_load_plugin() {
	update_option(
		'farcaster_wp',
		[
			'notifications_enabled' => true,
			'rpc_url'               => 'https://optimism-mainnet.infura.io/v3/ef5de06be90e40c29efc35534f46a5dd',
		] 
	);
	require dirname( __DIR__ ) . '/frames-integration-for-farcaster.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

define( 'IS_TEST_ENV', 1 );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

if ( ! defined( 'VENDOR_DIR' ) ) {
	define( 'VENDOR_DIR', dirname( __DIR__ ) . '/vendor/' );
}

if ( ! defined( 'WP_CLI_ROOT' ) ) {
	define( 'WP_CLI_ROOT', VENDOR_DIR . 'wp-cli/wp-cli' );
}

require_once WP_CLI_ROOT . '/php/utils.php';
require_once WP_CLI_ROOT . '/php/dispatcher.php';
require_once WP_CLI_ROOT . '/php/class-wp-cli.php';
require_once WP_CLI_ROOT . '/php/class-wp-cli-command.php';

\WP_CLI\Utils\load_dependencies();
\WP_CLI::set_logger( new Logger() );
