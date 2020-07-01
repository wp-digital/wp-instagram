<?php

namespace Innocode\Instagram;

/**
 * Class Query
 * @package Innocode\Instagram
 */
final class Query
{
    /**
     * Base endpoint.
     * @var string
     */
    private $endpoint;
    /**
     * Routes collection.
     * @var array
     */
    private $routes = [];

    /**
     * Query constructor.
     * @param string $endpoint
     */
    public function __construct( string $endpoint )
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Returns base endpoint.
     * @return string
     */
    public function get_endpoint()
    {
        return $this->endpoint;
    }

    /**
     * Returns routes collection.
     * @return array
     */
    public function get_routes()
    {
        return $this->routes;
    }

    /**
     * Adds route to collection.
     * @param string      $name
     * @param callable    $callback
     * @param string|null $capability
     */
    public function add_route( string $name, callable $callback, string $capability = null )
    {
        $route = new Route( $callback );

        if ( ! is_null( $capability ) ) {
            $route->set_capability( $capability );
        }

        $this->routes[ $name ] = $route;
    }

    /**
     * Returns endpoint.
     * @param string $path
     * @return string
     */
    public function path( string $path )
    {
        return "/{$this->get_endpoint()}/" . trim( $path, '/' ) . '/';
    }

    /**
     * Returns URL.
     * @param int    $blog_id
     * @param string $route
     * @return string
     */
    public function url( int $blog_id, string $route )
    {
        $path = $this->path( $route );

        return get_home_url( $blog_id, $path );
    }

    /**
     * Handles request.
     */
    public function handle_request()
    {
        $endpoint = $this->get_endpoint();
        $route = get_query_var( $endpoint, null );

        if ( is_null( $route ) ) {
            return;
        }

        $routes = $this->get_routes();

        if ( isset( $routes[ $route ] ) ) {
            $routes[ $route ]();
        }
    }
}
