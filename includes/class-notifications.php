<?php
/**
 * Frames Integration for Farcaster plugin notifications handling.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

use Farcaster_WP\Storage;

/**
 * Class to handle Farcaster notifications integration.
 */
class Notifications {

	/**
	 * Runs the initialization.
	 */
	public static function init() {
		if ( ! self::are_enabled() ) {
			return;
		}

		add_action( 'transition_post_status', array( __CLASS__, 'action_transition_post_status' ), 10, 3 );
		add_action( 'farcaster_wp_send_publish_post_notifications', array( __CLASS__, 'send_publish_post_notifications' ), 10, 1 );
		add_action( 'farcaster_wp_retry_notifications', array( __CLASS__, 'retry_notifications' ), 10, 3 );
	}

	/**
	 * Check if notifications are enabled.
	 *
	 * @return bool True if notifications are enabled, false otherwise.
	 */
	public static function are_enabled() {
		$options               = get_option( 'farcaster_wp', array() );
		$notifications_enabled = $options['notifications_enabled'] ?? false;
		return $notifications_enabled;
	}

	/**
	 * Handle the transition post status action for published posts.
	 *
	 * @param string $new_status The new post status.
	 * @param string $old_status The old post status.
	 * @param object $post The post object.
	 */
	public static function action_transition_post_status( $new_status, $old_status, $post ) {
		if ( 'publish' === $new_status && 'publish' !== $old_status && 'post' === $post->post_type ) {
			self::schedule_publish_post_notifications( $post->ID );
		}
	}

	/**
	 * Schedule the notifications.
	 *
	 * @param int $post_id The post ID.
	 */
	public static function schedule_publish_post_notifications( $post_id ) {
		wp_schedule_single_event( time(), 'farcaster_wp_send_publish_post_notifications', array( $post_id ) );
	}

	/**
	 * Send the notifications.
	 *
	 * @param int $post_id The post ID.
	 */
	public static function send_publish_post_notifications( $post_id ) {
		$post = get_post( $post_id );
		if ( ! empty( $post ) ) {
			$suppress_notifications = get_post_meta( $post_id, 'farcaster_wp_suppress_notifications', true );
			if ( ! empty( $suppress_notifications ) ) {
				self::log_error( 'Notifications suppressed for post ' . $post_id );
				return;
			}
			self::initiate_notifications( $post );
		}
	}

	/**
	 * Get the notification ID.
	 *
	 * @param int $post_id The post ID.
	 * @return string The notification ID.
	 */
	public static function get_notification_id( $post_id ) {
		$blog_name = str_replace( '-', '_', sanitize_title_with_dashes( get_bloginfo( 'name' ) ) );
		return 'farcaster_wp_notification_' . $post_id . '_' . $blog_name;
	}

	/**
	 * Get the tokens by app url.
	 *
	 * @return array The tokens by app url.
	 */
	public static function get_tokens_by_app_url() {
		$subscriptions = Storage::get_active_subscriptions();
		$tokens_by_url = array();
		foreach ( $subscriptions as $subscription ) {
			$tokens_by_url[ $subscription['app_url'] ][] = $subscription['token'];
		}
		return $tokens_by_url;
	}

	/**
	 * Retry notifications.
	 *
	 * @param string $app_url The URL.
	 * @param array  $tokens The tokens.
	 * @param int    $post_id The post ID.
	 */
	public static function retry_notifications( $app_url, $tokens, $post_id ) {
		$notification_body = self::get_notification_body( $post_id );
		self::chunk_and_send_notifications( $tokens, $notification_body, $app_url, $post_id );
	}

	/**
	 * Send the notifications.
	 *
	 * @param object $post The post object.
	 */
	public static function initiate_notifications( $post ) {
		$tokens_by_app = self::get_tokens_by_app_url();
		self::send_notifications_by_app( $tokens_by_app, $post->ID );
	}

	/**
	 * Get the notification body.
	 *
	 * @param int $post_id The post ID.
	 * @return array The notification body.
	 */
	public static function get_notification_body( $post_id ) {
		$stripped_title = substr( wp_strip_all_tags( get_the_title( $post_id ) ), 0, 32 );
		$title          = strlen( $stripped_title ) > 29 ? substr( $stripped_title, 0, 29 ) . '...' : $stripped_title;
		return array(
			'notificationId' => self::get_notification_id( $post_id ),
			'title'          => $title,
			'body'           => substr( wp_strip_all_tags( get_the_excerpt( $post_id ) ), 0, 128 ),
			'targetUrl'      => get_permalink( $post_id ),
		);
	}

