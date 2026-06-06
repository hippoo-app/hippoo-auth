<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {
    register_rest_route( 'hippoo-auth/v1', 'orders', array(
        'methods' => 'GET',
        'callback' => 'hippoo_auth_get_user_orders',
        'permission_callback' => 'hippoo_auth_permission_check',
    ) );

    register_rest_route( 'hippoo-auth/v1', 'orders/(?P<order_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'hippoo_auth_get_order_details',
        'permission_callback' => 'hippoo_auth_permission_check',
    ) );

    register_rest_route( 'hippoo-auth/v1', 'addresses', array(
        'methods' => 'GET',
        'callback' => 'hippoo_auth_get_user_address',
        'permission_callback' => 'hippoo_auth_permission_check',
    ) );

    register_rest_route( 'hippoo-auth/v1', 'addresses', array(
        'methods' => 'PUT',
        'callback' => 'hippoo_auth_update_user_address',
        'permission_callback' => 'hippoo_auth_permission_check',
    ) );

    register_rest_route( 'hippoo-auth/v1', 'settings', array(
        'methods'  => 'GET',
        'callback' => 'hippoo_auth_get_settings',
        'permission_callback' => 'hippoo_auth_permission_check',
    ) );
} );

function hippoo_auth_permission_check( $request ) {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if ( strpos( $auth_header, 'Bearer ' ) === 0 ) {
        $token = str_replace( 'Bearer ', '', $auth_header );
        $user = hippoo_auth_validate_access_token( $token );
        if ( $user ) {
            hippoo_auth_authenticate_user( $user );
            return true;
        }
    }

    return new WP_Error( 'unauthorized', 'You must be logged in to get results', [ 'status' => 401 ] );
}

function hippoo_auth_get_user_orders( $request ) {
    $user_id = get_current_user_id();

    $orders = wc_get_orders( array(
        'customer_id' => $user_id,
        'limit'       => -1,
        'orderby'     => 'date',
        'order'       => 'DESC'
    ) );

    $data = [];
    foreach ( $orders as $order ) {
        $controller = new WC_REST_Orders_V2_Controller();
        $prepared = $controller->prepare_object_for_response( $order, $request );
        $data[] = $prepared->get_data();
    }

    return new WP_REST_Response( $data, 200 );
}

function hippoo_auth_get_order_details( $request ) {
    $order_id = $request->get_param( 'order_id' );
    $user_id = get_current_user_id();

    $order = wc_get_order( $order_id );

    if ( ! $order || is_wp_error( $order ) ) {
        return new WP_Error( 'not_found', 'Order not found', [ 'status' => 404 ] );
    }

    if ( $order->get_user_id() !== $user_id ) {
        return new WP_Error( 'forbidden', 'You do not have permission to view this order', [ 'status' => 403 ] );
    }

    $controller = new WC_REST_Orders_V2_Controller();
    $prepared = $controller->prepare_object_for_response( $order, $request );

    return new WP_REST_Response( $prepared->get_data(), 200 );
}

function hippoo_auth_get_user_address( $request ) {
    $user_id = get_current_user_id();
    $customer = new WC_Customer( $user_id );

    if ( ! $customer || is_wp_error( $customer ) ) {
        return new WP_Error( 'no_customer', 'Customer not found', [ 'status' => 404 ] );
    }

    $billing = $customer->get_billing();
    $shipping = $customer->get_shipping();

    $response = array(
        'billing' => $billing,
        'shipping' => $shipping,
    );

    return new WP_REST_Response( $response, 200 );
}

function hippoo_auth_update_user_address( $request ) {
    $user_id = get_current_user_id();
    $customer = new WC_Customer( $user_id );

    if ( ! $customer || is_wp_error( $customer ) ) {
        return new WP_Error( 'no_customer', 'Customer not found.', [ 'status' => 404 ] );
    }

    $billing_data = $request->get_param( 'billing' );
    $shipping_data = $request->get_param( 'shipping' );

    if ( is_array( $billing_data ) ) {
        foreach ( $billing_data as $key => $value ) {
            $method = 'set_billing_' . $key;
            if ( method_exists( $customer, $method ) ) {
                $customer->$method( sanitize_text_field( $value ) );
            }
        }
    }

    if ( is_array( $shipping_data ) ) {
        foreach ( $shipping_data as $key => $value ) {
            $method = 'set_shipping_' . $key;
            if ( method_exists( $customer, $method ) ) {
                $customer->$method( sanitize_text_field( $value ) );
            }
        }
    }

    $customer->save();

    return new WP_REST_Response( 'Addresses updated successfully', 200);
}

function hippoo_auth_get_settings( $request ) {
    $options = get_option( 'hippoo_auth_settings' );
    $response = array(
        'google_client_id' => $options['google_client_id'] ?? '',
        'facebook_app_id' => $options['facebook_app_id'] ?? '',
        'apple_service_id' => $options['apple_service_id'] ?? '',
    );
    return new WP_REST_Response( $response, 200 );
}