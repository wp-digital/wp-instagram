<?php

namespace Innocode\Instagram;

/**
 * Class Storage
 * @package Innocode\Instagram
 */
class Storage
{
    /**
     * Option base name.
     * @var string
     */
    protected $base_name;

    /**
     * Storage constructor.
     * @param string $base_name
     */
    public function __construct( string $base_name )
    {
        $this->base_name = $base_name;
    }

    /**
     * Returns option base name.
     * @return string
     */
    public function get_base_name()
    {
        return $this->base_name;
    }

    /**
     * Returns sanitized and prefixed option name.
     * @param string $name
     * @return string
     */
    public function key( string $name )
    {
        return Helpers::key( $name, INNOCODE_INSTAGRAM . "_{$this->get_base_name()}" );
    }

    /**
     * Returns option.
     * @param string $name
     * @return array
     */
    public function get( string $name )
    {
        $default = [];

        if ( false === ( $data = get_option( $this->key( $name ), $default ) ) ) {
            return $default;
        }

        return (array) $data;
    }

    /**
     * Adds value to option.
     * @param string $name
     * @param mixed  $value
     */
    public function add( string $name, $value )
    {
        $data = $this->get( $name );
        $data[] = $value;

        update_option( $this->key( $name ), array_unique( $data ), false );
    }

    /**
     * Removes value from option.
     * @param string $name
     * @param mixed  $value
     */
    public function remove( string $name, $value )
    {
        $data = array_flip( $this->get( $name ) );

        unset( $data[ $value ] );

        if ( empty( $data ) ) {
            $this->delete( $name );
        } else {
            update_option( $this->key( $name ), array_flip( $data ), false );
        }
    }

    /**
     * Deletes option.
     * @param string $name
     */
    public function delete( string $name )
    {
        delete_option( $this->key( $name ) );
    }

    /**
     * Moves value between options.
     * @param string $from
     * @param string $to
     * @param mixed  $value
     */
    public function move( string $from, string $to, $value )
    {
        $this->remove( $from, $value );
        $this->add( $to, $value );
    }
}
