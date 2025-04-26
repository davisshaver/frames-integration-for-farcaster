<?php
/**
 * Frames Integration for Farcaster plugin frames handling.
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
		add_action( 'wp_footer', array( __CLASS__, 'action_wp_footer' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'action_add_image_size' ) );
	}

	/**
	 * Add image sizes.
	 */
	public static function action_add_image_size() {
		add_image_size( 'farcaster-wp-splash-image', 200, 200, array( 'center', 'center' ) );
		add_image_size( 'farcaster-wp-frame-image', 2400, 1600, array( 'center', 'center' ) );
		add_image_size( 'farcaster-wp-hero-image', 1200, 630, array( 'center', 'center' ) );
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
	 * Get frame image URL from settings.
	 *
	 * @param array $options Plugin options.
	 * @return string Frame image URL or empty string if not set.
	 */
	public static function get_frame_image_url( $options ) {
		$frame_image = $options['fallback_image'] ?? '';
		
		if ( ! empty( $frame_image ) && ! empty( $frame_image['id'] ) ) {
			$frame_image_src = wp_get_attachment_image_src( $frame_image['id'], 'farcaster-wp-frame-image' );
			if ( ! empty( $frame_image_src ) ) {
				return $frame_image_src[0];
			}
		}
		
		return '';
	}

	/**
	 * Get button text from settings.
	 *
	 * @param array $options Plugin options.
	 * @return string Button text.
	 */
	public static function get_button_text( $options ) {
		return $options['button_text'] ?? __( 'Read More', 'frames-integration-for-farcaster' );
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
	 * Get no index value from settings.
	 *
	 * @param array $options Plugin options.
	 * @return boolean No index value.
	 */
	public static function get_no_index( $options ) {
		return $options['no_index'] ?? false;
	}

	/**
	 * Get tagline from settings.
	 *
	 * @param array $options Plugin options.
	 * @return string Tagline.
	 */
	public static function get_tagline( $options ) {
		return $options['tagline'] ?? '';
	}

	/**
	 * Get description from settings.
	 *
	 * @param array $options Plugin options.
	 * @return string Description.
	 */
	public static function get_description( $options ) {
		return $options['description'] ?? '';
	}

	/**
	 * Get category from settings.
	 *
	 * @param array $options Plugin options.
	 * @return string Category.
	 */
	public static function get_category( $options ) {
		return $options['category'] ?? '';
	}

	/**
	 * Get hero image from settings.
	 *
	 * @param array $options Plugin options.
	 * @return string Hero image URL.
	 */
	public static function get_hero_image( $options ) {
		$frame_image = $options['hero_image'] ?? '';
		
		if ( ! empty( $frame_image ) && ! empty( $frame_image['id'] ) ) {
			$frame_image_src = wp_get_attachment_image_src( $frame_image['id'], 'farcaster-wp-hero-image' );
			if ( ! empty( $frame_image_src ) ) {
				return $frame_image_src[0];
			}
		}

		return '';
	}

	/**
	 * Get tags from settings.
	 *
	 * @param array $options Plugin options.
	 * @return array Tags.
	 */
	public static function get_tags( $options ) {
		return $options['tags'] ?? array();
	}

	/**
	 * Get OG title from settings.
	 *
	 * @param array $options Plugin options.
	 * @return string OG title.
	 */
	public static function get_og_title( $options ) {
		return $options['og_title'] ?? '';
	}

	/**
	 * Get OG description from settings.
	 *
	 * @param array $options Plugin options.
	 * @return string OG description.
	 */
	public static function get_og_description( $options ) {
		return $options['og_description'] ?? '';
	}

	/**
	 * Get OG image from settings.
	 *
	 * @param array $options Plugin options.
	 * @return string OG image URL.
	 */
	public static function get_og_image_url( $options ) {
		$frame_image = $options['og_image'] ?? '';
		
		if ( ! empty( $frame_image ) && ! empty( $frame_image['id'] ) ) {
			$frame_image_src = wp_get_attachment_image_src( $frame_image['id'], 'farcaster-wp-hero-image' );
			if ( ! empty( $frame_image_src ) ) {
				return $frame_image_src[0];
			}
		}

		return '';
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
			$button_text = $options['button_text'] ?? __( 'Read More', 'frames-integration-for-farcaster' );
		} else {
			$title       = ! is_front_page() && is_singular() ? get_the_title() : get_bloginfo( 'name' );
			$button_text = strlen( $title ) > 29 ? substr( $title, 0, 29 ) . '...' : $title;
		}
		
		$splash_image_url        = self::get_splash_image_url( $options );
		$splash_background_color = self::get_splash_background_color( $options );

		$frame_image = is_singular() ? get_the_post_thumbnail_url( null, 'farcaster-wp-frame-image' ) : '';
		if ( empty( $frame_image ) ) {
			$frame_image = self::get_frame_image_url( $options );
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

			$asset_file = dirname( plugin_dir_path( __FILE__ ) ) . '/build/sdk.asset.php';

			if ( ! file_exists( $asset_file ) ) {
				return;
			}
		
			$asset = include $asset_file;
	
			wp_enqueue_script(
				'farcaster-frame-sdk',
				plugins_url( 'build/sdk.js', plugin_dir_path( __FILE__ ) ),
				$asset['dependencies'],
				$asset['version'],
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);

			$cast_text = ! is_front_page() && is_singular() ?
				sprintf( 'I\'m reading \'%s\' by %s', get_the_title(), get_bloginfo( 'name' ) ) :
				sprintf( 'I\'m reading %s', get_bloginfo( 'name' ) );

			wp_localize_script(
				'farcaster-frame-sdk',
				'farcasterWP',
				array(
					'notificationsEnabled' => $notifications_enabled,
					'debugEnabled'         => $options['debug_enabled'] ?? false,
					'castText'             => $cast_text,
					'tippingAddress'       => $options['tipping_address'] ?? '',
					'tippingAmounts'       => $options['tipping_amounts'] ?? array(),
					'tippingChains'        => $options['tipping_chains'] ?? array(),
				)
			);

			wp_register_style(
				'farcaster-frame-sdk',
				plugins_url( '../build/sdk.css', __FILE__ ),
				false,
				$asset['version']
			);
			wp_enqueue_style( 'farcaster-frame-sdk' );
		}
	}

	/**
	 * Add tipping modal container to footer if enabled.
	 */
	public static function action_wp_footer() {
		$options         = get_option( 'farcaster_wp', array() );
		$tipping_enabled = $options['tipping_enabled'] ?? false;

		if ( $tipping_enabled ) {
			echo '<div id="farcaster-wp-tipping-modal"></div>';
		}
	}
}
