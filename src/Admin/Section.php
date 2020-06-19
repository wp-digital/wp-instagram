<?php

namespace Innocode\Instagram\Admin;

/**
 * Class Section
 * @package Innocode\Instagram\Admin
 */
class Section
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
     * Fields.
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
     * Returns name.
     * @return string
     */
    public function get_name()
    {
        return $this->name;
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
     * Returns fields.
     * @return Field[]
     */
    public function get_fields()
    {
        return $this->fields;
    }

    /**
     * Adds field.
     * @param string $name
     * @param Field  $field
     */
    public function add_field( string $name, Field $field )
    {
        $this->fields[ $name ] = $field;
    }
}
