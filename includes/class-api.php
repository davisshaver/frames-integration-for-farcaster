<?php
/**
 * Frames Integration for Farcaster API setup.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

use Farcaster_WP\API\Manifest_Controller;
use Farcaster_WP\API\Webhook_Controller;
use Farcaster_WP\API\Subscriptions_Controller;
use Farcaster_WP\API\Events_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the API as a whole.
 */
class API {

	/**
	 * Load up and register the endpoints.
	 */
	public static function init() {
		include_once 'api/class-manifest-controller.php';
		$manifest_api = new Manifest_Controller();
		add_action( 'rest_api_init', [ $manifest_api, 'register_routes' ] );

		include_once 'api/class-webhook-controller.php';
		$webhook_api = new Webhook_Controller();
		add_action( 'rest_api_init', [ $webhook_api, 'register_routes' ] );

		include_once 'api/class-subscriptions-controller.php';
		$subscriptions_api = new Subscriptions_Controller();
		add_action( 'rest_api_init', [ $subscriptions_api, 'register_routes' ] );

		include_once 'api/class-events-controller.php';
		$events_api = new Events_Controller();
		add_action( 'rest_api_init', [ $events_api, 'register_routes' ] );
	}
}
