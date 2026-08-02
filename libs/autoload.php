<?php

if ( ! defined( 'ABSPATH' ) ) exit;

$hippoo_auth_prefixes = array(
    'Firebase\\JWT\\' => HIPPOO_AUTH_PATH . '/libs/php-jwt/src/',
);

spl_autoload_register( function ( $class ) use ( $hippoo_auth_prefixes ) {
    foreach ( $hippoo_auth_prefixes as $prefix => $base_dir ) {
        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) === 0 ) {
            $relative_class = substr( $class, $len );
            $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
            
            if ( file_exists( $file ) ) {
                require_once $file;
                return;
            }
        }
    }
} );