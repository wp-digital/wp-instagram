<?php

namespace Innocode\Instagram\Admin;

use InvalidArgumentException;

/**
 * Class Setting
 * @property string   $type
 * @property string   $description
 * @property callable $sanitize_callback
 * @property bool     $show_in_rest
 * @property mixed    $default
 * @package Innocode\Instagram\Admin
 */
class Setting
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $title;
    /**
     * @var array
     */
    protected $args = [];

    /**
     * Setting constructor.
     * @param string $name
     * @param string $title
     */
    public function __construct( $name, $title )
    {
        $this->name = $name;
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set( $name, $value )
    {
        $this->args[ $name ] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get( $name )
    {
        if ( array_key_exists( $name, $this->args ) ) {
            return $this->args[ $name ];
        }

        throw new InvalidArgumentException(
            sprintf(
                'Property %s doesn\'t exist in class %s',
                $name,
                get_class( $this )
            )
        );
    }

    /**
     * @return array
     */
    public function get_args()
    {
        return wp_parse_args( $this->args, [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );
    }

    /**
     * @return mixed
     */
    public function get_value()
    {
        return get_option( $this->get_name() );
    }
}
