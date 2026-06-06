<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {
    register_rest_route( 'hippoo-auth/v1', 'login', array(
        'methods' => 'POST',
        'callback' => 'hippoo_auth_handle_login',
        'permission_callback' => '__return_true',
        'args' => array(
            'email' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => 'is_email',
                'sanitize_callback' => 'sanitize_email',
            ),
            'password' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => function ( $param ) {
                    return ! empty( trim( $param ) );
                },
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );

    register_rest_route( 'hippoo-auth/v1', 'signup', array(
        'methods' => 'POST',
        'callback' => 'hippoo_auth_handle_signup',
        'permission_callback' => '__return_true',
        'args' => array(
            'email' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => 'is_email',
                'sanitize_callback' => 'sanitize_email',
            ),
            'password' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => function ( $param ) {
                    return ! empty( trim( $param ) );
                },
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );

    register_rest_route( 'hippoo-auth/v1', 'logout', array(
        'methods' => 'POST',
        'callback' => 'hippoo_auth_handle_logout',
        'permission_callback' => 'hippoo_auth_permission_check',
    ) );

    register_rest_route( 'hippoo-auth/v1', 'reset-password/request', array(
        'methods' => 'POST',
        'callback' => 'hippoo_auth_handle_reset_request',
        'permission_callback' => '__return_true',
        'args' => array(
            'email' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => 'is_email',
                'sanitize_callback' => 'sanitize_email',
            ),
        ),
    ) );

    register_rest_route( 'hippoo-auth/v1', 'reset-password/confirm', array(
        'methods' => 'POST',
        'callback' => 'hippoo_auth_handle_reset_confirm',
        'permission_callback' => '__return_true',
        'args' => array(
            'token' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => function ( $param ) {
                    return ! empty( trim( $param ) );
                },
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'new_password' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => function ( $param ) {
                    return ! empty( trim( $param ) );
                },
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );
    
    register_rest_route( 'hippoo-auth/v1', 'social-login', array(
        'methods' => 'POST',
        'callback' => 'hippoo_auth_handle_social_login',
        'permission_callback' => '__return_true',
        'args' => array(
            'provider' => array(
                'required' => true,
                'type' => 'string',
                'enum' => array( 'google', 'facebook', 'apple' ),
                'validate_callback' => function ( $param ) {
                    return ! empty( trim( $param ) );
                },
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'token' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => function ( $param ) {
                    return ! empty( trim( $param ) );
                },
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );

    register_rest_route( 'hippoo-auth/v1', 'refresh-token', array(
        'methods' => 'POST',
        'callback' => 'hippoo_auth_handle_refresh_token',
        'permission_callback' => '__return_true',
        'args' => array(
            'refresh_token' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => function ( $param ) {
                    return ! empty( trim( $param ) );
                },
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );
	
	register_rest_route( 'hippoo-auth/v1', 'validate-token', array(
        'methods' => 'POST',
        'callback' => 'hippoo_auth_handle_validate_token',
        'permission_callback' => '__return_true',
        'args' => array(
            'token' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => function ( $param ) {
                    return ! empty( trim( $param ) );
                },
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );
} );

function hippoo_auth_get_user_response( $user ) {
    hippoo_auth_authenticate_user( $user );

    $access_token = hippoo_auth_generate_access_token( $user->ID );
    $refresh_token = hippoo_auth_generate_refresh_token( $user->ID );

    return array(
        'token' => $access_token,
        'refresh_token' => $refresh_token,
        'user' => array(
            'id' => $user->ID,
            'email' => $user->user_email,
            'name' => $user->display_name,
        ),
    );
}

function hippoo_auth_handle_login( $request ) {
    $email = $request->get_param( 'email' );
    $password = $request->get_param( 'password' );

    $user = wp_authenticate( $email, $password );

    if ( is_wp_error( $user ) ) {
        return new WP_Error( 'authentication_failed', 'Invalid email or password', [ 'status' => 401 ] );
    }

    $response = hippoo_auth_get_user_response( $user );
    
    return new WP_REST_Response( $response, 200 );
}

function hippoo_auth_handle_signup( $request ) {
    $email = $request->get_param( 'email' );
    $password = $request->get_param( 'password' );
    
    if ( email_exists( $email ) ) {
        return new WP_Error( 'email_exists', 'This email is already registered', [ 'status' => 400 ] );
    }

    $user = hippoo_auth_find_or_create_user( array( 'email' => $email, 'password' => $password ) );

    if ( is_wp_error( $user ) ) {
        return $user;
    }

    $response = hippoo_auth_get_user_response( $user );

    return new WP_REST_Response( $response, 200 );
}

function hippoo_auth_handle_logout( $request ) {
    $user_id = get_current_user_id();
    delete_user_meta( $user_id, 'hippoo_auth_access_token' );
    delete_user_meta( $user_id, 'hippoo_auth_refresh_token' );
    wp_logout();

    return new WP_REST_Response( 'Logged out successfully', 200 );
}

function hippoo_auth_handle_reset_request( $request ) {
    $email = $request->get_param( 'email' );
    
    $user = get_user_by( 'email', $email );

    if ( ! $user ) {
        return new WP_Error( 'user_not_found', 'No user found with this email address', [ 'status' => 404 ] );
    }

    $results = retrieve_password( $user->user_login );
    
    if ( true === $results ) {
        setcookie( 'rp-userlogin' . COOKIEHASH, $user->user_login, 0, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

        return new WP_REST_Response( 'A password reset link was emailed to ' . $email , 200 );
    }

    return new WP_Error( 'reset_request_failed', $results->get_error_message(), [ 'status' => 400 ] );
}

function hippoo_auth_handle_reset_confirm( $request ) {
    $token = $request->get_param( 'token' );
    $new_password = $request->get_param( 'new_password' );

    if ( isset( $_COOKIE[ 'rp-userlogin' . COOKIEHASH ] ) ) {
        $rp_login = sanitize_key( $_COOKIE[ 'rp-userlogin' . COOKIEHASH ] );
    }

    $user = check_password_reset_key( $token, $rp_login ?? '' );

    if ( is_wp_error( $user ) ) {
        return new WP_Error( 'invalid_token', 'Invalid or expired reset token', [ 'status' => 400 ] );
    }

    setcookie( 'rp-userlogin' . COOKIEHASH, '', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

    reset_password( $user, $new_password );

    return new WP_REST_Response( 'Password has been reset successfully', 200 );
}

function hippoo_auth_handle_social_login( $request ) {
    $provider = $request->get_param( 'provider' );
    $token = $request->get_param( 'token' );

    switch ( $provider ) {
        case 'google':
            $payload = hippoo_auth_verify_google_token( $token );
            break;
        case 'facebook':
            $payload = hippoo_auth_verify_facebook_token( $token );
            break;
        case 'apple':
            $payload = hippoo_auth_verify_apple_token( $token );
            break;
        default:
            return new WP_Error( 'invalid_provider', 'Unsupported social login provider', [ 'status' => 400 ] );
    }

    if ( is_wp_error( $payload ) ) {
        return $payload;
    }

    $payload['provider'] = $provider;
    $user = hippoo_auth_find_or_create_user( $payload );

    if ( is_wp_error( $user ) ) {
        return $user;
    }

    $response = hippoo_auth_get_user_response( $user );

    return new WP_REST_Response( $response, 200 );
}

function hippoo_auth_handle_refresh_token( $request ) {
    $refresh_token = $request->get_param( 'refresh_token' );
    
    $user = hippoo_auth_validate_refresh_token( $refresh_token );
    
    if ( ! $user ) {
        return new WP_Error( 'invalid_refresh_token', 'Invalid or expired refresh token', [ 'status' => 401 ] );
    }
    
    $new_access_token = hippoo_auth_generate_access_token( $user->ID );
    $new_refresh_token = hippoo_auth_generate_refresh_token( $user->ID );

    $response = array(
        'token' => $new_access_token,
        'refresh_token' => $new_refresh_token,
        'user' => array(
            'id' => $user->ID,
            'email' => $user->user_email,
            'name' => $user->display_name,
        ),
    );
    
    return new WP_REST_Response( $response, 200 );
}

function hippoo_auth_handle_validate_token( $request ) {
    $token = $request->get_param( 'token' );
    
    $user = hippoo_auth_validate_access_token( $token );
    
    if ( ! $user ) {
        return new WP_Error( 'invalid_token', 'Invalid or expired token', [ 'status' => 401 ] );
    }

    $response = array(
        'valid' => true,
        'user' => array(
            'id' => $user->ID,
            'email' => $user->user_email,
            'name' => $user->display_name,
        ),
    );
    
    return new WP_REST_Response( $response, 200 );
}