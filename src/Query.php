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
    public function __construct( $endpoint )
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
     * @param string   $uri
     * @param callable $callback
     */
    public function add_route( $uri, callable $callback )
    {
        $this->routes[ $uri ] = $callback;
    }

    /**
     * @param string $uri
     * @return string
     */
    public function path( $uri )
    {
        return "/{$this->get_endpoint()}/$uri/";
    }

    /**
     * @param string $uri
     * @return string
     */
    public function url( $uri )
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

        if ( isset( $routes[ $uri ] ) ) {
            $routes[ $uri ]();

            exit;
        }
    }
}
