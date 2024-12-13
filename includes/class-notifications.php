<?php
/**
 * Farcaster WP plugin notifications handling.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

/**
 * Class to handle Farcaster notifications integration.
 */
class Notifications {

	/**
	 * The option name for the notifications.
	 *
	 * @var string
	 */
	protected static $notifications_option_name = 'farcaster_wp_subscriptions';

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
	 * Get the tokens by url.
	 *
	 * @return array The tokens by url.
	 */
	public static function get_tokens_by_url() {
		$subscriptions = get_option( self::$notifications_option_name, array() );
		$tokens_by_url = array();
		foreach ( $subscriptions as $fid => $apps ) {
			foreach ( $apps as $app_key => $app ) {
				$tokens_by_url[ $app['url'] ][] = $app['token'];
			}
		}
		return $tokens_by_url;
	}

	/**
	 * Retry notifications.
	 *
	 * @param string $url The URL.
	 * @param array  $tokens The tokens.
	 * @param int    $post_id The post ID.
	 */
	public static function retry_notifications( $url, $tokens, $post_id ) {
		$notification_body = self::get_notification_body( $post_id );
		self::chunk_and_send_notifications( $tokens, $notification_body, $url, $post_id );
	}

	/**
	 * Send the notifications.
	 *
	 * @param object $post The post object.
	 */
	public static function initiate_notifications( $post ) {
		$tokens_by_app = self::get_tokens_by_url();
		self::send_notifications_by_app( $tokens_by_app, $post->ID );
	}

	/**
	 * Get the notification body.
	 *
	 * @param int $post_id The post ID.
	 * @return array The notification body.
	 */
	public static function get_notification_body( $post_id ) {
		return array(
			'notificationId' => self::get_notification_id( $post_id ),
			'title'          => substr( wp_strip_all_tags( get_the_title( $post_id ) ), 0, 32 ),
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
		foreach ( $tokens_by_app as $url => $tokens ) {
			self::chunk_and_send_notifications( $tokens, $notification_body, $url, $post_id );
		}
	}

	/**
	 * Send the notification.
	 *
	 * @param string $url The URL.
	 * @param array  $notification_body The notification body.
	 * @param int    $post_id The post ID.
	 * @return array The response.
	 */
	public static function send_notification( $url, $notification_body, $post_id ) {
		$response = wp_safe_remote_post( $url, array( 'body' => $notification_body ) );
		if ( is_wp_error( $response ) ) {
			$admin_email   = get_option( 'admin_email' );
			$error_message = $response->get_error_message();
			
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
					$url,
					$unsent_tokens,
					$post_id,
				)
			);
			return array(); // Return empty array since request failed.
		}
		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Chunk and send the notifications.
	 *
	 * @param array  $tokens The tokens.
	 * @param array  $notification_body The notification body.
	 * @param string $url The URL.
	 * @param int    $post_id The post ID.
	 */
	public static function chunk_and_send_notifications( $tokens, $notification_body, $url, $post_id ) {
		$chunks = array_chunk( $tokens, 100 );
		foreach ( $chunks as $chunk ) {
			$notification_body['tokens'] = $chunk;
			$webhook_response            = self::send_notification( $url, $notification_body, $post_id );

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
						self::remove_subscription_by_token( $invalid_token, $url );
					}
				}

				// Schedule a retry for the rate limited tokens.
				if ( ! empty( $webhook_response['result']['rateLimitedTokens'] ) ) {
					$rate_limited_tokens = $webhook_response['result']['rateLimitedTokens'];
					wp_schedule_single_event(
						time() + 300,
						'farcaster_wp_retry_notifications',
						array(
							$url,
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
	 * @param array $header The webhook header.
	 * @param array $payload The webhook payload.
	 * @return array The response.
	 * @throws \Exception If the event is invalid.
	 */
	public static function process_webhook( $header, $payload ) {
		$event = $payload['event'];
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
	 * @param string $key The app key (onchainer signer) public key.
	 * @param string $url The URL for notifications.
	 * @param string $token The token for notifications.
	 * @return array The response.
	 */
	public static function add_subscription( $fid, $key, $url, $token ) {
		$current_subscriptions                 = get_option( self::$notifications_option_name, array() );
		$current_subscriptions[ $fid ][ $key ] = [
			'url'   => $url,
			'token' => $token,
		];
		update_option( self::$notifications_option_name, $current_subscriptions );
		return [ 'success' => true ];
	}

	/**
	 * Remove a subscription by token.
	 *
	 * @param string $token The token.
	 * @param string $url The URL.
	 */
	public static function remove_subscription_by_token( $token, $url ) {
		$current_subscriptions = get_option( self::$notifications_option_name, array() );
		foreach ( $current_subscriptions as $fid => $apps ) {
			foreach ( $apps as $app_key => $app ) {
				if ( $app['url'] === $url && $app['token'] === $token ) {
					self::remove_subscription( $fid, $app_key );
				}
			}
		}
	}

	/**
	 * Remove a subscription.
	 *
	 * @param int    $fid The Farcaster ID.
	 * @param string $key The app key (onchainer signer) public key.
	 * @return array The response.
	 */
	private static function remove_subscription( $fid, $key ) {
		$current_subscriptions = get_option( self::$notifications_option_name, array() );
		if ( ! empty( $current_subscriptions[ $fid ] ) && ! empty( $current_subscriptions[ $fid ][ $key ] ) ) {
			unset( $current_subscriptions[ $fid ][ $key ] );
			update_option( self::$notifications_option_name, $current_subscriptions );
		}
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
		$fid   = $header['fid'];
		$key   = $header['key'];
		$url   = $payload['notificationDetails']['url'];
		$token = $payload['notificationDetails']['token'];
		return self::add_subscription( $fid, $key, $url, $token );
	}

	/**
	 * Process the frame removed event.
	 *
	 * @param array $header The webhook header.
	 * @return array The response.
	 */
	public static function process_frame_removed( $header ) {
		$fid = $header['fid'];
		$key = $header['key'];
		return self::remove_subscription( $fid, $key );
	}

	/**
	 * Process the notifications disabled event.
	 *
	 * @param array $header The webhook header.
	 * @return array The response.
	 */
	public static function process_notifications_disabled( $header ) {
		$fid                   = $header['fid'];
		$key                   = $header['key'];
		$current_subscriptions = get_option( self::$notifications_option_name, array() );
		if ( ! empty( $current_subscriptions[ $fid ] ) && ! empty( $current_subscriptions[ $fid ][ $key ] ) ) {
			unset( $current_subscriptions[ $fid ][ $key ] );
			update_option( self::$notifications_option_name, $current_subscriptions );
		}
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
		$fid   = $header['fid'];
		$key   = $header['key'];
		$url   = $payload['notificationDetails']['url'];
		$token = $payload['notificationDetails']['token'];
		return self::add_subscription( $fid, $key, $url, $token );
	}
}
