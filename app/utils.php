<?php

if ( ! defined( 'ABSPATH' ) ) exit;

use \Firebase\JWT\JWK;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

function hippoo_auth_generate_access_token( $user_id ) {
    try {
        $options = get_option( 'hippoo_auth_settings' );
        $key = ! empty( $options['jwt_secret_key'] ) ? $options['jwt_secret_key'] : 'hippoo-auth-jwt-super-secret-key';

        $issued_at = time();
        $exp = apply_filters( 'hippoo_auth_access_token_expiry', DAY_IN_SECONDS * 7 );
        $payload = array(
            'iss' => get_bloginfo( 'url' ),
            'iat' => $issued_at,
            'exp' => $issued_at + $exp,
            'user_id' => $user_id
        );

        $token = JWT::encode( $payload, $key, 'HS256' );
        
        update_user_meta( $user_id, 'hippoo_auth_access_token', $token );

        return $token;
    } catch ( Exception $e ) {
        return false;
    }
}

function hippoo_auth_validate_access_token( $token ) {
    try {
        $options = get_option( 'hippoo_auth_settings' );
        $key = ! empty( $options['jwt_secret_key'] ) ? $options['jwt_secret_key'] : 'hippoo-auth-jwt-super-secret-key';

        $decoded = JWT::decode( $token, new Key( $key, 'HS256' ) );
        $user = get_user_by( 'id', $decoded->user_id );
        $saved_token = get_user_meta( $user->ID, 'hippoo_auth_access_token', true );

        if ( $saved_token !== $token ) {
            return false;
        }

        return $user;
    } catch ( Exception $e ) {
        return false;
    }
}

function hippoo_auth_generate_refresh_token( $user_id ) {
    try {
        $options = get_option( 'hippoo_auth_settings' );
        $key = ! empty( $options['jwt_secret_key'] ) ? $options['jwt_secret_key'] : 'hippoo-auth-jwt-super-secret-key';

        $issued_at = time();
        $exp = apply_filters( 'hippoo_auth_refresh_token_expiry', YEAR_IN_SECONDS );
        $payload = array(
            'iss' => get_bloginfo( 'url' ),
            'iat' => $issued_at,
            'exp' => $issued_at + $exp,
            'user_id' => $user_id,
            'type' => 'refresh'
        );

        $token = JWT::encode( $payload, $key, 'HS256' );
        
        update_user_meta( $user_id, 'hippoo_auth_refresh_token', $token );

        return $token;
    } catch (Exception $e) {
        return false;
    }
}

function hippoo_auth_validate_refresh_token( $token ) {
    try {
        $options = get_option( 'hippoo_auth_settings' );
        $key = ! empty( $options['jwt_secret_key'] ) ? $options['jwt_secret_key'] : 'hippoo-auth-jwt-super-secret-key';

        $decoded = JWT::decode( $token, new Key( $key, 'HS256' ) );
        
        if ( ! isset( $decoded->type ) || $decoded->type !== 'refresh' ) {
            return false;
        }

        $user = get_user_by( 'id', $decoded->user_id );
        $saved_token = get_user_meta( $user->ID, 'hippoo_auth_refresh_token', true );

        if ( $saved_token !== $token ) {
            return false;
        }

        return $user;
    } catch (Exception $e) {
        return false;
    }
}

function hippoo_auth_authenticate_user( $user ) {
    if ( function_exists( 'wc_set_customer_auth_cookie' ) ) {
        wc_set_customer_auth_cookie( $user->ID );
    } else {
        wp_clear_auth_cookie();
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );
    }
}

