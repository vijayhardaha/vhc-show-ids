<?php
/**
 * Plugin Name: VH WP Show IDs
 * Plugin URI: https://github.com/vijayhardaha/
 * Description: This plugin shows IDs on all post, page, media list, user and taxonomy pages.
 * Version: 1.0.0
 * Author: Vijay Hardaha
 * Author URI: https://github.com/vijayhardaha/
 * Text Domain: vh-wp-show-ids
 * Domain Path: /languages/
 * Requires at least: 5.4
 * Requires PHP: 5.6
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package VH_WP_Show_Ids
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! defined( 'VH_WP_SHOW_IDS_PLUGIN_FILE' ) ) {
	define( 'VH_WP_SHOW_IDS_PLUGIN_FILE', __FILE__ );
}

// Include the main VH_WP_Show_Ids class.
if ( ! class_exists( 'VH_WP_Show_Ids', false ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-vh-wp-show-ids.php';
}

/**
 * Returns the main instance of VH_WP_Show_Ids.
 *
 * @since  1.0.0
 * @return VH_WP_Show_Ids
 */
function vh_wp_show_ids() {
	return VH_WP_Show_Ids::instance();
}

// Global for backwards compatibility.
$GLOBALS['vh_wp_show_ids'] = vh_wp_show_ids();
