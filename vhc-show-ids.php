<?php
/**
 * Plugin Name: VHC Show IDs
 * Plugin URI: https://github.com/vijayhardaha/vhc-show-ids
 * Description: Shows IDs on all post, page, media list, user and taxonomy pages.
 * Version: 1.0.0
 * Author: Vijay Hardaha
 * Author URI: https://twitter.com/vijayhardaha
 * Text Domain: vhc-show-ids
 * Domain Path: /languages/
 * Requires at least: 5.4
 * Requires PHP: 5.6
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package VHC_Show_Ids
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! defined( 'VHC_SHOW_IDS_PLUGIN_FILE' ) ) {
	define( 'VHC_SHOW_IDS_PLUGIN_FILE', __FILE__ );
}

// Include the main VHC_Show_Ids class.
if ( ! class_exists( 'VHC_Show_Ids', false ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-vhc-show-ids.php';
}

new VHC_Show_Ids();