function hippoo_auth_find_or_create_user( $user_data, $role = 'customer' ) {
    $user = get_user_by( 'email', $user_data['email'] );

    if ( ! $user ) {
        $name = current( explode( '@', $user_data['email'] ) );
        $username = sanitize_user( $name, true ) . wp_rand( 1111 , 9999 );
        $password = $user_data['password'] ?? wp_generate_password();
        $user_id = wp_create_user( $username, $password, $user_data['email'] );

        if ( is_wp_error( $user_id ) ) {
            return new WP_Error( 'registration_failed', 'User registration failed', [ 'status' => 400 ] );
        }

        wp_update_user( array(
            'ID' => $user_id,
            'display_name' => $user_data['name'] ?? $name,
            'first_name' => $user_data['first_name'] ?? '',
            'last_name' => $user_data['last_name'] ?? '',
            'role' => $role
        ) );

        $user = get_user_by( 'id', $user_id );

        if ( isset( $user_data['provider'] ) ) {
            update_user_meta( $user_id, 'hippoo_auth_provider', $user_data['provider'] );
            
            if ( function_exists( 'wc_get_customer' ) ) {
                $customer = wc_get_customer( $user_id );
                if ( $customer ) {
                    $customer->update_meta_data( 'hippoo_auth_provider', $user_data['provider'] );
                    $customer->save();
                }
            }
        }
    }

    return $user;
}

function hippoo_auth_verify_google_token( $token ) {
    $response = wp_remote_get( 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode( $token ) );
    
    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'google_api_error', 'Failed to verify Google token', [ 'status' => 401 ] );
    }
    
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    
    if ( isset( $body['error'] ) || ! isset( $body['email'] )) {
        return new WP_Error( 'invalid_token', $body['error']['message'] ?? 'Google token verification failed', [ 'status' => 401 ] );
    }
    
    return array(
        'email' => $body['email'],
        'name' => $body['name'],
        'first_name' => $body['given_name'],
        'last_name' => $body['family_name']
    );
}

function hippoo_auth_verify_facebook_token( $token ) {
    $response = wp_remote_get( 'https://graph.facebook.com/me?fields=id,name,email,first_name,last_name&access_token=' . urlencode( $token ) );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'facebook_api_error', 'Failed to verify Facebook token', [ 'status' => 401 ] );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( isset( $body['error'] ) || ! isset( $body['email'] ) ) {
        return new WP_Error( 'invalid_token', $body['error']['message'] ?? 'Facebook token verification failed', [ 'status' => 401 ] );
    }

    return array(
        'email' => $body['email'],
        'name' => $body['name'],
        'first_name' => $body['first_name'],
        'last_name' => $body['last_name']
    );
}

function hippoo_auth_verify_apple_token( $token ) {
    $header = json_decode( base64_decode( explode( '.', $token )[0] ), true );

    if ( empty( $header['kid'] ) ) {
        return new WP_Error( 'invalid_token', 'Invalid Apple token header', [ 'status' => 401 ] );
    }

    $keys_url = 'https://appleid.apple.com/auth/keys';
    $response = wp_remote_get( $keys_url );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'apple_api_error', 'Failed to fetch Apple public keys', [ 'status' => 401 ] );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! isset( $body['keys'] ) ) {
        return new WP_Error( 'apple_key_error', 'Apple public keys not available', [ 'status' => 401 ] );
    }

    $public_key_data = null;
    foreach ( $body['keys'] as $key ) {
        if ( $key['kid'] === $header['kid'] ) {
            $public_key_data = $key;
            break;
        }
    }

    if ( ! $public_key_data ) {
        return new WP_Error( 'key_not_found', 'Apple public key not found', [ 'status' => 401 ] );
    }

    try {
        $public_key_pem = JWK::parseKeySet( [ 'keys' => [ $public_key_data ] ] )[ $public_key_data['kid'] ];
    } catch ( Exception $e ) {
        return new WP_Error( 'key_parse_error', 'Failed to parse Apple public key: ' . $e->getMessage(), [ 'status' => 401 ] );
    }

    try {
        $decoded = JWT::decode( $token, $public_key_pem );
    } catch ( Exception $e ) {
        return new WP_Error( 'jwt_decode_error', 'Apple token decode failed: ' . $e->getMessage(), [ 'status' => 401 ] );
    }

    if ( ! isset( $decoded->email ) ) {
        return new WP_Error( 'missing_email', 'Email not found in Apple token', [ 'status' => 401 ] );
    }

    return array(
        'email' => $decoded->email,
        'name' => $decoded->name ?? '',
        'first_name' => '',
        'last_name' => '',
    );
}