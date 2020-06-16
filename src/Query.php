<?php

namespace Innocode\Instagram;

/**
 * Class Query
 * @package Innocode\Instagram
 */
final class Query
{
    /**
     * @var string
     */
    private $endpoint ;
    /**
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
     * @return string
     */
    public function get_endpoint()
    {
        return $this->endpoint;
    }

    /**
     * @return array
     */
    public function get_routes()
    {
        return $this->routes;
    }

    /**
     * @param string      $uri
     * @param callable    $callback
     * @param string|null $capability
     */
    public function add_route( string $uri, callable $callback, string $capability = null )
    {
        $this->routes[ $uri ] = [ $callback, $capability ];
    }

    /**
     * @param string $uri
     * @return string
     */
    public function path( string $uri )
    {
        return "/{$this->get_endpoint()}/$uri/";
    }

    /**
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

    public function handle_request()
    {
        $endpoint = $this->get_endpoint();
        $uri = get_query_var( $endpoint, null );

        if ( is_null( $uri ) ) {
            return;
        }

        $routes = $this->get_routes();

        if (
            isset( $routes[ $uri ] ) &&
            (
                ! isset( $routes[ $uri ][1] ) ||
                current_user_can( $routes[ $uri ][1] )
            )
        ) {
            $routes[ $uri ][0]();

            exit;
        }
    }
}
