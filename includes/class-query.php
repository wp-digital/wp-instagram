<?php

namespace InnocodeInstagram;

/**
 * Class Query
 *
 * @package InnocodeInstagram
 */
final class Query
{
    const ENDPOINT_INSTAGRAM = 'instagram';

    /**
     * @var array
     */
    private static $_query_vars = [
        self::ENDPOINT_INSTAGRAM,
    ];

    public static function register()
    {
        static::_init_query_vars();
        static::_add_endpoints();

        if ( !is_admin() ) {
            add_filter( 'query_vars', function ( array $vars ) {
                return static::_add_query_vars( $vars );
            }, 0 );
            add_action( 'wp', function () {
                static::_handle_request();
            } );
        }
    }

    /**
     * @return bool
     */
    public static function is_instagram_page()
    {
        return !is_null( get_query_var( static::ENDPOINT_INSTAGRAM, null ) );
    }

    private static function _init_query_vars()
    {
        static::$_query_vars = array_filter( array_combine( static::$_query_vars, array_map( function ( $var ) {
            return apply_filters( innocode_instagram_sanitize_key( 'query_var' ), $var );
        }, static::$_query_vars ) ) );
    }

    private static function _add_endpoints()
    {
        foreach ( static::$_query_vars as $var ) {
            add_rewrite_endpoint( $var, apply_filters( innocode_instagram_sanitize_key( 'endpoint_mask' ), EP_ROOT, $var ) );
        }
    }

    /**
     * @param array $vars
     *
     * @return array
     */
    private static function _add_query_vars( array $vars )
    {
        foreach ( static::$_query_vars as $key => $var ) {
            $vars[] = $key;
        }

        return $vars;
    }

    private static function _handle_request()
    {
        if ( static::is_instagram_page() ) {
            static::_handle_instagram();
        }
    }

    private static function _handle_instagram()
    {
        switch ( get_query_var( static::ENDPOINT_INSTAGRAM ) ) {
            case 'auth':
                $blog_id = isset( $_GET['blog_id'] ) ? absint( $_GET['blog_id'] ) : 0;

                if ( !$blog_id || false === ( $blog = get_blog_details( $blog_id ) ) ) {
                    wp_die(
                        '<h1>' . __( 'Something went wrong.' ) . '</h1>' .
                        '<p>' . __( 'Invalid blog ID.', 'innocode-instagram' ) . '</p>',
                        \WP_Http::BAD_REQUEST
                    );
                }

                $options_page_url = innocode_instagram_admin_url( $blog->blog_id );
                $return_link = "<p><a href=\"$options_page_url\" target=\"_parent\">" . sprintf( __( 'Return to %s', 'innocode-instagram' ), $blog->blogname ) . '</a>.</p>';
                $code = isset( $_GET['code'] ) ? $_GET['code'] : '';

                if ( !$code ) {
                    wp_die(
                        '<h1>' . __( 'Something went wrong.' ) . '</h1>' .
                        '<p>' . __( 'Invalid code.', 'innocode-instagram' ) . "</p>$return_link",
                        \WP_Http::BAD_REQUEST
                    );
                }

                $oauth_token = innocode_instagram()->getOAuthToken( $code );

                if ( isset( $oauth_token->error_type ) ) {
                    wp_die(
                        '<h1>' . __( 'Something went wrong.' ) . '</h1>' .
                        "<p>$oauth_token->error_message</p>$return_link",
                        $oauth_token->code
                    );
                }

                if ( isset( $oauth_token->access_token ) ) {
                    Settings::update( $blog->blog_id, Settings::SECTION_GENERAL, 'access_token', $oauth_token->access_token );
                }

                if ( isset( $oauth_token->user ) && $oauth_token->user instanceof \stdClass ) {
                    Settings::update_user( $blog->blog_id, $oauth_token->user );
                }

                wp_redirect( $options_page_url );
                exit;
        }
    }
}