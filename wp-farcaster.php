<?php
/**
 * WP Farcaster
 *
 * @package           WP_Farcaster
 * @author            Davis Shaver
 * @license:          GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       WP Farcaster
 * Plugin URI:        https://wp-farcaster.davisshaver.com/
 * Description:       WP Farcaster connects your WordPress site to Farcaster.
 * Version:           0.0.3
 * Author:            Davis Shaver
 * Author URI:        https://davisshaver.com/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-farcaster
 * Tags:              WordPress, web3, Farcaster, Ethereum
 * Contributors:      davisshaver
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_FARCASTER_VERSION', '0.0.3' );

define( 'WP_FARCASTER_API_NAMESPACE', 'wp-farcaster/v1' );
define( 'WP_FARCASTER_API_URL', get_site_url() . '/wp-json/' . WP_FARCASTER_API_NAMESPACE );

// Define WP_FARCASTER_PLUGIN_DIR.
if ( ! defined( 'WP_FARCASTER_PLUGIN_DIR' ) ) {
	define( 'WP_FARCASTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Define WP_FARCASTER_PLUGIN_FILE.
if ( ! defined( 'WP_FARCASTER_PLUGIN_FILE' ) ) {
	define( 'WP_FARCASTER_PLUGIN_FILE', __FILE__ );
}

// Load language files.
load_plugin_textdomain( 'wp-farcaster', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

require_once __DIR__ . '/vendor/autoload.php';

// Include non-class helper functions.
require_once __DIR__ . '/includes/helpers.php';

WP_Farcaster\Initializer::init();