	/**
	 * Send the notifications by app.
	 *
	 * @param array $tokens_by_app The tokens by app.
	 * @param int   $post_id The post ID.
	 */
	public static function send_notifications_by_app( $tokens_by_app, $post_id ) {
		$notification_body = self::get_notification_body( $post_id );
		foreach ( $tokens_by_app as $app_url => $tokens ) {
			self::chunk_and_send_notifications( $tokens, $notification_body, $app_url, $post_id );
		}
	}

	/**
	 * Log Frames Integration for Farcaster notifications errors.
	 *
	 * @param mixed $data The data to log.
	 */
	private static function log_error( $data ) {
		if ( ! apply_filters( 'farcaster_wp_log_notification_info_as_errors', false ) ) {
			return;
		}
		if ( is_array( $data ) || is_object( $data ) ) {
			// phpcs:ignore
			error_log( print_r( $data, true ) );
		} else {
			// phpcs:ignore
			error_log( $data );
		}
	}

	/**
	 * Send the notification.
	 *
	 * @param string $app_url The URL.
	 * @param array  $notification_body The notification body.
	 * @param int    $post_id The post ID.
	 * @return array The response.
	 */
	public static function send_notification( $app_url, $notification_body, $post_id ) {
		self::log_error( 'Sending notification to ' . $app_url );
		self::log_error( $notification_body );
		$response = wp_safe_remote_post(
			$app_url,
			array(
				'body'    => wp_json_encode( $notification_body ),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			) 
		);
		if ( is_wp_error( $response ) ) {
			$admin_email   = get_option( 'admin_email' );
			$error_message = $response->get_error_message();

			self::log_error( 'Notification error: ' . $error_message );

			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail
			wp_mail(
				$admin_email,
				'Farcaster Notification Error',
				sprintf(
					'There was an error sending notifications: %s. A retry has been scheduled.',
					$error_message
				)
			);

			$unsent_tokens = $notification_body['tokens'];
			wp_schedule_single_event(
				time() + 300,
				'farcaster_wp_retry_notifications',
				array(
					$app_url,
					$unsent_tokens,
					$post_id,
				)
			);
			return array(); // Return empty array since request failed.
		}
		$response = json_decode( wp_remote_retrieve_body( $response ), true );
		self::log_error( $response );
		return $response;
	}

	/**
	 * Chunk and send the notifications.
	 *
	 * @param array  $tokens The tokens.
	 * @param array  $notification_body The notification body.
	 * @param string $app_url The URL.
	 * @param int    $post_id The post ID.
	 */
	public static function chunk_and_send_notifications( $tokens, $notification_body, $app_url, $post_id ) {
		$chunks = array_chunk( $tokens, 100 );
		foreach ( $chunks as $chunk ) {
			$notification_body['tokens'] = $chunk;
			$webhook_response            = self::send_notification( $app_url, $notification_body, $post_id );

			if ( ! empty( $webhook_response['result'] ) ) {
				// Add succesful tokens to post meta.
				if ( ! empty( $webhook_response['result']['successfulTokens'] ) ) {
					$current_tokens = get_post_meta( $post_id, 'farcaster_wp_tokens', true );
					$current_tokens = is_array( $current_tokens ) ? $current_tokens : array();
					$current_tokens = array_merge( $current_tokens, $webhook_response['result']['successfulTokens'] );
					update_post_meta( $post_id, 'farcaster_wp_tokens', $current_tokens );
				}

				// Process invalid tokens and remove them from the subscription.
				if ( ! empty( $webhook_response['result']['invalidTokens'] ) ) {
					foreach ( $webhook_response['result']['invalidTokens'] as $invalid_token ) {
						self::remove_subscription_by_token( $invalid_token );
					}
				}

				// Schedule a retry for the rate limited tokens.
				if ( ! empty( $webhook_response['result']['rateLimitedTokens'] ) ) {
					$rate_limited_tokens = $webhook_response['result']['rateLimitedTokens'];
					wp_schedule_single_event(
						time() + 300,
						'farcaster_wp_retry_notifications',
						array(
							$app_url,
							$rate_limited_tokens,
							$post_id,
						)
					);
				}
			}
		}
	}

	/**
	 * Process the webhook.
	 *
	 * @param array  $header The webhook header.
	 * @param array  $payload The webhook payload.
	 * @param string $signature The webhook signature.
	 * @return array The response.
	 * @throws \Exception If the event is invalid.
	 */
	public static function process_webhook( $header, $payload, $signature ) {
		$event = $payload['event'];
		self::log_error( 'Processing webhook event: ' . $event );
		self::log_error( $header );
		self::log_error( $payload );
		self::log_error( $signature );
		Storage::record_event(
			[
				'event_type' => $event,
				'fid'        => $header['fid'],
				'timestamp'  => time(),
				'full_event' => wp_json_encode(
					array(
						'header'    => $header,
						'payload'   => $payload,
						'signature' => $signature,
					)
				),
			],
			[ '%s', '%d', '%s', '%s' ]
		);
		switch ( $event ) {
			case 'frame_added':
				return self::process_frame_added( $header, $payload );
			case 'frame_removed':
				return self::process_frame_removed( $header );
			case 'notifications_disabled':
				return self::process_notifications_disabled( $header );
			case 'notifications_enabled':
				return self::process_notifications_enabled( $header, $payload );
		}
		throw new \Exception( 'Invalid event: ' . esc_html( $event ) );
	}

