<?php
/**
 * Frames Integration for Farcaster auto-casting handling.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;


/**
 * Class to handle Farcaster auto-casting integration.
 */
class Casts {

	/**
	 * Runs the initialization.
	 */
	public static function init() {
		if ( ! self::are_enabled() ) {
			return;
		}

		add_action( 'transition_post_status', array( __CLASS__, 'action_transition_post_status' ), 10, 3 );
		add_action( 'farcaster_wp_cast_post', array( __CLASS__, 'action_cast_post' ), 10, 1 );
	}


	/**
	 * Check if auto-casting is enabled.
	 *
	 * @return bool True if auto-casting is enabled, false otherwise.
	 */
	public static function are_enabled() {
		$options       = get_option( 'farcaster_wp', array() );
		$casts_enabled = $options['auto_casting'] ?? false;
		return $casts_enabled;
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
			self::schedule_cast_post( $post->ID );
		}
	}


	/**
	 * Schedule the cast post.
	 *
	 * @param int $post_id The post ID.
	 */
	public static function schedule_cast_post( $post_id ) {
		wp_schedule_single_event( time(), 'farcaster_wp_cast_post', array( $post_id ) );
	}

	/**
	 * Cast the post.
	 *
	 * @param int $post_id The post ID.
	 */
	public static function action_cast_post( $post_id ) {
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
}
