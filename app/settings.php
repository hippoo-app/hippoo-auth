<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', 'hippoo_auth_register_settings' );
add_action( 'admin_menu', 'hippoo_auth_settings_menu' );

function hippoo_auth_register_settings() {
    register_setting(
        'hippoo_auth_settings_group',
        'hippoo_auth_settings',
        array(
            'type'              => 'string',
            'sanitize_callback' => function($input) {
                $output = array();
                $fields = array(
                    'jwt_secret_key',
                    'google_client_id',
                    'facebook_app_id',
                    'apple_service_id'
                );
                foreach ( $fields as $field ) {
                    if ( isset( $input[$field] ) ) {
                        $output[$field] = sanitize_text_field( $input[$field] );
                    }
                }
                return $output;
            }
        )
    );

    add_settings_section( 'hippoo_auth_main_section', '', null, 'hippoo_auth_settings' );

    $fields = array(
        'jwt_secret_key' => 'JWT Secret Key',
        'google_client_id' => 'Google Client ID',
        'facebook_app_id'  => 'Facebook App ID',
        'apple_service_id' => 'Apple Service ID',
    );

    foreach ( $fields as $key => $label ) {
        add_settings_field(
            $key, $label,
            function() use ( $key ) {
                $options = get_option( 'hippoo_auth_settings' );
                $value = $options[$key] ?? '';
                printf(
                    '<input type="text" name="hippoo_auth_settings[%s]" value="%s" class="regular-text" />',
                    esc_attr( $key ),
                    esc_attr( $value )
                );
            },
            'hippoo_auth_settings',
            'hippoo_auth_main_section'
        );
    }
}

function hippoo_auth_settings_menu() {
    add_menu_page( 'Hippoo Auth Settings', 'Hippoo Auth', 'manage_options', 'hippoo_auth_settings', 'hippoo_auth_settings_page' );
}

function hippoo_auth_settings_page() {
    ?>
    <div class="wrap">
        <h1>Hippoo Auth Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'hippoo_auth_settings_group' );
                do_settings_sections( 'hippoo_auth_settings' );
                submit_button();
            ?>
        </form>
    </div>
    <?php
}