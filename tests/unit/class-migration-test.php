<?php
/**
 * Tests for Migration class.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP\Tests\Unit;

use Farcaster_WP\Migration;
use Farcaster_WP\Storage;
use WP_UnitTestCase;

/**
 * Migration test case.
 */
class Migration_Test extends WP_UnitTestCase {
	/**
	 * Test data for subscriptions.
	 *
	 * @var array
	 */
	private $test_subscriptions = [
		900356 => [
			'0xacc58a07af22606a3ded35785e5a7020cda7e1372bf66369a0beb7e8c2caf417' => [
				'url'       => 'https://api.warpcast.com/v1/frame-notifications',
				'token'     => '0193c0ed-e0ed-f2c7-6ed8-ab8675dd88aa',
				'timestamp' => 1734174030,
			],
		],
	];

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();
		// Reset any existing subscriptions.
		delete_option( 'farcaster_wp_subscriptions' );

		// Initialize storage tables.
		Storage::action_init();

		// Clear the storage table.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}farcaster_wp_fids" );
	}

	/**
	 * Tear down test environment.
	 */
	public function tear_down(): void {
		parent::tear_down();
		// Clean up after tests.
		delete_option( 'farcaster_wp_subscriptions' );
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}farcaster_wp_fids" );
	}

	/**
	 * Test cli_migrate with dry run.
	 */
	public function test_cli_migrate_dry_run() {
		// Set up test data.
		update_option( 'farcaster_wp_subscriptions', $this->test_subscriptions );

		Migration::cli_migrate( [], [ 'dry-run' => true ] );

		// Get the output.
		$output = $this->getActualOutputForAssertion();

		// Verify output.
		$this->assertStringContainsString( "\n===================\n=     Dry Run     =\n===================\n", $output );
		$this->assertStringContainsString( "Migrating legacy subscriptions...\n", $output );
		$this->assertStringContainsString( 'Skipping migration for subscription with FID 900356', $output );
		$this->assertStringContainsString( 'Would have converted 1 subscription', $output );

		// Verify no data was actually migrated.
		$subscription = Storage::get_subscription_by_fid_and_app_key(
			900356,
			'0xacc58a07af22606a3ded35785e5a7020cda7e1372bf66369a0beb7e8c2caf417'
		);
		$this->assertFalse( $subscription );
	}

	/**
	 * Test cli_migrate without dry run.
	 */
	public function test_cli_migrate() {
		// Set up test data.
		update_option( 'farcaster_wp_subscriptions', $this->test_subscriptions );

		// Run migration without dry run.
		Migration::cli_migrate( [], [] );

		// Get the output.
		$output = $this->getActualOutputForAssertion();

		// Verify output.
		$this->assertStringContainsString( "Migrating legacy subscriptions...\n", $output );
		$this->assertStringContainsString( 'Converted subscription for FID 900356', $output );
		$this->assertStringContainsString( 'Converted 1 subscription', $output );

		// Verify data was actually migrated.
		$subscription = Storage::get_subscription_by_fid_and_app_key(
			900356,
			'0xacc58a07af22606a3ded35785e5a7020cda7e1372bf66369a0beb7e8c2caf417'
		);
		$this->assertNotEmpty( $subscription );
		$this->assertEquals( '0193c0ed-e0ed-f2c7-6ed8-ab8675dd88aa', $subscription['token'] );
		$this->assertEquals( 'https://api.warpcast.com/v1/frame-notifications', $subscription['app_url'] );
		$this->assertEquals( 1734174030, $subscription['created_timestamp'] );
	}

	/**
	 * Test cli_migrate with no subscriptions.
	 */
	public function test_cli_migrate_no_subscriptions() {
		// Run migration without any subscriptions.
		Migration::cli_migrate( [], [] );

		// Get the output.
		$output = $this->getActualOutputForAssertion();

		// Verify output.
		$this->assertStringContainsString( "Migrating legacy subscriptions...\n", $output );
		$this->assertStringContainsString( 'Completed! No legacy subscriptions found.', $output );
	}

	/**
	 * Test cli_migrate with existing subscription.
	 */
	public function test_cli_migrate_existing_subscription() {
		// First, add a subscription directly to the storage.
		Storage::add_subscription(
			[
				'created_timestamp' => 1734174030,
				'fid'               => 900356,
				'app_key'           => '0xacc58a07af22606a3ded35785e5a7020cda7e1372bf66369a0beb7e8c2caf417',
				'status'            => 'active',
				'token'             => '0193c0ed-e0ed-f2c7-6ed8-ab8675dd88aa',
				'app_url'           => 'https://api.warpcast.com/v1/frame-notifications',
			],
			[ '%s', '%d', '%s', '%s', '%s', '%s' ]
		);

		// Set up test data in options.
		update_option( 'farcaster_wp_subscriptions', $this->test_subscriptions );

		// Run migration.
		Migration::cli_migrate( [], [] );

		// Get the output.
		$output = $this->getActualOutputForAssertion();

		// Verify output.
		$this->assertStringContainsString( "Migrating legacy subscriptions...\n", $output );
		$this->assertStringContainsString( 'Skipping migration for subscription with FID 900356', $output );
	}
}
