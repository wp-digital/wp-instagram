<?php

namespace Innocode\Instagram;

use WP_Error;

/**
 * Class Helpers
 * @package Innocode\Instagram
 */
class Helpers
{
    /**
     * Sanitizes key with possible section prefix.
     * @param string $name
     * @param string $section
     * @return string
     */
    public static function key( string $name, string $section = '' )
    {
        if ( $section ) {
            $separator = $name ? '_' : '';
            $name = "{$section}{$separator}$name";
        }

        return sanitize_key( $name );
    }

    /**
     * Decodes base64 string.
     * @link https://developers.facebook.com/docs/games/gamesonfacebook/login#parsingsr
     * @param string $input
     * @return false|string
     */
    public static function base64_url_decode( string $input )
    {
        return base64_decode( strtr( $input, '-_', '+/' ) );
    }

    /**
     * Encodes base64 string.
     * @param string $input
     * @return false|string
     */
    public static function base64_url_encode( string $input )
    {
        return strtr( base64_encode( $input ), '+/', '-_' );
    }

    /**
     * Parses signed request.
     * @link https://developers.facebook.com/docs/games/gamesonfacebook/login#parsingsr
     * @param string $signed_request
     * @param string $secret
     * @return array|WP_Error
     */
    public static function parse_signed_request( string $signed_request, string $secret )
    {
        list( $encoded_sig, $payload ) = explode( '.', $signed_request, 2 );

        $sig = static::base64_url_decode( $encoded_sig );
        $expected_sig = hash_hmac( 'sha256', $payload, $secret, true );

        if ( $sig !== $expected_sig ) {
            return new WP_Error(
                'invalid_signature',
                __( 'Bad Signed JSON signature.', 'innocode-instagram' )
            );
        }

        return json_decode( static::base64_url_decode( $payload ), true );
    }

    /**
     * Creates signed request.
     * @param array $payload
     * @param string $secret
     * @return string
     */
    public static function create_signed_request( array $payload, string $secret )
    {
        $encoded_payload = static::base64_url_encode( json_encode( wp_parse_args( $payload, [
            'algorithm' => 'HMAC-SHA256',
            'time'      => time(),
        ] ) ) );
        $hashed_sig = hash_hmac( 'sha256', $encoded_payload, $secret, true );
        $encoded_sig = static::base64_url_encode( $hashed_sig );

        return "$encoded_sig.$encoded_payload";
    }
}
