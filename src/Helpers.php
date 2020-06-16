<?php

namespace Innocode\Instagram;

/**
 * Class Helpers
 * @package Innocode\Instagram
 */
class Helpers
{
    /**
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
}
