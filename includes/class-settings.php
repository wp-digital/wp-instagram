<?php

namespace InnocodeInstagram;

/**
 * Class Settings
 *
 * @package InnocodeInstagram
 */
final class Settings
{
    const SECTION_GENERAL = '';
    const SECTION_USER = 'user';

    /**
     * @param string $section
     * @param string $key
     *
     * @return string
     */
    public static function sanitize_key( $section, $key )
    {
        if ( !isset( static::get_sections()[ $section ] ) ) {
            return new \WP_Error( 'invalid_section', __( 'Invalid section.', 'innocode-instagram' ) );
        }

        return innocode_instagram_sanitize_key( $section !== '' ? "{$section}_$key" : $key );
    }

    /**
     * @param int    $blog_id
     * @param string $section
     * @param string $key
     *
     * @return mixed|\WP_Error
     */
    public static function get( $blog_id, $section, $key )
    {
        $key = static::sanitize_key( $section, $key );

        if ( is_wp_error( $key ) ) {
            return $key;
        }

        return get_blog_option( $blog_id, $key );
    }

    /**
     * @param int    $blog_id
     * @param string $section
     * @param string $key
     * @param mixed  $value
     *
     * @return bool|\WP_Error
     */
    public static function update( $blog_id, $section, $key, $value )
    {
        $key = static::sanitize_key( $section, $key );

        if ( is_wp_error( $key ) ) {
            return $key;
        }

        return update_blog_option( $blog_id, $key, $value );
    }

    /**
     * @param int    $blog_id
     * @param string $section
     * @param string $key
     *
     * @return bool|string
     */
    public static function delete( $blog_id, $section, $key )
    {
        $key = static::sanitize_key( $section, $key );

        if ( is_wp_error( $key ) ) {
            return $key;
        }

        return delete_blog_option( $blog_id, $key );
    }

    /**
     * @param int       $blog_id
     * @param \stdClass $user
     */
    public static function update_user( $blog_id, \stdClass $user )
    {
        foreach ( $user as $key => $value ) {
            Settings::update( $blog_id, static::SECTION_USER, $key, $value );
        }
    }

    /**
     * @return string
     */
    public static function get_access_token()
    {
        return (string) Settings::get( get_current_blog_id(), static::SECTION_GENERAL, 'access_token' );
    }

    /**
     * @return array
     */
    public static function get_sections()
    {
        return [
            static::SECTION_GENERAL => __( 'General Settings', 'innocode-instagram' ),
            static::SECTION_USER    => __( 'User', 'innocode-instagram' ),
        ];
    }
}