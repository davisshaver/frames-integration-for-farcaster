<?php
/**
 * Frames Integration for Farcaster plugin administration screen handling.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

/**
 * Class to handle the plugin admin pages
 */
class Admin {

	const FARCASTER_WP_PAGE_SLUG = 'farcaster-wp';

	/**
	 * Runs the initialization.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'action_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'action_admin_enqueue_scripts' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'action_enqueue_block_editor_assets' ) );
		add_action( 'init', array( __CLASS__, 'action_init' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $admin_page The admin page slug.
	 * @return void
	 */
	public static function action_admin_enqueue_scripts( $admin_page ) {
		if ( 'settings_page_farcaster-wp' !== $admin_page ) {
			return;
		}

		$asset_file = dirname( plugin_dir_path( __FILE__ ) ) . '/build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}
	
		$asset = include $asset_file;

		wp_enqueue_script(
			'farcaster-wp-script',
			plugins_url( 'build/index.js', plugin_dir_path( __FILE__ ) ),
			$asset['dependencies'],
			$asset['version'],
			array(
				'in_footer' => true,
			)
		);

		wp_enqueue_style(
			'farcaster-wp-style',
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
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public static function action_enqueue_block_editor_assets() {
		wp_enqueue_script(
			'farcaster-wp-editor',
			plugins_url( '../build/editor.js', __FILE__ ),
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-i18n' ),
			filemtime( FARCASTER_WP_PLUGIN_DIR . 'build/editor.js' ),
			FARCASTER_WP_VERSION
		);
		wp_register_style(
			'farcaster-wp-editor',
			plugins_url( '../build/editor.css', __FILE__ ),
			false,
			FARCASTER_WP_VERSION
		);
		wp_enqueue_style( 'nativepack-editor' );
	}

	/**
	 * Add the Farcaster settings page to the admin menu.
	 * 
	 * @return void
	 */
	public static function action_admin_menu() {
		add_options_page(
			__( 'Farcaster', 'frames-integration-for-farcaster' ),
			__( 'Farcaster', 'frames-integration-for-farcaster' ),
			'manage_options',
			self::FARCASTER_WP_PAGE_SLUG,
			[ __CLASS__, 'render_options_page' ]
		);
	}

	/**
	 * Render the Farcaster settings page.
	 * 
	 * @return void
	 */
	public static function render_options_page() {
		printf(
			'<div class="wrap" id="farcaster-wp-settings">%s</div>',
			esc_html__( 'Loading…', 'frames-integration-for-farcaster' )
		);
	}

	/**
	 * Register the settings.
	 * 
	 * @return void
	 */
	public static function action_init() {
		$default = array(
			'frames_enabled'           => false,
			'splash_background_color'  => '#ffffff',
			'button_text'              => __( 'Read More', 'frames-integration-for-farcaster' ),
			'use_title_as_button_text' => false,
			'splash_image'             => array(
				'id'  => 0,
				'url' => '',
			),
			'fallback_image'           => array(
				'id'  => 0,
				'url' => '',
			),
			'domain_manifest'          => '',
			'rpc_url'                  => '',
			'notifications_enabled'    => false,
			'debug_enabled'            => false,
			'tipping_enabled'          => false,
			'tipping_address'          => '',
			'tipping_amounts'          => array(),
			'tipping_chains'           => array(
				'optimism',
				'base',
				'mainnet',
				'zora',
			),
		);
		$schema  = array(
			'type'       => 'object',
			'properties' => array(
				'frames_enabled'           => array(
					'type' => 'boolean',
				),
				'notifications_enabled'    => array(
					'type' => 'boolean',
				),
				'tipping_enabled'          => array(
					'type' => 'boolean',
				),
				'tipping_amounts'          => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'integer',
					),
				),
				'tipping_chains'           => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'tipping_address'          => array(
					'type' => 'string',
				),
				'debug_enabled'            => array(
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
				'domain_manifest'          => array(
					'type' => 'string',
				),
				'rpc_url'                  => array(
					'type' => 'string',
				),
			),
		);
	
		register_setting(
			'options',
			'farcaster_wp',
			array(
				'type'              => 'object',
				'default'           => $default,
				'show_in_rest'      => array(
					'schema' => $schema,
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);

		register_meta(
			'post',
			'farcaster_wp_suppress_notifications',
			array(
				'type'         => 'boolean',
				'single'       => true,
				'show_in_rest' => true,
			) 
		);
	}

	/**
	 * Sanitize settings array recursively.
	 *
	 * Handles nested arrays, strings, numbers, and booleans.
	 * 
	 * @param array $settings Array of settings to be sanitized.
	 * @return array The sanitized settings.
	 */
	public static function sanitize_settings( $settings ) {
		foreach ( $settings as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = self::sanitize_settings( $value );
			} elseif ( is_string( $value ) ) {
					$value = sanitize_text_field( $value );
			} elseif ( is_numeric( $value ) ) {
				$value = intval( $value );
			} else {
				$value = boolval( $value );
			}

			$settings[ $key ] = $value;
		}
		return $settings;
	}
}
