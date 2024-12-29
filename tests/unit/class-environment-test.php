<?php
/**
 * Environment sanity checks.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP\Tests;

use WP_UnitTestCase;

/**
 * These tests prove test setup works.
 *
 * They are useful for debugging.
 */
class Environment_Test extends WP_UnitTestCase {

	/**
	 * Most basic test possible.
	 */
	public function testSomething() {
		$this->assertIsBool( true );
	}
}
