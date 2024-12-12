<?php
/**
 * WP Farcaster plugin administration screen handling.
 *
 * @package WP_Farcaster
 */

namespace WP_Farcaster;

/**
 * Class to handle the plugin admin pages
 */
class Admin {

	const WP_FARCASTER_PAGE_SLUG = 'wp-farcaster';

	/**
	 * Runs the initialization.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'action_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'action_admin_enqueue_scripts' ) );
		add_action( 'init', array( __CLASS__, 'action_init' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $admin_page The admin page slug.
	 * @return void
	 */
	public static function action_admin_enqueue_scripts( $admin_page ) {
		if ( 'settings_page_wp-farcaster' !== $admin_page ) {
			return;
		}
	
		$asset_file = dirname( plugin_dir_path( __FILE__ ) ) . '/build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}
	
		$asset = include $asset_file;

		wp_enqueue_script(
			'wp-farcaster-script',
			plugins_url( 'build/index.js', plugin_dir_path( __FILE__ ) ),
			$asset['dependencies'],
			$asset['version'],
			array(
				'in_footer' => true,
			)
		);

		wp_enqueue_style(
			'wp-farcaster-style',
			plugins_url( 'build/index.css', plugin_dir_path( __FILE__ ) ),
			array_filter(
				$asset['dependencies'],
				function ( $style ) {
					return wp_style_is( $style, 'registered' );
				}
			),
			$asset['version']
		);

		wp_enqueue_media();
	}

	/**
	 * Add the WP Farcaster settings page to the admin menu.
	 * 
	 * @return void
	 */
	public static function action_admin_menu() {
		add_options_page(
			__( 'Farcaster', 'wp-farcaster' ),
			__( 'Farcaster', 'wp-farcaster' ),
			'manage_options',
			self::WP_FARCASTER_PAGE_SLUG,
			[ __CLASS__, 'render_options_page' ]
		);
	}

	/**
	 * Render the WP Farcaster settings page.
	 * 
	 * @return void
	 */
	public static function render_options_page() {
		printf(
			'<div class="wrap" id="wp-farcaster-settings">%s</div>',
			esc_html__( 'Loading…', 'wp-farcaster' )
		);
	}

	/**
	 * Register the settings.
	 * 
	 * @return void
	 */
	public static function action_init() {
		$default = array(
			'message'                 => __( 'Hello, World!', 'wp-farcaster' ),
			'display'                 => true,
			'size'                    => 'medium',
			'frames_enabled'          => false,
			'splash_background_color' => '#ffffff',
			'button_text'             => __( 'Read More', 'wp-farcaster' ),
			'splash_image'            => array(
				'id'  => 0,
				'url' => '',
			),
			'fallback_image'          => array(
				'id'  => 0,
				'url' => '',
			),
		);
		$schema  = array(
			'type'       => 'object',
			'properties' => array(
				'frames_enabled'           => array(
					'type' => 'boolean',
				),
				'splash_background_color'  => array(
					'type' => 'string',
				),
				'splash_image'             => array(
					'type'       => 'object',
					'properties' => array(
						'id'  => array(
							'type' => 'integer',
						),
						'url' => array(
							'type' => 'string',
						),
					),
				),
				'fallback_image'           => array(
					'type'       => 'object',
					'properties' => array(
						'id'  => array(
							'type' => 'integer',
						),
						'url' => array(
							'type' => 'string',
						),
					),
				),
				'use_title_as_button_text' => array(
					'type' => 'boolean',
				),
				'button_text'              => array(
					'type'      => 'string',
					'maxLength' => 32,
				),
				'message'                  => array(
					'type' => 'string',
				),
				'display'                  => array(
					'type' => 'boolean',
				),
				'size'                     => array(
					'type' => 'string',
					'enum' => array(
						'small',
						'medium',
						'large',
						'x-large',
					),
				),
			),
		);
	
		register_setting(
			'options',
			'wp_farcaster',
			array(
				'type'         => 'object',
				'default'      => $default,
				'show_in_rest' => array(
					'schema' => $schema,
				),
			)
		);
	}
}
