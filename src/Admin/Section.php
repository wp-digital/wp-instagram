<?php

namespace Innocode\Instagram\Admin;

/**
 * Class Section
 * @package Innocode\Instagram\Admin
 */
class Section
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
     * @var Field[]
     */
    protected $fields = [];

    /**
     * Section constructor.
     * @param string $name
     * @param string $title
     */
    public function __construct( string $name, string $title )
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
     * @return Field[]
     */
    public function get_fields()
    {
        return $this->fields;
    }

    /**
     * @param string $name
     * @param Field  $field
     */
    public function add_field( string $name, Field $field )
    {
        $this->fields[ $name ] = $field;
    }
}
