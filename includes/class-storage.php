<?php
/**
 * Farcaster WP plugin storage handling.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

/**
 * Class to handle Farcaster plugin storage.
 */
class Storage {

	/**
	 * Installed version number of the events custom table.
	 */
	const EVENTS_TABLE_VERSION = '1.0';

	/**
	 * Installed version number of the fids custom table.
	 */
	const FIDS_TABLE_VERSION = '1.0';

	/**
	 * Option name for the installed version number of the events custom table.
	 */
	const EVENTS_TABLE_VERSION_OPTION = '_farcaster_wp_events_version';

	/**
	 * Option name for the installed version number of the fids custom table.
	 */
	const FIDS_TABLE_VERSION_OPTION = '_farcaster_wp_fids_version';

	/**
	 * The single instance of the class.
	 *
	 * @var $instance
	 */
	protected static $instance = null;

	/**
	 * Main Storage instance.
	 * Ensures only one instance of Storage is loaded or can be loaded.
	 *
	 * @return Storage - Main instance.
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
		register_activation_hook( FARCASTER_WP_PLUGIN_FILE, [ __CLASS__, 'action_register_activation' ] );
		add_action( 'init', [ __CLASS__, 'action_init' ] );
	}

	/**
	 * Get events table name.
	 */
	public static function get_events_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'farcaster_wp_events';
	}

	/**
	 * Get fids table name.
	 */
	public static function get_fids_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'farcaster_wp_fids';
	}

	/**
	 * Checks if the custom table has been created and is up-to-date.
	 * If not, run the create_custom_table method.
	 * See: https://codex.wordpress.org/Creating_Tables_with_Plugins
	 */
	public static function action_init() {
		$events_current_version = get_option( self::EVENTS_TABLE_VERSION_OPTION, false );
		$fids_current_version   = get_option( self::FIDS_TABLE_VERSION_OPTION, false );

		if ( self::EVENTS_TABLE_VERSION !== $events_current_version ) {
			self::create_events_custom_table();
			update_option( self::EVENTS_TABLE_VERSION_OPTION, self::EVENTS_TABLE_VERSION );
		}

		if ( self::FIDS_TABLE_VERSION !== $fids_current_version ) {
			self::create_fids_custom_table();
			update_option( self::FIDS_TABLE_VERSION_OPTION, self::FIDS_TABLE_VERSION );
		}
	}

	/**
	 * Runs on plugin activation.
	 */
	public static function action_register_activation() {
		self::create_events_custom_table();
		self::create_fids_custom_table();
	}

	/**
	 * Create a custom DB table to store events data.
	 * Only create the table if it doesn't already exist.
	 */
	public static function create_events_custom_table() {
		global $wpdb;
		$table_name = self::get_events_table_name();

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) != $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $table_name (
				-- ID.
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				-- Event type.
                -- Examples: frame_added, frame_removed, notifications_disabled, notifications_enabled
				event_type varchar(255) NOT NULL,
				-- FID.
				fid bigint(20) unsigned NOT NULL,
				-- Event timestamp.
				timestamp bigint(20) unsigned NOT NULL,
				-- Full event data.
				full_event text,
				PRIMARY KEY (id),
				KEY (fid),
                KEY (event_type)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.dbDelta_dbdelta
		}
	}

	/**
	 * Create a custom DB table to store fids data.
	 * Avoids the use of a huge options value.
	 * Only create the table if it doesn't already exist.
	 */
	public static function create_fids_custom_table() {
		global $wpdb;
		$table_name = self::get_fids_table_name();

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) != $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $table_name (
				-- ID.
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				-- Farcaster FID.
				fid bigint(20) unsigned NOT NULL,
				-- App key.
				app_key varchar(255) NOT NULL,
				-- URL.
				app_url text NOT NULL,
				-- Token.
				token text NOT NULL,
				-- Created timestamp.
				created_timestamp bigint(20) unsigned NOT NULL,
				-- Updated timestamp.
				updated_timestamp bigint(20) unsigned NOT NULL,
                -- Status.
                status varchar(255) NOT NULL,
				PRIMARY KEY (id),
                KEY (fid, app_key),
				KEY (fid),
				KEY (app_key),
				KEY (status)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.dbDelta_dbdelta
		}
	}

	/**
	 * Get events.
	 *
	 * @return array The events.
	 */
	public static function get_events() {
		global $wpdb;
		$table_name = self::get_events_table_name();
		$results    = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %1$s', [ $table_name ] ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder, WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $results ? $results : [];
	}

	/**
	 * Get active subscriptions.
	 *
	 * @return array The subscriptions.
	 */
	public static function get_active_subscriptions() {
		global $wpdb;
		$table_name = self::get_fids_table_name();

		$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %1$s WHERE status = \'active\'', [ $table_name ] ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $results ? $results : [];
	}

	/**
	 * Get subscription by token.
	 *
	 * @param string $token The token.
	 * @return array The subscription.
	 */
	public static function get_subscription_by_token( $token ) {
		global $wpdb;
		$table_name = self::get_fids_table_name();

		$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %1$s WHERE token = "%2$s"', [ $table_name, $token ] ), ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $results ? $results[0] : [];
	}

	/**
	 * Update subscription.
	 *
	 * @param int   $id The subscription ID.
	 * @param array $data The data to update.
	 * @param array $format The format of the data.
	 */
	public static function update_subscription( $id, $data, $format = null ) {
		global $wpdb;
		$table_name = self::get_fids_table_name();
		return $wpdb->update( $table_name, $data, [ 'id' => $id ], $format, [ '%d' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Add subscription.
	 *
	 * @param array $data The data to add.
	 * @param array $format The format of the data.
	 * @return int|false The ID of the inserted subscription, or false if the subscription was not added.
	 */
	public static function add_subscription( $data, $format ) {
		global $wpdb;
		$table_name = self::get_fids_table_name();
		$results    = $wpdb->insert( $table_name, $data, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $results ) ) {
			return $wpdb->insert_id;
		}
		return false;
	}

	/**
	 * Get subscription by FID and app key.
	 *
	 * @param int    $fid The FID.
	 * @param string $app_key The app key.
	 * @return array|false The subscription or false if not found.
	 */
	public static function get_subscription_by_fid_and_app_key( $fid, $app_key ) {
		global $wpdb;
		$table_name = self::get_fids_table_name();

		$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %1$s WHERE fid = %2$d AND app_key = "%3$s"', [ $table_name, $fid, $app_key ] ), ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $results ? $results[0] : false;
	}

	/**
	 * Record an event.
	 *
	 * @param array $data The data to add.
	 * @param array $format The format of the data.
	 * @return int|false The ID of the inserted event, or false if the event was not added.
	 */
	public static function record_event( $data, $format ) {
		global $wpdb;
		$table_name = self::get_events_table_name();
		$results    = $wpdb->insert( $table_name, $data, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $results ) ) {
			return $wpdb->insert_id;
		}
		return false;
	}
}

Storage::instance();
