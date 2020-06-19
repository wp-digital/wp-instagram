<?php

namespace Innocode\Instagram;

use WP_Error;

/**
 * Class AppSite
 * @package Innocode\Instagram
 */
final class AppSite
{
    /**
     * Site URL.
     * @var string
     */
    private $url;
    /**
     * Map user_id with sites URLs
     * @var Storage
     */
    private $sites_storage;

    /**
     * AppSite constructor.
     * @param string $url
     */
    public function __construct( string $url )
    {
        $this->url = $url;
        $this->sites_storage = new Storage( 'sites_storage' );
    }

    /**
     * Returns site URL.
     * @return string
     */
    public function get_url()
    {
        return untrailingslashit( $this->url );
    }

    /**
     * Returns map of user_id with sites URLs.
     * @return Storage
     */
    public function get_sites_storage()
    {
        return $this->sites_storage;
    }

    /**
     * Returns site URL.
     * @param string $path
     * @return string
     */
    public function url( string $path = '' )
    {
        $url = $this->get_url();

        if ( $path ) {
            $url .= '/' . ltrim( $path, '/' );
        }

        return $url;
    }

    /**
     * Checks whether current site is app site.
     * @return bool
     */
    public function is_current_site()
    {
        return $this->url() == home_url();
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $args
     * @return array|WP_Error
     */
    public function request( string $method, string $path, array $args = [] )
    {
        /**
         * @var Plugin $innocode_instagram
         */
        global $innocode_instagram;

        $response = wp_remote_request(
            $this->url(
                $innocode_instagram->get_rest_controller()
                    ->path( $path )
            ),
            wp_parse_args( $args, [
                'method'    => $method,
                'blocking'  => false,
                'sslverify' => false,
            ] )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    /**
     *
     * @param string $method
     * @param array  $payload
     * @param array  $args
     * @return array|WP_Error
     */
    public function site_signed_request( string $method, array $payload, array $args = [] )
    {
        /**
         * @var Plugin $innocode_instagram
         */
        global $innocode_instagram;

        return $this->request( $method, 'sites', wp_parse_args( $args, [
            'body' => [
                'signed_request' => Helpers::create_signed_request(
                    $payload,
                    $innocode_instagram->get_client_secret()
                ),
            ],
        ] ) );
    }

    /**
     *
     * @param string $user_id
     * @return array|WP_Error
     */
    public function add_current_site( string $user_id )
    {
        return $this->site_signed_request( 'POST', [
            'user_id' => $user_id,
            'url'     => home_url(),
        ] );
    }

    /**
     *
     * @param string $user_id
     * @param string $previous_user_id
     * @return array|WP_Error
     */
    public function update_current_site( string $user_id, string $previous_user_id )
    {
        return $this->site_signed_request( 'POST', [
            'user_id' => $user_id,
            'url'     => home_url(),
        ] );
    }

    /**
     *
     * @param string $user_id
     * @return array|WP_Error
     */
    public function delete_current_site( string $user_id )
    {
        return $this->site_signed_request( 'DELETE', [
            'user_id' => $user_id,
            'url'     => home_url(),
        ] );
    }
}
