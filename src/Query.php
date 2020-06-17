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
     *
     * @return string
     */
    public function get_endpoint()
    {
        return $this->endpoint;
    }

    /**
     * Returns routes collection.
     *
     * @return array
     */
    public function get_routes()
    {
        return $this->routes;
    }

    /**
     * Adds route to collection.
     *
     * @param string      $uri
     * @param callable    $callback
     * @param string|null $capability
     */
    public function add_route( string $uri, callable $callback, string $capability = null )
    {
        $route = new Route( $callback );

        if ( ! is_null( $capability ) ) {
            $route->set_capability( $capability );
        }

        $this->routes[ $uri ] = $route;
    }

    /**
     * Returns endpoint.
     *
     * @param string $uri
     * @return string
     */
    public function path( string $uri )
    {
        return "/{$this->get_endpoint()}/$uri/";
    }

    /**
     * Returns URL.
     *
     * @param string $uri
     * @return string
     */
    public function url( string $uri )
    {
        return network_home_url(
            $this->path( $uri ),
            is_ssl() ? 'https' : 'http'
        );
    }

    /**
     * Handles request.
     */
    public function handle_request()
    {
        $endpoint = $this->get_endpoint();
        $uri = get_query_var( $endpoint, null );

        if ( is_null( $uri ) ) {
            return;
        }

        $routes = $this->get_routes();

        if ( isset( $routes[ $uri ] ) ) {
            $routes[ $uri ]();
        }
    }
}
