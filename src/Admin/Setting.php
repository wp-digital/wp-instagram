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
     * Name.
     * @var string
     */
    protected $name;
    /**
     * Title.
     * @var string
     */
    protected $title;
    /**
     * Arguments.
     * @var array
     */
    protected $args = [];

    /**
     * Setting constructor.
     * @param string $name
     * @param string $title
     */
    public function __construct( string $name, string $title )
    {
        $this->name = $name;
        $this->title = $title;
    }

    /**
     * Returns name.
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * Returns title.
     *
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * Sets argument.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set( string $name, $value )
    {
        $this->args[ $name ] = $value;
    }

    /**
     * Returns argument.
     *
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
     * Returns arguments.
     *
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
     * Retrieves option.
     *
     * @param mixed    $default
     * @param int|null $blog_id
     * @return bool|mixed
     */
    public function get_value( $default = false, int $blog_id = null )
    {
        if ( $blog_id && is_multisite() ) {
            return get_blog_option( $blog_id, $this->get_name(), $default );
        } else {
            return get_option( $this->get_name(), $default );
        }
    }

    /**
     * Updates option.
     *
     * @param mixed    $value
     * @param int|null $blog_id
     */
    public function update_value( $value, int $blog_id = null )
    {
        if ( $blog_id && is_multisite() ) {
            update_blog_option( $blog_id, $this->get_name(), $value );
        } else {
            update_option( $this->get_name(), $value );
        }
    }

    /**
     * Deletes option.
     *
     * @param int|null $blog_id
     */
    public function delete_value( int $blog_id = null )
    {
        if ( $blog_id && is_multisite() ) {
            delete_blog_option( $blog_id, $this->get_name() );
        } else {
            delete_option( $this->get_name() );
        }
    }
}
