<?php
/**
 * Plugin Name: Hippoo Auth
 * Plugin URI: https://Hippoo.app
 * Description: REST API-based authentication system with JWT and social login.
 * Version: 1.0.4
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

define( 'HIPPOO_AUTH_VERSION', '1.0.4' );
define( 'HIPPOO_AUTH_PATH', plugin_dir_path( __FILE__ ) );
define( 'HIPPOO_AUTH_URL', plugin_dir_url( __FILE__ ) );

require_once HIPPOO_AUTH_PATH . 'libs/autoload.php';
require_once HIPPOO_AUTH_PATH . 'app/utils.php';
require_once HIPPOO_AUTH_PATH . 'app/settings.php';
require_once HIPPOO_AUTH_PATH . 'app/web-api.php';
require_once HIPPOO_AUTH_PATH . 'app/web-api-auth.php';

require_once HIPPOO_AUTH_PATH . 'app/test.php';