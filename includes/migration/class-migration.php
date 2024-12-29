<?php
/**
 * Migration utilities for Farcaster WP subscriptions.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

use Farcaster_WP\Storage;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Migration class.
 * Sets up CLI-based migration for subscriptions.
 */
final class Migration {
	/**
	 * Whether the script is running as a dry-run.
	 *
	 * @var Migration
	 */
	public static $is_dry_run = false;

	/**
	 * The single instance of the class.
	 *
	 * @var Migration
	 */
	protected static $instance = null;

	/**
	 * Main Migration instance.
	 * Ensures only one instance of Migration is loaded or can be loaded.
	 *
	 * @return Migration - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Runs the initialization.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'add_cli_commands' ] );
	}

	/**
	 * Register the 'farcaster-wp migrate' WP CLI command.
	 */
	public static function add_cli_commands() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		WP_CLI::add_command(
			'farcaster-wp migrate',
			[ __CLASS__, 'cli_migrate' ],
			[
				'shortdesc' => 'Migrate legacy subscriptions in options to custom table.',
				'synopsis'  => [
					[
						'type'        => 'flag',
						'name'        => 'dry-run',
						'description' => 'Whether to do a dry run.',
						'optional'    => true,
						'repeating'   => false,
					],
				],
			]
		);

		WP_CLI::add_command(
			'farcaster-wp purge-legacy-subscriptions',
			[ __CLASS__, 'cli_purge_legacy_subscriptions' ],
			[
				'shortdesc' => 'Purge legacy subscriptions in options.',
				'synopsis'  => [],
			]
		);
	}

	/**
	 * Run the 'farcaster-wp migrate' WP CLI command.
	 *
	 * @param array $args Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public static function cli_migrate( $args, $assoc_args ) {
		// If a dry run, we won't persist any data.
		self::$is_dry_run = isset( $assoc_args['dry-run'] ) ? true : false;

		if ( self::$is_dry_run ) {
			WP_CLI::log( "\n===================\n=     Dry Run     =\n===================\n" );
		}

		WP_CLI::log( "Migrating legacy subscriptions...\n" );

		$migrated_subscriptions = self::migrate_legacy_subscriptions();

		if ( 0 === count( $migrated_subscriptions ) ) {
			WP_CLI::success( 'Completed! No legacy subscriptions found.' );
		} else {
			WP_CLI::success(
				sprintf(
					'Completed! %1$s %2$s %3$s.',
					self::$is_dry_run ? 'Would have converted' : 'Converted',
					count( $migrated_subscriptions ),
					1 < count( $migrated_subscriptions ) ? 'subscriptions' : 'subscription'
				)
			);
		}
	}

	/**
	 * Convert legacy subscriptions in options to custom table.
	 *
	 * @return array Array of migrated subscriptions.
	 */
	public static function migrate_legacy_subscriptions() {
		$migrated_subscriptions = [];
		$migrated_count         = 0;
		$subscriptions          = get_option( 'farcaster_wp_subscriptions', array() );
		foreach ( $subscriptions as $fid => $apps ) {
			foreach ( $apps as $app_key => $app ) {
				$subscription = Storage::get_subscription_by_fid_and_app_key( $fid, $app_key );
				if ( ! empty( $subscription ) ) {
					WP_CLI::log(
						sprintf(
							'Skipping migration for subscription with FID %1$d and key "%2$s", already exists with ID %3$d.',
							$fid,
							$app_key,
							$subscription['id']
						)
					);
					$migrated_count++;
				} else {
					if ( ! self::$is_dry_run ) {
						$migrated_subscription = Storage::add_subscription(
							[
								'created_timestamp' => $app['timestamp'] ?? time(),
								'fid'               => $fid,
								'app_key'           => $app_key,
								'status'            => 'active',
								'token'             => $app['token'],
								'app_url'           => $app['url'],
							],
							[ '%s', '%d', '%s', '%s', '%s', '%s' ]
						);
						WP_CLI::log(
							sprintf(
								'Converted subscription for FID %1$d and key "%2$s" to custom table, ID %3$d.',
								$fid,
								$app_key,
								$migrated_subscription
							)
						);
					} else {
						WP_CLI::log(
							sprintf(
								'Skipping migration for subscription with FID %1$d and key "%2$s".',
								$fid,
								$app_key
							)
						);
						$migrated_count++;
						$migrated_subscription = $migrated_count;
					}
					$migrated_subscriptions[] = [
						'fid'     => $fid,
						'app_key' => $app_key,
						'id'      => $migrated_subscription,
						'token'   => $app['token'],
						'app_url' => $app['url'],
					];
				}
			}
		}

		return $migrated_subscriptions;
	}

	/**
	 * Purge legacy subscriptions in options.
	 */
	public static function cli_purge_legacy_subscriptions() {
		WP_CLI::confirm( 'Are you sure you want to purge legacy subscriptions?' );
		delete_option( 'farcaster_wp_subscriptions' );
		WP_CLI::success( 'Completed! Purged legacy subscriptions.' );
	}
}

Migration::instance();
