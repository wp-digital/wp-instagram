<?php

namespace Innocode\Instagram\Admin;

/**
 * Class Field
 * @package Innocode\Instagram\Admin
 */
class Field
{
    /**
     * Setting.
     * @var Setting
     */
    protected $setting;
    /**
     * Type.
     * @var string
     */
    protected $type = 'text';
    /**
     * ID.
     * @var string
     */
    protected $id;
    /**
     * Attributes.
     * @var array
     */
    protected $attrs = [];
    /**
     * Render callback.
     * @var callable
     */
    protected $callback;
    /**
     * Description.
     * @var string
     */
    protected $description;

    /**
     * Returns setting.
     *
     * @return Setting
     */
    public function get_setting()
    {
        return $this->setting;
    }

    /**
     * Sets setting.
     *
     * @param Setting $setting
     */
    public function set_setting( Setting $setting )
    {
        $this->setting = $setting;
    }

    /**
     * Returns type.
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     * Sets type.
     *
     * @param string $type
     */
    public function set_type( string $type )
    {
        $this->type = $type;
    }

    /**
     * Returns ID.
     *
     * @return string
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Sets ID.
     *
     * @param string $id
     */
    public function set_id( string $id )
    {
        $this->id = $id;
    }

    /**
     * Returns attributes.
     *
     * @return array
     */
    public function get_attrs()
    {
        return wp_parse_args( $this->attrs, [
            'type' => 'text',
        ] );
    }

    /**
     * Sets attributes.
     *
     * @param array $attrs
     */
    public function set_attrs( array $attrs )
    {
        $this->attrs = $attrs;
    }

    /**
     * Returns attributes HTML string.
     *
     * @return string
     */
    public function get_attrs_html()
    {
        return implode( ' ', array_map( function ( $name, $value ) {
            return esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
        }, array_keys( $this->attrs ), $this->attrs ) );
    }

    /**
     * Adds attribute.
     *
     * @param string $name
     * @param string $value
     */
    public function add_attr( string $name, string $value )
    {
        $this->attrs[ $name ] = $value;
    }

    /**
     * Returns callback.
     *
     * @return callable
     */
    public function get_callback()
    {
        return $this->callback;
    }

    /**
     * Sets callback.
     *
     * @param callable $callback
     */
    public function set_callback( callable $callback )
    {
        $this->callback = $callback;
    }

    /**
     * Returns description.
     *
     * @return string
     */
    public function get_description()
    {
        return $this->description;
    }

    /**
     * Sets description.
     *
     * @param string $description
     */
    public function set_description( string $description )
    {
        $this->description = $description;
    }

    /**
     * Returns HTML element.
     *
     * @return string
     */
    public function get_html()
    {
        $callback = $this->get_callback();

        if ( is_callable( $callback ) ) {
            return $callback( $this );
        }

        $setting = $this->get_setting();
        $type = $this->get_type();
        $name = $setting->get_name();
        $value = $setting->get_value();
        $attrs = $this->get_attrs_html();

        switch ( $type ) {
            case 'textarea':
                $html = sprintf(
                    '<textarea id="%s" name="%s" cols="45" rows="5" %s>%s</textarea>',
                    esc_attr( $name ),
                    esc_attr( $name ),
                    $attrs,
                    esc_html( $value )
                );
                break;
            default:
                $html = sprintf(
                    "<input id=\"%s\" type=\"%s\" name=\"%s\" value=\"%s\" class=\"regular-text\" %s>",
                    esc_attr( $name ),
                    esc_attr( $type ),
                    esc_attr( $name ),
                    esc_attr( $value ),
                    $attrs
                );
                break;
        }

        $description = $this->get_description();

        if ( $description ) {
            $html = "<p class=\"description\">$description</p>";
        }

        return $html;
    }
}
