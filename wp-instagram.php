<?php
/**
 * Plugin Name: Instagram
 * Description: Enables Instagram API for developers.
 * Version: 0.0.1
 * Author: Innocode
 * Author URI: https://innocode.com
 * Requires at least: 4.9
 * Tested up to: 4.9.6
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package InnocodeInstagram
 */

define( 'INNOCODE_INSTAGRAM', 'innocode_instagram' );
define( 'INNOCODE_INSTAGRAM_VERSION', '0.0.1' );

function innocode_instagram_is_enabled() {
    return defined( 'INSTAGRAM_CLIENT_ID' ) && defined( 'INSTAGRAM_CLIENT_SECRET' );
}

/**
 * @param string $key
 *
 * @return string
 */
function innocode_instagram_sanitize_key( $key = '' ) {
    return sanitize_key( INNOCODE_INSTAGRAM . ( $key !== '' ? "_$key" : $key ) );
}

function innocode_instagram_load() {
    if ( innocode_instagram_is_enabled() ) {
        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/includes/class-settings.php';
        require_once __DIR__ . '/includes/class-api.php';
        require_once __DIR__ . '/includes/class-query.php';
        require_once __DIR__ . '/includes/class-admin.php';

        $GLOBALS['innocode_instagram'] = new InnocodeInstagram\API();
    }
}

add_action( 'init', 'innocode_instagram_load', 1 );

/**
 * @param int $blog_id
 *
 * @return string
 */
function innocode_instagram_admin_url( $blog_id ) {
    return class_exists( 'InnocodeInstagram\Admin' )
        ? get_admin_url( $blog_id, 'options-general.php?page=' . InnocodeInstagram\Admin::PAGE )
        : '';
}

/**
 * @return array
 */
function innocode_instagram_scope() {
    return apply_filters( innocode_instagram_sanitize_key( 'scope' ), [ 'basic' ] );
}

/**
 * @return string
 */
function innocode_instagram_callback() {
    return add_query_arg(
        'blog_id',
        get_current_blog_id(),
        network_home_url( '/instagram/auth/', is_ssl() ? 'https' : 'http' )
    );
}

/**
 * @return InnocodeInstagram\API
 */
function innocode_instagram() {
    /**
     * @var InnocodeInstagram\API $innocode_instagram
     */
    global $innocode_instagram;

    return $innocode_instagram;
}

function innocode_instagram_init() {
    if ( class_exists( 'InnocodeInstagram\Query' ) && is_main_site() ) {
        InnocodeInstagram\Query::register();
    }

    if ( class_exists( 'InnocodeInstagram\Admin' ) ) {
        InnocodeInstagram\Admin::register_menu_page();
    }
}

add_action( 'init', 'innocode_instagram_init' );

function innocode_instagram_admin_init() {
    if ( class_exists( 'InnocodeInstagram\Admin' ) ) {
        InnocodeInstagram\Admin::register();
    }
}

add_action( 'admin_init', 'innocode_instagram_admin_init' );