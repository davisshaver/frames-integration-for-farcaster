<?php
/**
 * WP Farcaster plugin initialization.
 *
 * @package WP_Farcaster
 */

namespace WP_Farcaster;

/**
 * Class to handle the plugin initialization
 */
class Initializer {

	/**
	 * Runs the initialization.
	 */
	public static function init() {
		Admin::init();
		API::init();
		Frames::init();
	}
}
