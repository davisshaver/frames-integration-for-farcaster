<?php
/**
 * Webhook API endpoint
 *
 * @package Farcaster_WP\API
 */

namespace Farcaster_WP\API;

use WP_REST_Controller;
use WP_Error;
use WP_REST_Response;
use WP_REST_Request;
use Farcaster_WP\Notifications;
use Farcaster_WP\Signature_Verifier;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * REST API endpoints for Farcaster notifications webhook.
 */
class Webhook_Controller extends WP_REST_Controller {

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
	protected $resource_name = 'webhook';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		// Register farcaster-wp/v1/webhook endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->resource_name,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'process_webhook' ],
					'validate_callback'   => [ $this, 'validate_webhook' ],
					'permission_callback' => '__return_true',
				],
				'schema' => [ $this, 'get_webhook_schema' ],
			]
		);
	}

	/**
	 * Process the webhook.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function process_webhook( $request ) {
		$body    = $request->get_body();
		$data    = json_decode( $body, true );
		$header  = json_decode( base64_decode( $data['header'] ), true );
		$payload = json_decode( base64_decode( $data['payload'] ), true );
		// Don't decode the signature.
		$signature = $data['signature'];
		$response  = Notifications::process_webhook( $header, $payload, $signature );
		return new WP_REST_Response( $response );
	}

	/**
	 * Validate the webhook.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool True if the webhook is valid, false otherwise.
	 */
	public function validate_webhook( $request ) {
		$body = $request->get_body();
		$data = json_decode( $body, true );

		if ( empty( $data['header'] ) || empty( $data['payload'] ) || empty( $data['signature'] ) ) {
			return new WP_Error( 'invalid_webhook_parameters', 'Invalid webhook parameters', [ 'status' => 400 ] );
		}

		$header  = json_decode( base64_decode( $data['header'] ), true );
		$payload = json_decode( base64_decode( $data['payload'] ), true );

		if ( empty( $header['fid'] ) || empty( $header['type'] ) || empty( $header['key'] ) ) {
			return new WP_Error( 'invalid_webhook_header', 'Invalid webhook header', [ 'status' => 400 ] );
		}

		if ( empty( $payload['event'] ) || ! in_array( $payload['event'], [ 'frame_added', 'frame_removed', 'notifications_disabled', 'notifications_enabled' ] ) ) {
			return new WP_Error( 'invalid_webhook_payload', 'Invalid webhook payload', [ 'status' => 400 ] );
		}

		// We are only handling frame_added and notifications_enabled events if they have notificationDetails.
		if (
		in_array( $payload['event'], [ 'frame_added', 'notifications_enabled' ] ) &&
		( empty( $payload['notificationDetails'] ) || empty( $payload['notificationDetails']['url'] ) || empty( $payload['notificationDetails']['token'] ) )
		) {
			return new WP_Error( 'invalid_notification_details', 'Invalid notification details', [ 'status' => 400 ] );
		}

		try {
			$is_valid = Signature_Verifier::verify( $body );
			if ( ! $is_valid ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Farcaster signature verification failed: ' . $body );
				return new WP_Error( 'signature_verification_failed', 'Signature verification failed', [ 'status' => 400 ] );
			}
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Farcaster signature verification error: ' . $e->getMessage() );
			return new WP_Error( 'signature_verification_error', 'Signature verification error', [ 'status' => 400 ] );
		}

		return true;
	}

	/**
	 * Get the REST schema for the endpoints.
	 *
	 * @return array
	 */
	public function get_webhook_schema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->resource_name,
			'type'       => 'object',
			'properties' => [
				'header'    => [
					'required' => true,
					'type'     => 'string',
				],
				'payload'   => [
					'required' => true,
					'type'     => 'string',
				],
				'signature' => [
					'required' => true,
					'type'     => 'string',
				],
			],
		];
	}
}
