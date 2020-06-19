<?php

namespace Innocode\Instagram\Admin;

/**
 * Class OptionsPage
 * @package Innocode\Instagram\Admin
 */
class OptionsPage
{
    /**
     * Name.
     * @var string
     */
    protected $name;
    /**
     * Menu slug.
     * @var string
     */
    protected $menu_slug;
    /**
     * Title.
     * @var string
     */
    protected $title;
    /**
     * Menu title.
     * @var string
     */
    protected $menu_title;
    /**
     * Capability.
     * @var string
     */
    protected $capability = 'manage_options';
    /**
     * View file name.
     * @var string
     */
    protected $view;
    /**
     * Sections collection.
     * @var Section[]
     */
    protected $sections = [];

    /**
     * OptionsPage constructor.
     * @param string $name
     * @param string $menu_slug
     * @param string $title
     */
    public function __construct( string $name, string $menu_slug, string $title )
    {
        $this->name = $name;
        $this->menu_slug = $menu_slug;
        $this->title = $title;
        $this->menu_title = $title;
    }

    /**
     * Returns name.
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * Returns menu slug.
     * @return string
     */
    public function get_menu_slug()
    {
        return $this->menu_slug;
    }

    /**
     * Returns title.
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * Returns menu title.
     * @return string
     */
    public function get_menu_title()
    {
        return $this->menu_title;
    }

    /**
     * Sets menu title.
     * @param string $menu_title
     */
    public function set_menu_title( string $menu_title )
    {
        $this->menu_title = $menu_title;
    }

    /**
     * Returns capability.
     * @return string
     */
    public function get_capability()
    {
        return $this->capability;
    }

    /**
     * Sets capability.
     * @param string $capability
     */
    public function set_capability( string $capability )
    {
        $this->capability = $capability;
    }

    /**
     * Returns view file name.
     * @return string
     */
    public function get_view()
    {
        return $this->view;
    }

    /**
     * Sets view file name.
     * @param string $view
     */
    public function set_view( string $view )
    {
        $this->view = $view;
    }

    /**
     * Returns sections collection.
     * @return Section[]
     */
    public function get_sections()
    {
        return $this->sections;
    }

    /**
     * Adds section.
     * @param string  $name
     * @param Section $section
     */
    public function add_section( string $name, Section $section )
    {
        $this->sections[ $name ] = $section;
    }

    /**
     * Returns admin page URL.
     * @param int|null $blog_id
     * @return string
     */
    public function get_admin_url( int $blog_id = null )
    {
        return get_admin_url( $blog_id, "options-general.php?page={$this->get_menu_slug()}" );
    }

    /**
     * Checks whether page has at least one field which is not disabled.
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
