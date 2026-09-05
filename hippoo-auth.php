<?php
/**
 * Plugin Name: Hippoo Auth
 * Plugin URI: https://Hippoo.app
 * Description: REST API-based authentication system with JWT and social login.
 * Version: 1.1.1
 * Author: Hippoo team
 * Author URI: https://Hippoo.app
 * Text Domain: hippoo-auth
 * License: GPL3
 * Requires at least: 5.8
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.8
 * Requires Plugins: woocommerce
 **/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'HIPPOO_AUTH_VERSION', '1.1.1' );
define( 'HIPPOO_AUTH_PATH', plugin_dir_path( __FILE__ ) );
define( 'HIPPOO_AUTH_URL', plugin_dir_url( __FILE__ ) );

require_once HIPPOO_AUTH_PATH . 'libs/autoload.php';
require_once HIPPOO_AUTH_PATH . 'app/utils.php';
require_once HIPPOO_AUTH_PATH . 'app/settings.php';
require_once HIPPOO_AUTH_PATH . 'app/web-api.php';
require_once HIPPOO_AUTH_PATH . 'app/web-api-auth.php';

require_once HIPPOO_AUTH_PATH . 'app/test.php';

/**
 * Ensure a signing key exists so token issuing and validation work out of the box.
 *
 * Runs on activation: if the stored key is missing or empty, generate a random one
 * and persist it. Token functions fail closed when no key is configured, so a fresh
 * install needs a key in place before the first login attempt.
 */
function hippoo_auth_maybe_bootstrap_signing_key() {
    $options = get_option( 'hippoo_auth_settings', [] );

    if ( ! is_array( $options ) ) {
        $options = [];
    }

    if ( empty( $options['jwt_secret_key'] ) ) {
        $options['jwt_secret_key'] = bin2hex( random_bytes( 32 ) );
        update_option( 'hippoo_auth_settings', $options );
    }
}
register_activation_hook( __FILE__, 'hippoo_auth_maybe_bootstrap_signing_key' );