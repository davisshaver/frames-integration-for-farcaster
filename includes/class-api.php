<?php
/**
 * Frames Integration for Farcaster API setup.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

use Farcaster_WP\API\Manifest_Controller;

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
	}
}
