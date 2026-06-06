<?php // phpcs:ignoreFile

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'hippoo_auth_test_menu' );

function hippoo_auth_test_menu() {
    add_menu_page( 'Hippoo Auth Test', 'Hippoo Auth Test', 'manage_options', 'hippoo-auth-test', 'hippoo_auth_test_page' );
}

function hippoo_auth_test_page() {
    $options = get_option( 'hippoo_auth_settings' );
?>
    <div class="social-login-container">
        <button id="google-login" class="social-btn">Sign in with Google</button>
        <button id="facebook-login" class="social-btn">Sign in with Facebook</button>
        <button id="apple-login" class="social-btn">Sign in with Apple</button>
        
        <div id="info-message"></div>
        
        <div id="user-info" class="user-info">
            <p>Name: <span id="user-name"></span></p>
            <p>Email: <span id="user-email"></span></p>
            <button id="logout-btn" class="social-btn" style="background-color: #666;">Logout</button>
        </div>
    </div>
<?php
}

add_action( 'admin_enqueue_scripts', 'hippoo_auth_enqueue_assets' );

function hippoo_auth_enqueue_assets( $hook ) {
    if ( $hook !== 'toplevel_page_hippoo-auth-test' ) {
        return;
    }

    wp_enqueue_style( 'hippoo-auth-admin', HIPPOO_AUTH_URL . 'assets/css/test.css', array(), HIPPOO_AUTH_VERSION );

    wp_enqueue_script( 'hippoo-auth-admin', HIPPOO_AUTH_URL . 'assets/js/test.js', array( 'jquery' ), HIPPOO_AUTH_VERSION, true );

    $options = get_option( 'hippoo_auth_settings' );
    wp_localize_script(
        'hippoo-auth-admin',
        'hippooAuthConfig',
        array(
            'apiUrl' => '/wp-json/hippoo-auth/v1/social-login',
            'google' => array(
                'clientId' => esc_html( $options['google_client_id'] ?? '' )
            ),
            'facebook' => array(
                'appId' => esc_html( $options['facebook_app_id'] ?? '' )
            ),
            'apple' => array(
                'clientId' => esc_html( $options['apple_service_id'] ?? '' ),
                'redirectUri' => esc_url( home_url() )
            ),
        )
    );

    wp_register_script(
        'google-signin',
        'https://accounts.google.com/gsi/client',
        array(),
        null,
        array(
            'strategy' => 'async',
            'in_footer' => true
        )
    );
    wp_enqueue_script( 'google-signin' );

    wp_register_script(
        'facebook-sdk',
        'https://connect.facebook.net/en_US/sdk.js',
        array(),
        null,
        array(
            'strategy' => 'async',
            'in_footer' => true
        )
    );
    wp_enqueue_script( 'facebook-sdk' );

    wp_register_script(
        'apple-signin',
        'https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js',
        array(),
        null,
        array(
            'strategy' => 'async',
            'in_footer' => true
        )
    );
    wp_enqueue_script( 'apple-signin' );
}