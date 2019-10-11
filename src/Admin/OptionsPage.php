<?php

namespace Innocode\Instagram\Admin;

/**
 * Class OptionsPage
 * @package Innocode\Instagram\Admin
 */
class OptionsPage
{
    /**
     * @var string
     */
    protected $_name;
    /**
     * @var string
     */
    protected $_menu_slug;
    /**
     * @var string
     */
    protected $_title;
    /**
     * @var string
     */
    protected $_menu_title;
    /**
     * @var string
     */
    protected $_capability = 'manage_options';
    /**
     * @var string
     */
    protected $_view;
    /**
     * @var Section[]
     */
    protected $_sections = [];

    /**
     * OptionsPage constructor.
     * @param string $name
     * @param string $menu_slug
     * @param string $title
     */
    public function __construct( $name, $menu_slug, $title )
    {
        $this->_name = $name;
        $this->_menu_slug = $menu_slug;
        $this->_title = $title;
        $this->_menu_title = $title;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->_name;
    }

    /**
     * @return string
     */
    public function get_menu_slug()
    {
        return $this->_menu_slug;
    }

    /**
     * @return string
     */
    public function get_title()
    {
        return $this->_title;
    }

    /**
     * @return string
     */
    public function get_menu_title()
    {
        return $this->_menu_title;
    }

    /**
     * @param string $menu_title
     */
    public function set_menu_title( $menu_title )
    {
        $this->_menu_title = $menu_title;
    }

    /**
     * @return string
     */
    public function get_capability()
    {
        return $this->_capability;
    }

    /**
     * @param string $capability
     */
    public function set_capability( $capability )
    {
        $this->_capability = $capability;
    }

    /**
     * @return string
     */
    public function get_view()
    {
        return $this->_view;
    }

    /**
     * @param string $view
     */
    public function set_view( $view )
    {
        $this->_view = $view;
    }

    /**
     * @return Section[]
     */
    public function get_sections()
    {
        return $this->_sections;
    }

    /**
     * @param string  $name
     * @param Section $section
     */
    public function add_section( $name, Section $section )
    {
        $this->_sections[ $name ] = $section;
    }

    /**
     * @param int|null $blog_id
     * @return string
     */
    public function get_admin_url( $blog_id = null )
    {
        return get_admin_url( $blog_id, "options-general.php?page={$this->get_menu_slug()}" );
    }

    /**
     * @return bool
     */
    public function has_enabled_fields()
    {
        $sections = $this->get_sections();

        foreach ( $sections as $section ) {
            foreach ( $section->get_fields() as $field ) {
                $attrs = $field->get_attrs();

                if ( empty( $attrs['disabled'] ) ) {
                    return true;
                }
            }
        }

        return false;
    }
}
