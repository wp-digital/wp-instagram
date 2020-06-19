<?php

namespace Innocode\Instagram;

/**
 * Class Route
 * @package Innocode\Instagram
 */
class Route
{
    /**
     * Callback.
     * @var callable
     */
    private $callback;
    /**
     * Capability.
     * @var string
     */
    private $capability;

    /**
     * Route constructor.
     * @param callable $callback
     */
    public function __construct( callable $callback )
    {
        $this->callback = $callback;
    }

    /**
     * Returns callback.
     * @return callable
     */
    public function get_callback()
    {
        return $this->callback;
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
     * Returns capability.
     * @return string
     */
    public function get_capability()
    {
        return $this->capability;
    }

    /**
     * Invokes callback.
     */
    public function __invoke()
    {
        $capability = $this->get_capability();

        if ( $capability && current_user_can( $capability ) ) {
            $this->get_callback()();
            exit;
        }
    }
}
