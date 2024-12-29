<?php
/**
 * Frames Integration for Farcaster
 *
 * @package           Farcaster_WP
 * @author            Davis Shaver
 * @license:          GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Frames Integration for Farcaster
 * Plugin URI:        https://davisshaver.com/frames-integration-for-farcaster/
 * Description:       Frames Integration for Farcaster connects your WordPress site to Farcaster.
 * Version:           0.0.42
 * Author:            Davis Shaver
 * Author URI:        https://davisshaver.com/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       frames-integration-for-farcaster
 * Tags:              WordPress, web3, Farcaster, Ethereum
 * Contributors:      davisshaver
 */

defined( 'ABSPATH' ) || exit;

define( 'FARCASTER_WP_VERSION', '0.0.42' );

define( 'FARCASTER_WP_API_NAMESPACE', 'farcaster-wp/v1' );
define( 'FARCASTER_WP_API_URL', get_site_url() . '/wp-json/' . FARCASTER_WP_API_NAMESPACE );

// Define FARCASTER_WP_PLUGIN_DIR.
if ( ! defined( 'FARCASTER_WP_PLUGIN_DIR' ) ) {
	define( 'FARCASTER_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Define FARCASTER_WP_PLUGIN_FILE.
if ( ! defined( 'FARCASTER_WP_PLUGIN_FILE' ) ) {
	define( 'FARCASTER_WP_PLUGIN_FILE', __FILE__ );
}

// Load language files.
load_plugin_textdomain( 'frames-integration-for-farcaster', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

require_once __DIR__ . '/vendor/autoload.php';

// Include non-class helper functions.
require_once __DIR__ . '/includes/helpers.php';

Farcaster_WP\Initializer::init();
