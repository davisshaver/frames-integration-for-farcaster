<?php
/**
 * Tests for Storage class.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP\Tests\Unit;

use Farcaster_WP\Storage;
use PHPUnit\Framework\TestCase;

/**
 * Test Storage class functionality
 *
 * @TODO: Refactor to use WP_UnitTestCase.
 */
class Storage_Test extends TestCase {
	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->drop_tables();
		delete_option( Storage::EVENTS_TABLE_VERSION_OPTION );
		delete_option( Storage::FIDS_TABLE_VERSION_OPTION );
	}

	/**
	 * Clean up test environment.
	 */
	protected function tearDown(): void {
		$this->drop_tables();
		delete_option( Storage::EVENTS_TABLE_VERSION_OPTION );
		delete_option( Storage::FIDS_TABLE_VERSION_OPTION );
		parent::tearDown();
	}

	/**
	 * Drop test tables.
	 */
	private function drop_tables(): void {
		global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 
			$wpdb->prepare( 
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
				'DROP TABLE IF EXISTS %i', 
				Storage::get_events_table_name() 
			) 
		); 
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 
				$wpdb->prepare( 
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
					'DROP TABLE IF EXISTS %i', 
					Storage::get_fids_table_name() 
				) 
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Test instance creation
	 */
	public function test_instance() {
		$this->assertInstanceOf( Storage::class, Storage::instance() );
	}

	/**
	 * Test table names
	 */
	public function test_table_names() {
		global $wpdb;
		$this->assertEquals( $wpdb->prefix . 'farcaster_wp_events', Storage::get_events_table_name() );
		$this->assertEquals( $wpdb->prefix . 'farcaster_wp_fids', Storage::get_fids_table_name() );
	}

	/**
	 * Test subscription management
	 */
	public function test_subscription_management() {
		// Ensure tables exist.
		Storage::action_init();

		$test_data = [
			'fid'               => 12345,
			'app_key'           => 'test_app',
			'app_url'           => 'https://example.com',
			'token'             => 'test_token',
			'created_timestamp' => time(),
			'updated_timestamp' => time(),
			'status'            => 'active',
		];

		// Test adding subscription.
		$format = [
			'%d',
			'%s',
			'%s',
			'%s',
			'%d',
			'%d',
			'%s',
		];
		$id     = Storage::add_subscription( $test_data, $format );
		$this->assertNotFalse( $id );

		// Test getting subscription by FID and app key.
		$subscription = Storage::get_subscription_by_fid_and_app_key( $test_data['fid'], $test_data['app_key'] );
		$this->assertNotEmpty( $subscription );
		$this->assertEquals( $test_data['fid'], $subscription['fid'] );
		$this->assertEquals( $test_data['app_key'], $subscription['app_key'] );

		// Test getting subscription by token.
		$subscription = Storage::get_subscription_by_token( $test_data['token'] );
		$this->assertNotEmpty( $subscription );
		$this->assertEquals( $test_data['token'], $subscription['token'] );

		// Test getting active subscriptions before status update.
		$active_subscriptions = Storage::get_active_subscriptions();
		$this->assertIsArray( $active_subscriptions );
		$this->assertCount( 1, $active_subscriptions, 'Should have exactly one active subscription' );
		$this->assertEquals( $test_data['fid'], $active_subscriptions[0]['fid'], 'Active subscription should match test data' );
		$this->assertEquals( $test_data['app_key'], $active_subscriptions[0]['app_key'], 'Active subscription should match test data' );
		$this->assertEquals( $test_data['app_url'], $active_subscriptions[0]['app_url'], 'Active subscription should match test data' );
		$this->assertEquals( $test_data['token'], $active_subscriptions[0]['token'], 'Active subscription should match test data' );
		$this->assertEquals( 'active', $active_subscriptions[0]['status'], 'Subscription status should be active' );

		// Test updating subscription.
		$update_data   = [
			'status' => 'inactive',
		];
		$update_format = [ '%s' ];
		$result        = Storage::update_subscription( $id, $update_data, $update_format );
		$this->assertNotFalse( $result );

		// Test getting active subscriptions after status update.
		$active_subscriptions = Storage::get_active_subscriptions();
		$this->assertIsArray( $active_subscriptions );
		$this->assertEmpty( $active_subscriptions, 'Should have no active subscriptions after status update' );
	}

	/**
	 * Test event recording
	 */
	public function test_event_recording() {
		global $wpdb;

		// Ensure tables exist.
		Storage::action_init();

		$test_event = [
			'event_type' => 'frame_added',
			'fid'        => 12345,
			'timestamp'  => time(),
			'full_event' => wp_json_encode( [ 'test' => 'data' ] ),
		];

		$format = [
			'%s',
			'%d',
			'%d',
			'%s',
		];

		$event_id = Storage::record_event( $test_event, $format );
		$this->assertNotFalse( $event_id );

		// Verify the event was stored correctly in the database.
		$table_name = Storage::get_events_table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$stored_event = $wpdb->get_row( 
			$wpdb->prepare( 
				'SELECT * FROM %i WHERE id = %d', 
				$table_name,
				$event_id 
			), 
			ARRAY_A 
		);

		$this->assertNotEmpty( $stored_event, 'Event should be found in database' );
		$this->assertEquals( $test_event['event_type'], $stored_event['event_type'], 'Event type should match' );
		$this->assertEquals( $test_event['fid'], $stored_event['fid'], 'FID should match' );
		$this->assertEquals( $test_event['timestamp'], $stored_event['timestamp'], 'Timestamp should match' );
		$this->assertEquals( $test_event['full_event'], $stored_event['full_event'], 'Full event data should match' );

		// Test recording multiple events.
		$test_event_2 = [
			'event_type' => 'frame_removed',
			'fid'        => 67890,
			'timestamp'  => time(),
			'full_event' => wp_json_encode( [ 'test' => 'data2' ] ),
		];

		$event_id_2 = Storage::record_event( $test_event_2, $format );
		$this->assertNotFalse( $event_id_2 );

		// Verify both events exist and have correct values.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$stored_events = $wpdb->get_results( 
			$wpdb->prepare( 
				'SELECT * FROM %i WHERE id IN (%d, %d) ORDER BY id', 
				$table_name,
				$event_id, 
				$event_id_2 
			), 
			ARRAY_A 
		);

		$this->assertCount( 2, $stored_events, 'Should have two events stored' );

		// Verify first event.
		$this->assertEquals( $test_event['event_type'], $stored_events[0]['event_type'], 'First event type should match' );
		$this->assertEquals( $test_event['fid'], $stored_events[0]['fid'], 'First FID should match' );
		$this->assertEquals( $test_event['timestamp'], $stored_events[0]['timestamp'], 'First timestamp should match' );
		$this->assertEquals( $test_event['full_event'], $stored_events[0]['full_event'], 'First full event data should match' );

		// Verify second event.
		$this->assertEquals( $test_event_2['event_type'], $stored_events[1]['event_type'], 'Second event type should match' );
		$this->assertEquals( $test_event_2['fid'], $stored_events[1]['fid'], 'Second FID should match' );
		$this->assertEquals( $test_event_2['timestamp'], $stored_events[1]['timestamp'], 'Second timestamp should match' );
		$this->assertEquals( $test_event_2['full_event'], $stored_events[1]['full_event'], 'Second full event data should match' );

		// Test querying events by FID.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$fid_events = $wpdb->get_results( 
			$wpdb->prepare( 
				'SELECT * FROM %i WHERE fid = %d', 
				$table_name,
				$test_event['fid'] 
			), 
			ARRAY_A 
		);

		$this->assertCount( 1, $fid_events, 'Should find one event for the first FID' );
		$this->assertEquals( $test_event['event_type'], $fid_events[0]['event_type'], 'Event type should match for FID query' );

		// Test querying events by event_type.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$type_events = $wpdb->get_results( 
			$wpdb->prepare( 
				'SELECT * FROM %i WHERE event_type = %s', 
				$table_name,
				$test_event_2['event_type'] 
			), 
			ARRAY_A 
		);

		$this->assertCount( 1, $type_events, 'Should find one event for frame_removed type' );
		$this->assertEquals( $test_event_2['fid'], $type_events[0]['fid'], 'FID should match for event type query' );

		// Test that the stored full_event can be decoded and matches original data.
		$decoded_event = json_decode( $stored_events[0]['full_event'], true );
		$this->assertNotNull( $decoded_event, 'Stored JSON should be valid and decodable' );
		$this->assertEquals( 
			json_decode( $test_event['full_event'], true ),
			$decoded_event,
			'Decoded full event should match original event data'
		);

		$decoded_event_2 = json_decode( $stored_events[1]['full_event'], true );
		$this->assertNotNull( $decoded_event_2, 'Second stored JSON should be valid and decodable' );
		$this->assertEquals(
			json_decode( $test_event_2['full_event'], true ),
			$decoded_event_2, 
			'Decoded second full event should match original event data'
		);
	}

	/**
	 * Test table creation
	 */
	public function test_table_creation() {
		global $wpdb;

		// Verify tables don't exist initially.
		$events_table = Storage::get_events_table_name();
		$fids_table   = Storage::get_fids_table_name();

		$this->assertNull(
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $events_table ) ),
			'Events table should not exist before initialization'
		);
		$this->assertNull(
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $fids_table ) ),
			'FIDs table should not exist before initialization'
		);

		// Trigger table creation.
		Storage::action_init();

		// Verify tables exist after initialization.
		$this->assertEquals( 
			$events_table, 
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $events_table ) ),
			'Events table should exist after initialization'
		);
		$this->assertEquals( 
			$fids_table, 
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $fids_table ) ),
			'FIDs table should exist after initialization'
		);

		// Check if version options are set.
		$this->assertEquals( 
			Storage::EVENTS_TABLE_VERSION, 
			get_option( Storage::EVENTS_TABLE_VERSION_OPTION ),
			'Events table version option should be set correctly'
		);
		$this->assertEquals( 
			Storage::FIDS_TABLE_VERSION, 
			get_option( Storage::FIDS_TABLE_VERSION_OPTION ),
			'FIDs table version option should be set correctly'
		);
	}
}
