<?php
/**
 * Farcaster WP plugin frames handling.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

/**
 * Class to handle Farcaster frames integration.
 */
class Frames {

	/**
	 * Runs the initialization.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'action_enqueue_scripts' ) );
		add_action( 'wp_head', array( __CLASS__, 'action_wp_head' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'action_add_image_size' ) );
	}

	/**
	 * Add image sizes.
	 */
	public static function action_add_image_size() {
		add_image_size( 'farcaster-wp-splash-image', 200, 200, array( 'center', 'center' ) );
		add_image_size( 'farcaster-wp-frame-image', 2400, 1600, array( 'center', 'center' ) );
	}

	/**
	 * Get splash image URL from settings.
	 *
	 * @param array $options Plugin options.
	 * @return string Splash image URL or empty string if not set.
	 */
	public static function get_splash_image_url( $options ) {
		$splash_image = $options['splash_image'] ?? '';
		
		if ( ! empty( $splash_image ) && ! empty( $splash_image['id'] ) ) {
			$splash_image_src = wp_get_attachment_image_src( $splash_image['id'], 'farcaster-wp-splash-image' );
			if ( ! empty( $splash_image_src ) ) {
				return $splash_image_src[0];
			}
		}
		
		return '';
	}

	/**
	 * Get splash background color from settings.
	 *
	 * @param array $options Plugin options.
	 * @return string Background color hex value.
	 */
	public static function get_splash_background_color( $options ) {
		return $options['splash_background_color'] ?? '#ffffff';
	}

	/**
	 * Generate Farcaster frame data for the current page.
	 *
	 * @return array|null Frame data array or null if frames are disabled.
	 */
	public static function get_frame_data() {
		$options = get_option( 'farcaster_wp', array() );
		
		if ( empty( $options['frames_enabled'] ) ) {
			return null;
		}

		global $wp;
		
		$use_title_as_button_text = $options['use_title_as_button_text'] ?? false;
		if ( empty( $use_title_as_button_text ) ) {
			$button_text = $options['button_text'] ?? __( 'Read More', 'farcaster-wp' );
		} else {
			$title       = ! is_front_page() && is_singular() ? get_the_title() : get_bloginfo( 'name' );
			$button_text = mb_substr( $title, 0, 32 );
		}
		
		$splash_image_url        = self::get_splash_image_url( $options );
		$splash_background_color = self::get_splash_background_color( $options );

		$frame_image = is_singular() ? get_the_post_thumbnail_url( null, 'farcaster-wp-frame-image' ) : '';
		if ( empty( $frame_image ) ) {
			$fallback_image = $options['fallback_image'] ?? '';
			if ( ! empty( $fallback_image ) && ! empty( $fallback_image['id'] ) ) {
				$frame_image_src = wp_get_attachment_image_src( $fallback_image['id'], 'farcaster-wp-frame-image' );
				if ( ! empty( $frame_image_src ) ) {
					$frame_image = $frame_image_src[0];
				}
			}
		}

		$url = is_singular() ? get_permalink() : home_url( $wp->request );

		return array(
			'version'  => 'next',
			'imageUrl' => ! empty( $frame_image ) ? $frame_image : '',
			'button'   => array(
				'title'  => $button_text,
				'action' => array(
					'type'                  => 'launch_frame',
					'name'                  => get_bloginfo( 'name' ),
					'url'                   => $url,
					'splashImageUrl'        => $splash_image_url,
					'splashBackgroundColor' => $splash_background_color,
				),
			),
		);
	}

	/**
	 * Add Farcaster frame meta tag to head.
	 */
	public static function action_wp_head() {
		$frame_data = self::get_frame_data();
		
		if ( $frame_data ) {
			printf(
				'<meta name="fc:frame" content="%s" />' . PHP_EOL,
				esc_attr( wp_json_encode( $frame_data ) )
			);
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public static function action_enqueue_scripts() {
		$options               = get_option( 'farcaster_wp', array() );
		$notifications_enabled = $options['notifications_enabled'] ?? false;

		// Only enqueue if frames are enabled in settings.
		if ( ! empty( $options['frames_enabled'] ) ) {
			wp_enqueue_script(
				'farcaster-frame-sdk',
				plugins_url( 'build/sdk.js', plugin_dir_path( __FILE__ ) ),
				array(),
				FARCASTER_WP_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
			wp_localize_script(
				'farcaster-frame-sdk',
				'farcasterWP',
				array(
					'notificationsEnabled' => $notifications_enabled,
				)
			);
		}
	}
}
