<?php
/**
 * Farcaster WP plugin initialization.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

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
