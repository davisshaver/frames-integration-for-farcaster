<?php
/**
 * Manifest API endpoint
 *
 * @package Farcaster_WP\API
 */

namespace Farcaster_WP\API;

use WP_REST_Controller;
use WP_Error;
use WP_REST_Response;
use Farcaster_WP\Frames;
use Farcaster_WP\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * REST API endpoints for Farcaster manifest.
 */
class Manifest_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = FARCASTER_WP_API_NAMESPACE;

	/**
	 * Endpoint resource.
	 *
	 * @var string
	 */
	protected $resource_name = 'manifest';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		// Register farcaster-wp/v1/manifest endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->resource_name,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_manifest' ],
					'permission_callback' => function() {
						return current_user_can( 'manage_options' );
					},
				],
				'schema' => [ $this, 'get_manifest_schema' ],
			]
		);
	}

	/**
	 * Get the manifest.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_manifest() {
		$options                 = get_option( 'farcaster_wp', array() );
		$splash_background_color = Frames::get_splash_background_color( $options );
		$splash_image_url        = Frames::get_splash_image_url( $options );
		$domain_manifest         = json_decode( $options['domain_manifest'], true );
		$frame_image_url         = Frames::get_frame_image_url( $options );
		$button_title            = Frames::get_button_text( $options );
		$no_index                = Frames::get_no_index( $options );
		$tagline                 = Frames::get_tagline( $options );
		$description             = Frames::get_description( $options );
		$category                = Frames::get_category( $options );
		$hero_image              = Frames::get_hero_image( $options );

		$header                  = '';
		if ( ! empty( $domain_manifest['accountAssociation']['header'] ) ) {
			$header = $domain_manifest['accountAssociation']['header'];
		} elseif ( ! empty( $domain_manifest['header'] ) ) {
			$header = $domain_manifest['header'];
		}

		$payload = '';
		if ( ! empty( $domain_manifest['accountAssociation']['payload'] ) ) {
			$payload = $domain_manifest['accountAssociation']['payload'];
		} elseif ( ! empty( $domain_manifest['payload'] ) ) {
			$payload = $domain_manifest['payload'];
		}

		$signature = '';
		if ( ! empty( $domain_manifest['accountAssociation']['signature'] ) ) {
			$signature = $domain_manifest['accountAssociation']['signature'];
		} elseif ( ! empty( $domain_manifest['signature'] ) ) {
			$signature = $domain_manifest['signature'];
		}


		$manifest = [
			'accountAssociation' => [
				'header'    => $header,
				'payload'   => $payload,
				'signature' => $signature,
			],
			'frame'              => [
				'version'               => '1',
				'name'                  => get_bloginfo( 'name' ),
				'homeUrl'               => get_home_url(),
				'iconUrl'               => get_site_icon_url(),
				'splashImageUrl'        => $splash_image_url,
				'splashBackgroundColor' => $splash_background_color,
				// Deprecated.
				'imageUrl'              => $frame_image_url,
				// Deprecated.
				'buttonTitle'           => $button_title,
				'subtitle'              => get_bloginfo( 'description' ),
				'description'           => $description,
				'primaryCategory'       => $category,
				'tags'                  => [],
				'heroImageUrl'          => $hero_image,
				'tagline'               => $tagline,
				'noindex'               => $no_index,
			],
		];

		if ( Notifications::are_enabled() ) {
			$manifest['frame']['webhookUrl'] = get_rest_url( null, FARCASTER_WP_API_NAMESPACE . '/webhook' );
		}

		return new WP_REST_Response( $manifest );
	}

	/**
	 * Get the REST schema for the endpoints.
	 *
	 * @return array
	 */
	public function get_manifest_schema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->resource_name,
			'type'       => 'object',
			'properties' => [
				'accountAssociation' => [
					'type'       => 'object',
					'required'   => [ 'header', 'payload', 'signature' ],
					'properties' => [
						'header'    => [
							'type'        => 'string',
							'description' => 'base64url encoded JFS header',
						],
						'payload'   => [
							'type'        => 'string',
							'description' => 'base64url encoded payload containing a single property `domain`',
						],
						'signature' => [
							'type'        => 'string',
							'description' => 'base64url encoded signature bytes',
						],
					],
				],
				'frame'              => [
					'type'       => 'object',
					'required'   => [ 'version', 'name', 'homeUrl' ],
					'properties' => [
						'version'               => [
							'type'     => 'string',
							'enum'     => [ '1' ],
							'required' => true,
						],
						'name'                  => [
							'type'      => 'string',
							'maxLength' => 32,
						],
						'homeUrl'               => [
							'type'      => 'string',
							'maxLength' => 1024,
							'format'    => 'uri',
						],
						'iconUrl'               => [
							'type'      => 'string',
							'maxLength' => 1024,
							'format'    => 'uri',
						],
						'splashImageUrl'        => [
							'type'      => 'string',
							'maxLength' => 1024,
							'format'    => 'uri',
						],
						'splashBackgroundColor' => [
							'type'    => 'string',
							'pattern' => '^#[0-9a-fA-F]{3,6}$',
						],
						'webhookUrl'            => [
							'type'      => 'string',
							'maxLength' => 1024,
							'format'    => 'uri',
						],
						'subtitle'              => [
							'type'      => 'string',
							'maxLength' => 30,
						],
						'description'           => [
							'type'      => 'string',
							'maxLength' => 170,
						],
						'primaryCategory'       => [
							'type' => 'string',
							'enum' => [
								'games',
								'social',
								'finance',
								'utility',
								'productivity',
								'health-fitness',
								'news-media',
								'music',
								'shopping',
								'education',
								'developer-tools',
								'entertainment',
								'art-creativity',
							],
						],
						'tags'                  => [
							'type'  => 'array',
							'items' => [
								'type'      => 'string',
								'maxLength' => 20,
							],
						],
						'heroImageUrl'        => [
							'type'      => 'string',
							'maxLength' => 1024,
							'format'    => 'uri',
						],
						'tagline'               => [
							'type'      => 'string',
							'maxLength' => 30,
						],
						'noindex'               => [
							'type'      => 'boolean',
						],
					],
				],
				'triggers'           => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [ 'type', 'id', 'url' ],
						'properties' => [
							'type' => [
								'type' => 'string',
								'enum' => [ 'cast', 'composer' ],
							],
							'id'   => [
								'type' => 'string',
							],
							'url'  => [
								'type'      => 'string',
								'maxLength' => 512,
								'format'    => 'uri',
							],
							'name' => [
								'type' => 'string',
							],
						],
					],
				],
			],
		];
	}
}
