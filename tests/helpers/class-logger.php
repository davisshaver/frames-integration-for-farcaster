<?php
/**
 * Helper class for logging in PHPUnit tests.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

/**
 * PHPUnit logger
 */
class Logger {

	/**
	 * Log an info message.
	 *
	 * @param string $message Message to write.
	 *
	 * @return void
	 */
	public function info( $message ) {
		// phpcs:ignore
		print $message;
	}

	/**
	 * Log a success message.
	 *
	 * @param string $message Message to write.
	 *
	 * @return void
	 */
	public function success( $message ) {
		// phpcs:ignore
		print $message;
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Message to write.
	 *
	 * @return void
	 */
	public function warning( $message ) {
		// phpcs:ignore
		print $message;
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Message to write.
	 *
	 * @return void
	 */
	public function error( $message ) {
		// phpcs:ignore
		print $message;
	}

	/**
	 * Log an error message.
	 *
	 * @param array $message_lines Message lines to write.
	 *
	 * @return void
	 */
	public function error_multi_line( $message_lines ) {
		$message = implode( "\n", $message_lines );

		// phpcs:ignore
		print $message;
	}
}
