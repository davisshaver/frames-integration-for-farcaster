<?php
/**
 * Tests for Notifications class.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP\Tests\Unit;

use Farcaster_WP\Notifications;
use Farcaster_WP\Storage;
use WP_UnitTestCase;

/**
 * Test Notifications class functionality
 */
class Notifications_Test extends WP_UnitTestCase {

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();
		// Remove all pre-registered HTTP request handlers.
		remove_all_filters( 'pre_http_request' );
		// Remove all email filters.
		remove_all_filters( 'wp_mail' );
		// Clean up subscriptions table.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}farcaster_wp_fids" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}farcaster_wp_events" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down() {
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'wp_mail' );
		parent::tear_down();
	}

	/**
	 * Test notifications enabled check
	 */
	public function test_are_enabled() {
		$options                          = get_option( 'farcaster_wp' );
		$options['notifications_enabled'] = false;
		update_option( 'farcaster_wp', $options );
		// Test when notifications are not enabled.
		$this->assertFalse( Notifications::are_enabled(), 'Notifications should be disabled when option is false' );

		$options['notifications_enabled'] = true;
		update_option( 'farcaster_wp', $options );
		$this->assertTrue( Notifications::are_enabled(), 'Notifications should be enabled when option is true' );
	}

	/**
	 * Test notification ID generation
	 */
	public function test_get_notification_id() {
		$post_id     = 123;
		$blog_name   = get_bloginfo( 'name' );
		$expected_id = 'farcaster_wp_notification_' . $post_id . '_' . str_replace( '-', '_', sanitize_title_with_dashes( $blog_name ) );
		$this->assertEquals( $expected_id, Notifications::get_notification_id( $post_id ) );
	}

	/**
	 * Test getting notification body
	 */
	public function test_get_notification_body() {
		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Post Title',
				'post_content' => 'Test post content',
				'post_status'  => 'publish',
			)
		);

		$notification_body = Notifications::get_notification_body( $post_id );

		$this->assertIsArray( $notification_body );
		$this->assertArrayHasKey( 'notificationId', $notification_body );
		$this->assertArrayHasKey( 'title', $notification_body );
		$this->assertArrayHasKey( 'body', $notification_body );
		$this->assertArrayHasKey( 'targetUrl', $notification_body );

		$this->assertEquals( Notifications::get_notification_id( $post_id ), $notification_body['notificationId'] );
		$this->assertStringContainsString( 'Test Post Title', $notification_body['title'] );
	}

	/**
	 * Test getting tokens by app URL
	 */
	public function test_get_tokens_by_app_url() {
		// Add test subscriptions.
		$test_data = array(
			array(
				'fid'               => 12345,
				'app_key'           => 'test_app_1',
				'app_url'           => 'https://example1.com',
				'token'             => 'test_token_1',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
			array(
				'fid'               => 67890,
				'app_key'           => 'test_app_2',
				'app_url'           => 'https://example1.com',
				'token'             => 'test_token_2',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
		);

		$subscription_ids = [];
		$format           = array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' );
		foreach ( $test_data as $data ) {
			$subscription_ids[] = Storage::add_subscription( $data, $format );
		}

		$tokens_by_url = Notifications::get_tokens_by_app_url();

		$this->assertIsArray( $tokens_by_url );
		$this->assertArrayHasKey( 'https://example1.com', $tokens_by_url );
		$this->assertCount( 2, $tokens_by_url['https://example1.com'] );
		$this->assertContains( 'test_token_1', $tokens_by_url['https://example1.com'] );
		$this->assertContains( 'test_token_2', $tokens_by_url['https://example1.com'] );

		// Let's update the status of the first subscription to inactive.
		Storage::update_subscription( $subscription_ids[0], array( 'status' => 'inactive' ), array( '%s' ) );

		$tokens_by_url = Notifications::get_tokens_by_app_url();
		$this->assertArrayHasKey( 'https://example1.com', $tokens_by_url );
		$this->assertCount( 1, $tokens_by_url['https://example1.com'] );
		$this->assertContains( 'test_token_2', $tokens_by_url['https://example1.com'] );

		// Let's update the status of the second subscription to inactive.
		Storage::update_subscription( $subscription_ids[1], array( 'status' => 'inactive' ), array( '%s' ) );

		$tokens_by_url = Notifications::get_tokens_by_app_url();
		$this->assertArrayNotHasKey( 'https://example1.com', $tokens_by_url );
	}

	/**
	 * Test webhook processing
	 */
	public function test_process_webhook() {
		$header = array(
			'fid' => 12345,
			'key' => 'test_app_key',
		);

		$payload = array(
			'event'               => 'frame_added',
			'notificationDetails' => array(
				'url'   => 'https://example.com',
				'token' => 'test_token',
			),
		);

		$signature = 'test_signature';

		$response = Notifications::process_webhook( $header, $payload, $signature );

		$this->assertIsArray( $response );
		$this->assertTrue( $response['success'] );

		// Verify subscription was added.
		$subscription = Storage::get_subscription_by_fid_and_app_key( 12345, 'test_app_key' );
		$this->assertNotEmpty( $subscription );
		$this->assertEquals( 'active', $subscription['status'] );
		$this->assertEquals( 'test_token', $subscription['token'] );
		$this->assertEquals( 'https://example.com', $subscription['app_url'] );

		// Let's update the status of the subscription to inactive.
		Storage::update_subscription( $subscription['id'], array( 'status' => 'inactive' ), array( '%s' ) );

		// Verify the subscription was updated.
		$subscription = Storage::get_subscription_by_fid_and_app_key( 12345, 'test_app_key' );
		$this->assertEquals( 'inactive', $subscription['status'] );
	}

	/**
	 * Test subscription management through webhook events
	 */
	public function test_subscription_management_through_webhooks() {
		// Test frame_added event.
		$header  = array(
			'fid' => 12345,
			'key' => 'test_app_key',
		);
		$payload = array(
			'event'               => 'frame_added',
			'notificationDetails' => array(
				'url'   => 'https://example.com',
				'token' => 'test_token',
			),
		);
		Notifications::process_webhook( $header, $payload, 'test_signature' );

		// Verify subscription was added.
		$subscription = Storage::get_subscription_by_fid_and_app_key( 12345, 'test_app_key' );
		$this->assertNotEmpty( $subscription );
		$this->assertEquals( 'active', $subscription['status'] );

		// Test frame_removed event.
		$payload['event'] = 'frame_removed';
		Notifications::process_webhook( $header, $payload, 'test_signature' );

		// Verify subscription was deactivated.
		$subscription = Storage::get_subscription_by_fid_and_app_key( 12345, 'test_app_key' );
		$this->assertEquals( 'inactive', $subscription['status'] );
	}

	/**
	 * Test notifications disabled event
	 */
	public function test_notifications_disabled() {
		// First add a subscription.
		$header  = array(
			'fid' => 12345,
			'key' => 'test_app_key',
		);
		$payload = array(
			'event'               => 'frame_added',
			'notificationDetails' => array(
				'url'   => 'https://example.com',
				'token' => 'test_token',
			),
		);
		Notifications::process_webhook( $header, $payload, 'test_signature' );

		// Then disable notifications.
		$payload['event'] = 'notifications_disabled';
		Notifications::process_webhook( $header, $payload, 'test_signature' );

		// Verify subscription was deactivated.
		$subscription = Storage::get_subscription_by_fid_and_app_key( 12345, 'test_app_key' );
		$this->assertEquals( 'inactive', $subscription['status'] );
	}

	/**
	 * Test notifications enabled event
	 */
	public function test_notifications_enabled() {
		$header  = array(
			'fid' => 12345,
			'key' => 'test_app_key',
		);
		$payload = array(
			'event'               => 'notifications_enabled',
			'notificationDetails' => array(
				'url'   => 'https://example.com',
				'token' => 'test_token',
			),
		);
		Notifications::process_webhook( $header, $payload, 'test_signature' );

		// Verify subscription was added and activated.
		$subscription = Storage::get_subscription_by_fid_and_app_key( 12345, 'test_app_key' );
		$this->assertNotEmpty( $subscription );
		$this->assertEquals( 'active', $subscription['status'] );
		$this->assertEquals( 'test_token', $subscription['token'] );
		$this->assertEquals( 'https://example.com', $subscription['app_url'] );

		// Let's update the status of the subscription to inactive.
		Storage::update_subscription( $subscription['id'], array( 'status' => 'inactive' ), array( '%s' ) );

		// Verify the subscription was updated.
		$subscription = Storage::get_subscription_by_fid_and_app_key( 12345, 'test_app_key' );
		$this->assertEquals( 'inactive', $subscription['status'] );
	}

	/**
	 * Test sending notifications for a published post
	 */
	public function test_send_publish_post_notifications() {
		// Create test subscriptions.
		$test_data = array(
			array(
				'fid'               => 12345,
				'app_key'           => 'test_app_1',
				'app_url'           => 'https://example1.com',
				'token'             => 'test_token_1',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
			array(
				'fid'               => 67890,
				'app_key'           => 'test_app_2',
				'app_url'           => 'https://example2.com',
				'token'             => 'test_token_2',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
		);

		$format           = array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' );
		$subscription_ids = [];
		foreach ( $test_data as $data ) {
			$subscription_ids[] = Storage::add_subscription( $data, $format );
		}

		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Notification Post',
				'post_content' => 'Test notification content',
				'post_status'  => 'publish',
			)
		);

		// Mock the HTTP request handler.
		$request_count = 0;
		$mock_handler  = function( $preempt, $args ) use ( &$request_count, $post_id ) {
			$request_count++;
			
			// Decode the request body.
			$body = json_decode( $args['body'], true );

			// Verify the notification payload.
			$this->assertArrayHasKey( 'notificationId', $body );
			$this->assertArrayHasKey( 'title', $body );
			$this->assertArrayHasKey( 'body', $body );
			$this->assertArrayHasKey( 'targetUrl', $body );
			$this->assertArrayHasKey( 'tokens', $body );
			
			// Verify the notification ID matches our expected format.
			$this->assertEquals( 
				Notifications::get_notification_id( $post_id ),
				$body['notificationId']
			);

			// Return a mock successful response.
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'success' => true,
						'result'  => array(
							'successfulTokens'  => $body['tokens'],
							'invalidTokens'     => array(),
							'rateLimitedTokens' => array(),
						),
					)
				),
			);
		};

		add_filter( 'pre_http_request', $mock_handler, 10, 3 );

		// Send the notifications.
		Notifications::send_publish_post_notifications( $post_id );

		// Verify that we made the expected number of HTTP requests.
		$this->assertEquals( 2, $request_count, 'Expected one request per unique app URL.' );

		// Verify the tokens were recorded in post meta.
		$recorded_tokens = get_post_meta( $post_id, 'farcaster_wp_tokens', true );
		$this->assertIsArray( $recorded_tokens );
		$this->assertContains( 'test_token_1', $recorded_tokens );
		$this->assertContains( 'test_token_2', $recorded_tokens );

		// Let's update the status of the first subscription to inactive.
		Storage::update_subscription( $subscription_ids[0], array( 'status' => 'inactive' ), array( '%s' ) );
		// Let's update the status of the second subscription to inactive.
		Storage::update_subscription( $subscription_ids[1], array( 'status' => 'inactive' ), array( '%s' ) );

		// Send the notifications again.
		Notifications::send_publish_post_notifications( $post_id );

		// Verify that we made the expected number of HTTP requests.
		$this->assertEquals( 2, $request_count, 'Expected no new requests since all subscriptions are inactive.' );
	}

	/**
	 * Test sending notifications with failed requests
	 */
	public function test_send_publish_post_notifications_with_failures() {
		// Create test subscriptions.
		$test_data = array(
			array(
				'fid'               => 12345,
				'app_key'           => 'test_app_1',
				'app_url'           => 'https://example1.com',
				'token'             => 'test_token_1',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
			array(
				'fid'               => 67890,
				'app_key'           => 'test_app_2',
				'app_url'           => 'https://example1.com',
				'token'             => 'test_token_2',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
		);

		$format = array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' );
		foreach ( $test_data as $data ) {
			Storage::add_subscription( $data, $format );
		}

		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Notification Post',
				'post_content' => 'Test notification content',
				'post_status'  => 'publish',
			)
		);

		// Track request attempts.
		$request_attempts = 0;

		// Mock the HTTP request handler to simulate failures initially.
		$mock_handler = function() use ( &$request_attempts ) {
			$request_attempts++;
			return new \WP_Error( 'http_request_failed', 'Request failed' );
		};

		add_filter( 'pre_http_request', $mock_handler, 10, 3 );

		// Capture emails.
		$emails = array();
		add_filter(
			'wp_mail',
			function( $args ) use ( &$emails ) {
				$emails[] = $args;
				return false; // Prevent actual email sending.
			}
		);

		// Send the notifications (this will fail).
		Notifications::send_publish_post_notifications( $post_id );

		// Verify that an error email was sent.
		$this->assertCount( 1, $emails, 'Expected one error email' );
		$this->assertStringContainsString( 'error', strtolower( $emails[0]['message'] ) );

		// Get all scheduled events.
		$cron_array  = _get_cron_array();
		$found_retry = false;
		$retry_args  = null;
		$retry_time  = null;

		// Look for our retry event.
		foreach ( $cron_array as $timestamp => $cron_events ) {
			foreach ( $cron_events as $hook => $events ) {
				if ( 'farcaster_wp_retry_notifications' === $hook ) {
					foreach ( $events as $event ) {
						if ( isset( $event['args'] ) && 
							'https://example1.com' === $event['args'][0] && 
							$post_id === $event['args'][2] ) {
							$found_retry = true;
							$retry_args  = $event['args'];
							$retry_time  = $timestamp;
							break 3;
						}
					}
				}
			}
		}

		$this->assertTrue( $found_retry, 'Expected to find a retry event scheduled' );
		$this->assertEquals( 1, $request_attempts, 'Expected one failed request attempt' );
		$this->assertNotNull( $retry_time, 'Expected retry time to be set' );
		$this->assertNotNull( $retry_args, 'Expected retry args to be set' );

		// Now let's simulate a successful retry.
		remove_all_filters( 'pre_http_request' );

		// Mock the HTTP request handler to simulate success on retry.
		$mock_success_handler = function( $preempt, $args ) use ( &$request_attempts, $post_id ) {
			$request_attempts++;
			
			// Decode the request body.
			$body = json_decode( $args['body'], true );

			// Verify the notification payload.
			$this->assertArrayHasKey( 'notificationId', $body );
			$this->assertArrayHasKey( 'title', $body );
			$this->assertArrayHasKey( 'body', $body );
			$this->assertArrayHasKey( 'targetUrl', $body );
			$this->assertArrayHasKey( 'tokens', $body );
			
			// Return a mock successful response.
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'success' => true,
						'result'  => array(
							'successfulTokens'  => $body['tokens'],
							'invalidTokens'     => array(),
							'rateLimitedTokens' => array(),
						),
					)
				),
			);
		};

		add_filter( 'pre_http_request', $mock_success_handler, 10, 3 );

		// Execute the retry by triggering the scheduled action.
		if ( $retry_args ) {
			do_action( 'farcaster_wp_retry_notifications', ...$retry_args );
		}

		// Verify that we made both the initial failed request and the successful retry.
		$this->assertEquals( 2, $request_attempts, 'Expected one failed request and one successful retry' );

		// Verify the tokens were recorded in post meta after successful retry.
		$recorded_tokens = get_post_meta( $post_id, 'farcaster_wp_tokens', true );
		$this->assertIsArray( $recorded_tokens );
		$this->assertContains( 'test_token_1', $recorded_tokens );
		$this->assertContains( 'test_token_2', $recorded_tokens );
	}

	/**
	 * Test notification suppression via post meta
	 */
	public function test_send_publish_post_notifications_with_suppression() {
		// Create test subscriptions.
		$test_data = array(
			array(
				'fid'               => 12345,
				'app_key'           => 'test_app_1',
				'app_url'           => 'https://example1.com',
				'token'             => 'test_token_1',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
			array(
				'fid'               => 67890,
				'app_key'           => 'test_app_2',
				'app_url'           => 'https://example2.com',
				'token'             => 'test_token_2',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
		);

		$format = array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' );
		foreach ( $test_data as $data ) {
			Storage::add_subscription( $data, $format );
		}

		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Notification Post',
				'post_content' => 'Test notification content',
				'post_status'  => 'publish',
			)
		);

		// Track request attempts.
		$request_attempts = 0;

		// Mock the HTTP request handler.
		$mock_handler = function() use ( &$request_attempts ) {
			$request_attempts++;
			
			// Return a mock successful response.
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'success' => true,
						'result'  => array(
							'successfulTokens'  => array( 'test_token_1', 'test_token_2' ),
							'invalidTokens'     => array(),
							'rateLimitedTokens' => array(),
						),
					)
				),
			);
		};

		add_filter( 'pre_http_request', $mock_handler, 10, 3 );

		// First, suppress notifications.
		update_post_meta( $post_id, 'farcaster_wp_suppress_notifications', true );

		// Try to send notifications (should be suppressed).
		Notifications::send_publish_post_notifications( $post_id );

		// Verify that no requests were made.
		$this->assertEquals( 0, $request_attempts, 'Expected no requests when notifications are suppressed' );

		// Verify no tokens were recorded in post meta.
		$recorded_tokens = get_post_meta( $post_id, 'farcaster_wp_tokens', true );
		$this->assertEmpty( $recorded_tokens, 'Expected no tokens to be recorded when notifications are suppressed' );

		// Now, remove suppression.
		delete_post_meta( $post_id, 'farcaster_wp_suppress_notifications' );

		// Try to send notifications again (should work).
		Notifications::send_publish_post_notifications( $post_id );

		// Verify that requests were made.
		$this->assertEquals( 2, $request_attempts, 'Expected requests to be made when notifications are not suppressed' );

		// Verify tokens were recorded in post meta.
		$recorded_tokens = get_post_meta( $post_id, 'farcaster_wp_tokens', true );
		$this->assertIsArray( $recorded_tokens );
		$this->assertContains( 'test_token_1', $recorded_tokens );
		$this->assertContains( 'test_token_2', $recorded_tokens );
	}

	/**
	 * Test handling of invalid tokens in notification responses
	 */
	public function test_send_publish_post_notifications_with_invalid_tokens() {
		// Create test subscriptions.
		$test_data = array(
			array(
				'fid'               => 12345,
				'app_key'           => 'test_app_1',
				'app_url'           => 'https://example1.com',
				'token'             => 'test_token_1',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
			array(
				'fid'               => 67890,
				'app_key'           => 'test_app_2',
				'app_url'           => 'https://example1.com',
				'token'             => 'test_token_2',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
			array(
				'fid'               => 13579,
				'app_key'           => 'test_app_3',
				'app_url'           => 'https://example2.com',
				'token'             => 'test_token_3',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
		);

		$subscription_ids = array();
		$format           = array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' );
		foreach ( $test_data as $data ) {
			$subscription_ids[] = Storage::add_subscription( $data, $format );
		}

		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Notification Post',
				'post_content' => 'Test notification content',
				'post_status'  => 'publish',
			)
		);

		// Track request attempts and responses.
		$request_attempts = 0;
		$processed_tokens = array();

		// Mock the HTTP request handler.
		$mock_handler = function( $preempt, $args, $url ) use ( &$request_attempts, &$processed_tokens ) {
			$request_attempts++;
			
			// Decode the request body to get the tokens.
			$body             = json_decode( $args['body'], true );
			$tokens           = $body['tokens'];
			$processed_tokens = array_merge( $processed_tokens, $tokens );

			// For example1.com, mark test_token_1 as invalid.
			if ( 'https://example1.com' === $url ) {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode(
						array(
							'success' => true,
							'result'  => array(
								'successfulTokens'  => array( 'test_token_2' ),
								'invalidTokens'     => array( 'test_token_1' ),
								'rateLimitedTokens' => array(),
							),
						)
					),
				);
			}

			// For example2.com, mark test_token_3 as invalid.
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'success' => true,
						'result'  => array(
							'successfulTokens'  => array(),
							'invalidTokens'     => array( 'test_token_3' ),
							'rateLimitedTokens' => array(),
						),
					)
				),
			);
		};

		add_filter( 'pre_http_request', $mock_handler, 10, 3 );

		// Send the notifications.
		Notifications::send_publish_post_notifications( $post_id );

		// Verify that we made the expected number of HTTP requests.
		$this->assertEquals( 2, $request_attempts, 'Expected one request per unique app URL' );

		// Verify all tokens were processed.
		$this->assertCount( 3, $processed_tokens, 'Expected all tokens to be processed' );
		$this->assertContains( 'test_token_1', $processed_tokens );
		$this->assertContains( 'test_token_2', $processed_tokens );
		$this->assertContains( 'test_token_3', $processed_tokens );

		// Verify only successful tokens were recorded in post meta.
		$recorded_tokens = get_post_meta( $post_id, 'farcaster_wp_tokens', true );
		$this->assertIsArray( $recorded_tokens );
		$this->assertContains( 'test_token_2', $recorded_tokens );
		$this->assertNotContains( 'test_token_1', $recorded_tokens );
		$this->assertNotContains( 'test_token_3', $recorded_tokens );

		// Verify subscriptions with invalid tokens were deactivated.
		$subscription1 = Storage::get_subscription_by_token( 'test_token_1' );
		$this->assertEquals( 'inactive', $subscription1['status'], 'Subscription with invalid token should be inactive' );

		$subscription2 = Storage::get_subscription_by_token( 'test_token_2' );
		$this->assertEquals( 'active', $subscription2['status'], 'Subscription with valid token should remain active' );

		$subscription3 = Storage::get_subscription_by_token( 'test_token_3' );
		$this->assertEquals( 'inactive', $subscription3['status'], 'Subscription with invalid token should be inactive' );

		// Verify that a second notification attempt doesn't include the invalid tokens.
		$request_attempts = 0;
		$processed_tokens = array();

		Notifications::send_publish_post_notifications( $post_id );

		// Verify that we still made requests but only processed the valid token.
		$this->assertEquals( 1, $request_attempts, 'Expected one request for the remaining valid token' );
		$this->assertCount( 1, $processed_tokens, 'Expected only valid token to be processed' );
		$this->assertContains( 'test_token_2', $processed_tokens );
		$this->assertNotContains( 'test_token_1', $processed_tokens );
		$this->assertNotContains( 'test_token_3', $processed_tokens );
	}

	/**
	 * Test handling of rate-limited tokens in notification responses
	 */
	public function test_send_publish_post_notifications_with_rate_limits() {
		// Create test subscriptions.
		$test_data = array(
			array(
				'fid'               => 12345,
				'app_key'           => 'test_app_1',
				'app_url'           => 'https://example1.com',
				'token'             => 'test_token_1',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
			array(
				'fid'               => 67890,
				'app_key'           => 'test_app_2',
				'app_url'           => 'https://example1.com',
				'token'             => 'test_token_2',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
			array(
				'fid'               => 13579,
				'app_key'           => 'test_app_3',
				'app_url'           => 'https://example2.com',
				'token'             => 'test_token_3',
				'created_timestamp' => time(),
				'updated_timestamp' => time(),
				'status'            => 'active',
			),
		);

		$format = array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' );
		foreach ( $test_data as $data ) {
			Storage::add_subscription( $data, $format );
		}

		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Notification Post',
				'post_content' => 'Test notification content',
				'post_status'  => 'publish',
			)
		);

		// Track request attempts and responses.
		$request_attempts = 0;
		$processed_tokens = array();

		// Mock the HTTP request handler.
		$mock_handler = function( $preempt, $args, $url ) use ( &$request_attempts, &$processed_tokens ) {
			$request_attempts++;
			
			// Decode the request body to get the tokens.
			$body             = json_decode( $args['body'], true );
			$tokens           = $body['tokens'];
			$processed_tokens = array_merge( $processed_tokens, $tokens );

			// For example1.com, mark test_token_1 as rate limited.
			if ( 'https://example1.com' === $url ) {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode(
						array(
							'success' => true,
							'result'  => array(
								'successfulTokens'  => array( 'test_token_2' ),
								'invalidTokens'     => array(),
								'rateLimitedTokens' => array( 'test_token_1' ),
							),
						)
					),
				);
			}

			// For example2.com, mark test_token_3 as rate limited.
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'success' => true,
						'result'  => array(
							'successfulTokens'  => array(),
							'invalidTokens'     => array(),
							'rateLimitedTokens' => array( 'test_token_3' ),
						),
					)
				),
			);
		};

		add_filter( 'pre_http_request', $mock_handler, 10, 3 );

		// Send the notifications.
		Notifications::send_publish_post_notifications( $post_id );

		// Verify that we made the expected number of HTTP requests.
		$this->assertEquals( 2, $request_attempts, 'Expected one request per unique app URL' );

		// Verify all tokens were processed.
		$this->assertCount( 3, $processed_tokens, 'Expected all tokens to be processed' );
		$this->assertContains( 'test_token_1', $processed_tokens );
		$this->assertContains( 'test_token_2', $processed_tokens );
		$this->assertContains( 'test_token_3', $processed_tokens );

		// Verify only successful tokens were recorded in post meta.
		$recorded_tokens = get_post_meta( $post_id, 'farcaster_wp_tokens', true );
		$this->assertIsArray( $recorded_tokens );
		$this->assertContains( 'test_token_2', $recorded_tokens );
		$this->assertNotContains( 'test_token_1', $recorded_tokens );
		$this->assertNotContains( 'test_token_3', $recorded_tokens );

		// Verify subscriptions with rate-limited tokens remain active.
		$subscription1 = Storage::get_subscription_by_token( 'test_token_1' );
		$this->assertEquals( 'active', $subscription1['status'], 'Subscription with rate-limited token should remain active' );

		$subscription2 = Storage::get_subscription_by_token( 'test_token_2' );
		$this->assertEquals( 'active', $subscription2['status'], 'Subscription with successful token should remain active' );

		$subscription3 = Storage::get_subscription_by_token( 'test_token_3' );
		$this->assertEquals( 'active', $subscription3['status'], 'Subscription with rate-limited token should remain active' );

		// Get all scheduled events.
		$cron_array   = _get_cron_array();
		$retry_events = array();

		// Look for our retry events.
		foreach ( $cron_array as $timestamp => $cron_events ) {
			foreach ( $cron_events as $hook => $events ) {
				if ( 'farcaster_wp_retry_notifications' === $hook ) {
					foreach ( $events as $event ) {
						if ( isset( $event['args'] ) && $post_id === $event['args'][2] ) {
							$retry_events[] = array(
								'url'    => $event['args'][0],
								'tokens' => $event['args'][1],
								'time'   => $timestamp,
							);
						}
					}
				}
			}
		}

		// Verify that retries were scheduled for rate-limited tokens.
		$this->assertCount( 2, $retry_events, 'Expected retries to be scheduled for both rate-limited tokens' );

		// Verify retry for example1.com.
		$example1_retry = array_filter(
			$retry_events,
			function( $event ) {
				return 'https://example1.com' === $event['url'];
			}
		);
		$this->assertCount( 1, $example1_retry );
		$example1_retry = reset( $example1_retry );
		$this->assertContains( 'test_token_1', $example1_retry['tokens'] );
		$this->assertNotContains( 'test_token_2', $example1_retry['tokens'] );

		// Verify retry for example2.com.
		$example2_retry = array_filter(
			$retry_events,
			function( $event ) {
				return 'https://example2.com' === $event['url'];
			}
		);
		$this->assertCount( 1, $example2_retry );
		$example2_retry = reset( $example2_retry );
		$this->assertContains( 'test_token_3', $example2_retry['tokens'] );

		// Now let's simulate a successful retry.
		remove_all_filters( 'pre_http_request' );
		$request_attempts = 0;
		$processed_tokens = array();

		// Mock the HTTP request handler to simulate success on retry.
		$mock_success_handler = function( $preempt, $args ) use ( &$request_attempts, &$processed_tokens ) {
			$request_attempts++;
			
			// Decode the request body to get the tokens.
			$body             = json_decode( $args['body'], true );
			$tokens           = $body['tokens'];
			$processed_tokens = array_merge( $processed_tokens, $tokens );

			// Return a successful response for all tokens.
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'success' => true,
						'result'  => array(
							'successfulTokens'  => $tokens,
							'invalidTokens'     => array(),
							'rateLimitedTokens' => array(),
						),
					)
				),
			);
		};

		add_filter( 'pre_http_request', $mock_success_handler, 10, 3 );

		// Execute the retries.
		foreach ( $retry_events as $retry ) {
			do_action( 'farcaster_wp_retry_notifications', $retry['url'], $retry['tokens'], $post_id );
		}

		// Verify that retries were attempted.
		$this->assertEquals( 2, $request_attempts, 'Expected one retry request per rate-limited URL' );

		// Verify rate-limited tokens were processed in retries.
		$this->assertCount( 2, $processed_tokens, 'Expected rate-limited tokens to be processed in retries' );
		$this->assertContains( 'test_token_1', $processed_tokens );
		$this->assertContains( 'test_token_3', $processed_tokens );

		// Verify all tokens were recorded in post meta after successful retries.
		$recorded_tokens = get_post_meta( $post_id, 'farcaster_wp_tokens', true );
		$this->assertIsArray( $recorded_tokens );
		$this->assertContains( 'test_token_1', $recorded_tokens );
		$this->assertContains( 'test_token_2', $recorded_tokens );
		$this->assertContains( 'test_token_3', $recorded_tokens );
	}

	/**
	 * Test add_subscription failure scenario
	 */
	public function test_add_subscription_failure() {
		global $wpdb;
		$wpdb->hide_errors();

		// First, add a valid subscription.
		$test_data = array(
			'created_timestamp' => time(),
			'fid'               => 12345,
			'app_key'           => 'test_app_key',
			'status'            => 'active',
			'token'             => 'test_token',
			'app_url'           => 'https://example.com',
		);

		$format       = array( '%d', '%s', '%s', '%s', '%s', '%s' );
		$first_result = Storage::add_subscription( $test_data, $format );
		$this->assertNotFalse( $first_result );

		// Now try to insert the same record again with a forced ID to cause a duplicate key error.
		$test_data['id'] = $first_result;
		$format          = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' );
		$result          = Storage::add_subscription( $test_data, $format );

		// Verify that the insert failed.
		$this->assertFalse( $result );
		$wpdb->show_errors();
	}

	/**
	 * Test record_event failure scenario
	 */
	public function test_record_event_failure() {
		global $wpdb;
		$wpdb->hide_errors();
		// Try to insert with an invalid column name.
		$test_data = array(
			'event_type'        => 'test_event',
			'fid'               => 12345,
			'timestamp'         => time(),
			'full_event'        => wp_json_encode(
				array(
					'header'    => array( 'test' => 'header' ),
					'payload'   => array( 'test' => 'payload' ),
					'signature' => 'test_signature',
				)
			),
			'nonexistent_field' => 'this will cause an error',
		);

		$format = array( '%s', '%d', '%d', '%s', '%s' );
		$result = Storage::record_event( $test_data, $format );

		// Verify that the insert failed.
		$this->assertFalse( $result );
		$wpdb->show_errors();
	}

	/**
	 * Test add_subscription method with existing subscriptions
	 */
	public function test_add_subscription_with_existing_subscriptions() {
		// First, add an initial subscription.
		$fid           = 12345;
		$app_key       = 'test_app_key';
		$initial_url   = 'https://example.com';
		$initial_token = 'initial_token';

		Notifications::add_subscription( $fid, $app_key, $initial_url, $initial_token );

		// Verify initial subscription was added.
		$subscription = Storage::get_subscription_by_fid_and_app_key( $fid, $app_key );
		$this->assertNotEmpty( $subscription );
		$this->assertEquals( 'active', $subscription['status'] );
		$this->assertEquals( $initial_token, $subscription['token'] );
		$this->assertEquals( $initial_url, $subscription['app_url'] );

		// Update the subscription status to inactive.
		Storage::update_subscription(
			$subscription['id'],
			[
				'status' => 'inactive',
			],
			[ '%s' ]
		);

		// Now try to add a subscription with the same FID and app_key but different URL and token.
		$new_url   = 'https://example.com/new';
		$new_token = 'new_token';
		Notifications::add_subscription( $fid, $app_key, $new_url, $new_token );

		// Verify the subscription was updated.
		$updated_subscription = Storage::get_subscription_by_fid_and_app_key( $fid, $app_key );
		$this->assertNotEmpty( $updated_subscription );
		$this->assertEquals( 'active', $updated_subscription['status'] );
		$this->assertEquals( $new_token, $updated_subscription['token'] );
		$this->assertEquals( $new_url, $updated_subscription['app_url'] );
		$this->assertEquals( $subscription['id'], $updated_subscription['id'] );

		// Try to add another subscription with the same FID and app_key while status is active.
		$another_url   = 'https://example.com/another';
		$another_token = 'another_token';
		Notifications::add_subscription( $fid, $app_key, $another_url, $another_token );

		// Verify the subscription was not updated (early return).
		$final_subscription = Storage::get_subscription_by_fid_and_app_key( $fid, $app_key );
		$this->assertNotEmpty( $final_subscription );
		$this->assertEquals( 'active', $final_subscription['status'] );
		$this->assertEquals( $new_token, $final_subscription['token'] );
		$this->assertEquals( $new_url, $final_subscription['app_url'] );
		$this->assertEquals( $subscription['id'], $final_subscription['id'] );
	}

	/**
	 * Test remove_subscription_by_token with non-existent subscription.
	 */
	public function test_remove_subscription_by_token_nonexistent() {
		// Try to remove a subscription with a token that doesn't exist.
		$nonexistent_token = 'nonexistent_token';
		
		// This should not throw any errors and should return silently.
		Notifications::remove_subscription_by_token( $nonexistent_token );

		// Verify that we can still add and remove a subscription after attempting to remove a nonexistent one.
		$fid     = 12345;
		$app_key = 'test_app_key';
		$app_url = 'https://example.com';
		$token   = 'test_token';

		// Add a subscription.
		Notifications::add_subscription( $fid, $app_key, $app_url, $token );

		// Verify it was added.
		$subscription = Storage::get_subscription_by_token( $token );
		$this->assertNotEmpty( $subscription );
		$this->assertEquals( 'active', $subscription['status'] );

		// Remove it.
		Notifications::remove_subscription_by_token( $token );

		// Verify it was deactivated.
		$updated_subscription = Storage::get_subscription_by_token( $token );
		$this->assertEquals( 'inactive', $updated_subscription['status'] );
	}

	/**
	 * Test remove_subscription with non-existent subscription.
	 */
	public function test_remove_subscription_nonexistent() {
		// Try to remove a subscription with FID and app_key that don't exist.
		$nonexistent_fid     = 99999;
		$nonexistent_app_key = 'nonexistent_app_key';
		
		// This should return success even though nothing was updated.
		$result = Notifications::process_frame_removed(
			array(
				'fid' => $nonexistent_fid,
				'key' => $nonexistent_app_key,
			)
		);

		$this->assertTrue( $result['success'] );

		// Verify that we can still add and remove a subscription after attempting to remove a nonexistent one.
		$fid     = 12345;
		$app_key = 'test_app_key';
		$app_url = 'https://example.com';
		$token   = 'test_token';

		// Add a subscription.
		Notifications::add_subscription( $fid, $app_key, $app_url, $token );

		// Verify it was added.
		$subscription = Storage::get_subscription_by_fid_and_app_key( $fid, $app_key );
		$this->assertNotEmpty( $subscription );
		$this->assertEquals( 'active', $subscription['status'] );

		// Remove it.
		$result = Notifications::process_frame_removed(
			array(
				'fid' => $fid,
				'key' => $app_key,
			)
		);

		$this->assertTrue( $result['success'] );

		// Verify it was deactivated.
		$updated_subscription = Storage::get_subscription_by_fid_and_app_key( $fid, $app_key );
		$this->assertEquals( 'inactive', $updated_subscription['status'] );
	}

	/**
	 * Test process_webhook throws exception for invalid event.
	 */
	public function test_process_webhook_invalid_event() {
		$header = array(
			'fid' => 12345,
			'key' => 'test_app_key',
		);

		$payload = array(
			'event'               => 'invalid_event',
			'notificationDetails' => array(
				'url'   => 'https://example.com',
				'token' => 'test_token',
			),
		);

		$signature = 'test_signature';

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid event: invalid_event' );

		Notifications::process_webhook( $header, $payload, $signature );
	}

	/**
	 * Test log_error behavior with filter.
	 */
	public function test_log_error_with_filter() {
		// Set up error log capture.
		$error_log_file     = tmpfile();
		$error_log_path     = stream_get_meta_data( $error_log_file )['uri'];
		$original_error_log = ini_get( 'error_log' );
		// phpcs:ignore WordPress.PHP.IniSet.Risky
		ini_set( 'error_log', $error_log_path );

		// Make sure the filter returns false by default.
		add_filter( 'farcaster_wp_log_notification_info_as_errors', '__return_false' );

		// First test with filter returning false.
		$header = array(
			'fid' => 12345,
			'key' => 'test_app_key',
		);

		$payload = array(
			'event'               => 'frame_added',
			'notificationDetails' => array(
				'url'   => 'https://example.com',
				'token' => 'test_token',
			),
		);

		$signature = 'test_signature';

		Notifications::process_webhook( $header, $payload, $signature );

		// Get the error log contents.
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		$error_log_contents = file_get_contents( $error_log_path );
		$this->assertStringNotContainsString( 'Processing webhook event:', $error_log_contents, 'Error log should not contain webhook processing messages when filter returns false.' );

		// Now test with filter returning true.
		remove_filter( 'farcaster_wp_log_notification_info_as_errors', '__return_false' );
		add_filter( 'farcaster_wp_log_notification_info_as_errors', '__return_true' );

		Notifications::process_webhook( $header, $payload, $signature );

		// Get the error log contents again.
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		$error_log_contents = file_get_contents( $error_log_path );
		$this->assertStringContainsString( 'Processing webhook event: frame_added', $error_log_contents, 'Error log should contain webhook processing messages when filter returns true.' );

		// Test array logging - check for array values in the print_r format.
		$this->assertStringContainsString( '[fid] => 12345', $error_log_contents );
		$this->assertStringContainsString( '[key] => test_app_key', $error_log_contents );

		// Clean up.
		remove_filter( 'farcaster_wp_log_notification_info_as_errors', '__return_true' );
		// phpcs:ignore WordPress.PHP.IniSet.Risky
		ini_set( 'error_log', $original_error_log );
		fclose( $error_log_file );
	}
}
