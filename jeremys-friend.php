<?php
/**
 * Plugin Name:       Jeremy's Friend
 * Plugin URI:        https://presto.blog
 * Description:       Sets up "plug-in territory" functionality for Jeremy.
 * Version:           1.0.0
 * Author:            prestobunny
 * Author URI:        https://presto.blog/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       jeremys-friend
 * Domain Path:       /languages
 *
 * @link              https://presto.blog
 * @since             1.0.0
 * @package           Jeremys_Friend
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plug-in version.
 *
 * @since 1.0.0
 * @var string JEREMYS_FRIEND_VERSION
 */
define( 'JEREMYS_FRIEND_VERSION', '1.0.0' );

/**
 * The core plug-in class that handles all our plug-iny stuff.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-jeremys-friend.php';

/**
 * The class that handles registering custom post types.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-jeremys-friend-cpt.php';

/**
 * Runs code when the plug-in is activated. Currently, this function:
 *  - Initializes the class to register our custom post types
 * 	- Flushes rewrite rules to set up permalinks for custom post types
 * 	
 * @TODO test. also test if it registers post types even if the main class don't
 * 
 * @link https://developer.wordpress.org/reference/functions/register_post_type/#flushing-rewrite-on-activation
 * 
 * @since 1.0.0
 */
function activate_jeremy_cpt() {
	$plugin_cpt = new Jeremys_Friend_CPT();
	$plugin_cpt->register_cpts();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'activate_jeremys_friend' );

/**
 * Initializes the main plug-in.
 *
 * @since    1.0.0
 */
function start_jeremys_friend() {
	$plugin = new Jeremys_Friend();
}
start_jeremys_friend();

/**
 * Runs code when the plugin is deactivated. Currently, this function:
 * 	- Flushes rewrite rules
 * 
 * @since 1.0.0
 */
function deactivate_jeremy_cpt() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'deactivate_jeremys_friend' );