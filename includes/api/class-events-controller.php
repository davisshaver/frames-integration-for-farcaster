<?php
/**
 * Events API endpoint
 *
 * @package Farcaster_WP\API
 */

namespace Farcaster_WP\API;

use WP_REST_Controller;
use WP_Error;
use WP_REST_Response;
use Farcaster_WP\Storage;

defined( 'ABSPATH' ) || exit;

/**
 * REST API endpoints for Farcaster events.
 */
class Events_Controller extends WP_REST_Controller {

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
	protected $resource_name = 'events';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		// Register farcaster-wp/v1/events endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->resource_name,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_events' ],
					'permission_callback' => function() {
						return current_user_can( 'manage_options' );
					},
				],
				'schema' => [ $this, 'get_events_schema' ],
			]
		);
	}

	/**
	 * Get the events.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_events() {
		$events      = Storage::get_events();
		$events_list = array();
		foreach ( $events as $event ) {
			$events_list[] = array(
				'fid'        => $event['fid'],
				'event_type' => $event['event_type'],
				'timestamp'  => $event['timestamp'],
				'full_event' => $event['full_event'],
			);
		}
		
		return new WP_REST_Response( $events_list );
	}

	/**
	 * Get the REST schema for the endpoints.
	 *
	 * @return array
	 */
	public function get_events_schema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->resource_name,
			'type'       => 'object',
			'properties' => [
				'events' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [ 'fid', 'event_type', 'timestamp', 'full_event' ],
						'properties' => [
							'fid'        => [
								'type' => 'integer',
							],
							'event_type' => [
								'type' => 'string',
							],
							'timestamp'  => [
								'type' => 'integer',
							],
							'full_event' => [
								'type' => 'string',
							],
						],
					],
				],
			],
		];
	}
}