	/**
	 * Add a subscription.
	 *
	 * Note: This data structure means that each FID + app key combo can only have one subscription.
	 *
	 * @param int    $fid The Farcaster ID.
	 * @param string $app_key The app key (onchainer signer) public key.
	 * @param string $app_url The URL for notifications.
	 * @param string $token The token for notifications.
	 */
	public static function add_subscription( $fid, $app_key, $app_url, $token ) {
		// Look up the FID and the app key in the custom table.
		$subscription = Storage::get_subscription_by_fid_and_app_key( $fid, $app_key );
		if ( ! empty( $subscription ) ) {
			// If the subscription exists and is not active, update the URL, token, and status.
			if ( 'active' !== $subscription['status'] ) {
				Storage::update_subscription(
					$subscription['id'],
					[
						'status'            => 'active',
						'token'             => $token,
						'updated_timestamp' => time(),
						'app_url'           => $app_url,
					] 
				);
			}
			return;
		}

		// If the fid and key don't exist, add a new subscription.
		Storage::add_subscription(
			[
				'created_timestamp' => time(),
				'fid'               => $fid,
				'app_key'           => $app_key,
				'status'            => 'active',
				'token'             => $token,
				'app_url'           => $app_url,
			],
			[ '%s', '%d', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Remove a subscription by token.
	 *
	 * @param string $token The token.
	 */
	public static function remove_subscription_by_token( $token ) {
		$subscription = Storage::get_subscription_by_token( $token );
		if ( empty( $subscription ) ) {
			return;
		}
		Storage::update_subscription(
			$subscription['id'],
			[
				'status'            => 'inactive',
				'updated_timestamp' => time(),
			],
			[ '%s', '%s' ]
		);
	}

	/**
	 * Remove a subscription.
	 *
	 * @param int    $fid The Farcaster ID.
	 * @param string $app_key The app key (onchainer signer) public key.
	 * @return array The response.
	 */
	private static function remove_subscription( $fid, $app_key ) {
		$subscription = Storage::get_subscription_by_fid_and_app_key( $fid, $app_key );
		if ( empty( $subscription ) ) {
			return [ 'success' => true ];
		}
		Storage::update_subscription(
			$subscription['id'],
			[
				'status'            => 'inactive',
				'updated_timestamp' => time(),
			],
			[ '%s', '%s' ]
		);
		return [ 'success' => true ];
	}

	/**
	 * Process the frame added event.
	 *
	 * @param array $header The webhook header.
	 * @param array $payload The webhook payload.
	 * @return array The response.
	 */
	public static function process_frame_added( $header, $payload ) {
		$fid     = $header['fid'];
		$app_key = $header['key'];
		$app_url = $payload['notificationDetails']['url'];
		$token   = $payload['notificationDetails']['token'];
		self::add_subscription( $fid, $app_key, $app_url, $token );
		return [ 'success' => true ];
	}

	/**
	 * Process the frame removed event.
	 *
	 * @param array $header The webhook header.
	 * @return array The response.
	 */
	public static function process_frame_removed( $header ) {
		$fid     = $header['fid'];
		$app_key = $header['key'];
		self::remove_subscription( $fid, $app_key );
		return [ 'success' => true ];
	}

	/**
	 * Process the notifications disabled event.
	 *
	 * @param array $header The webhook header.
	 * @return array The response.
	 */
	public static function process_notifications_disabled( $header ) {
		$fid     = $header['fid'];
		$app_key = $header['key'];
		self::remove_subscription( $fid, $app_key );
		return [ 'success' => true ];
	}

	/**
	 * Process the notifications enabled event.
	 *
	 * @param array $header The webhook header.
	 * @param array $payload The webhook payload.
	 * @return array The response.
	 */
	public static function process_notifications_enabled( $header, $payload ) {
		$fid     = $header['fid'];
		$app_key = $header['key'];
		$app_url = $payload['notificationDetails']['url'];
		$token   = $payload['notificationDetails']['token'];
		self::add_subscription( $fid, $app_key, $app_url, $token );
		return [ 'success' => true ];
	}
}
