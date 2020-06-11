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
    protected $name;
    /**
     * @var string
     */
    protected $menu_slug;
    /**
     * @var string
     */
    protected $title;
    /**
     * @var string
     */
    protected $menu_title;
    /**
     * @var string
     */
    protected $capability = 'manage_options';
    /**
     * @var string
     */
    protected $view;
    /**
     * @var Section[]
     */
    protected $sections = [];

    /**
     * OptionsPage constructor.
     * @param string $name
     * @param string $menu_slug
     * @param string $title
     */
    public function __construct( $name, $menu_slug, $title )
    {
        $this->name = $name;
        $this->menu_slug = $menu_slug;
        $this->title = $title;
        $this->menu_title = $title;
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
    public function get_menu_slug()
    {
        return $this->menu_slug;
    }

    /**
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function get_menu_title()
    {
        return $this->menu_title;
    }

    /**
     * @param string $menu_title
     */
    public function set_menu_title( $menu_title )
    {
        $this->menu_title = $menu_title;
    }

    /**
     * @return string
     */
    public function get_capability()
    {
        return $this->capability;
    }

    /**
     * @param string $capability
     */
    public function set_capability( $capability )
    {
        $this->capability = $capability;
    }

    /**
     * @return string
     */
    public function get_view()
    {
        return $this->view;
    }

    /**
     * @param string $view
     */
    public function set_view( $view )
    {
        $this->view = $view;
    }

    /**
     * @return Section[]
     */
    public function get_sections()
    {
        return $this->sections;
    }

    /**
     * @param string  $name
     * @param Section $section
     */
    public function add_section( $name, Section $section )
    {
        $this->sections[ $name ] = $section;
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
     * @return string
     */
    public function get_hook()
    {
        return get_plugin_page_hook( $this->get_menu_slug(), 'options-general.php' );
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
