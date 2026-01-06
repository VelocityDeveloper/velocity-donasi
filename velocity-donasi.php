<?php
/**
 * The plugin Donasi for Velocity Developer
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/VelocityDeveloper/velocity-donasi
 * @since             1.0.0
 * @package           velocity-donasi
 *
 * @wordpress-plugin
 * Plugin Name:       Velocity Donasi
 * Plugin URI:        https://velocitydeveloper.com/
 * Description:       Plugin Donasi oleh Velocity Developer
 * Version:           3.0.0
 * Author:            Velocity Developer
 * Author URI:        https://velocitydeveloper.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       velocity-donasi
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'VELOCITY_DONASI_VERSION', '3.0.0' );

/**
 * Define constants
 *
 * @since 1.2.0
 */
if (!defined('VELOCITY_DONASI_DIR')) {
    define('VELOCITY_DONASI_DIR', plugin_dir_path(__FILE__)); // Plugin directory absolute path with the trailing slash. Useful for using with includes eg - /var/www/html/wp-content/plugins/velocity-donasi/
}
if (!defined('VELOCITY_DONASI_DIR_URI')) {
    define('VELOCITY_DONASI_DIR_URI', plugin_dir_url(__FILE__)); // URL to the plugin folder with the trailing slash. Useful for referencing src eg - http://localhost/wp-content/plugins/velocity-donasi
}

// Load everything
$includes = [
    'inc/rest.php',
    'inc/meta-box.php',
    'inc/functions.php',
    'inc/shortcodes.php',
    'inc/customizer.php',
    'inc/post-type.php',
];
foreach ($includes as $include) {
    require_once VELOCITY_DONASI_DIR . $include;
}

// Add custom scripts and styles
function velocity_donasi_scripts() {
    $wptheme = wp_get_theme( 'velocity' );
    if (!$wptheme->exists()) {
        wp_enqueue_style( 'vdonasi-bootstrap-style', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' );
        wp_enqueue_script( 'vdonasi-bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array(), null, true );
    }
    wp_enqueue_style( 'vdonasi-custom-style', VELOCITY_DONASI_DIR_URI . 'css/donasi.css' );
    wp_enqueue_script( 'vdonasi-js', VELOCITY_DONASI_DIR_URI . 'js/donasi.js', array( 'jquery' ), null, true );
    wp_localize_script( 'vdonasi-js', 'vdonasi_api', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'rest_url' => esc_url_raw( rest_url( 'velocity-donasi/v1/submit' ) ),
        'rest_nonce' => wp_create_nonce( 'wp_rest' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'velocity_donasi_scripts' );
