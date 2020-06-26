<?php

namespace Innocode\Instagram;

use WP_Error;
use WP_Http;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Site;

/**
 * Class RESTController
 * @package Innocode\Instagram
 */
class RESTController extends WP_REST_Controller
{
    /**
     * REST constructor.
     */
    public function __construct()
    {
        $this->namespace = 'innocode/v1';
        $this->rest_base = 'instagram';
    }

    /**
     * Returns endpoint.
     * @param string $path
     * @return string
     */
    public function path( string $path )
    {
        return "/wp-json/$this->namespace/$this->rest_base/" . ltrim( $path, '/' );
    }

    /**
     * Adds routes.
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            "/$this->rest_base/deauth",
            [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'deauth' ],
                'args'     => $this->get_endpoint_args_for_signed_request(),
            ]
        );
    }

    /**
     * Adds app site routes.
     */
    public function register_app_site_routes()
    {
        register_rest_route(
            $this->namespace,
            "/$this->rest_base/site",
            [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'update_site' ],
                'args'     => $this->get_endpoint_args_for_signed_request(),
            ]
        );

        register_rest_route(
            $this->namespace,
            "/$this->rest_base/site",
            [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'delete_site' ],
                'args'     => $this->get_endpoint_args_for_signed_request(),
            ]
        );
    }

    /**
     * Returns array of endpoint arguments for signed request.
     * @return array[]
     */
    public function get_endpoint_args_for_signed_request()
    {
        return [
            'signed_request' => [
                'description'       => __(
                    'Base64URL encoded and signed with an HMAC version of your App Secret, based on the OAuth 2.0 spec.',
                    'innocode-instagram'
                ),
                'type'              => 'string',
                'required'          => true,
                'validate_callback' => [ $this, 'check_signed_request' ],
            ],
        ];
    }

    /**
     * Checks signed request with minimum rules without parsing.
     * @param string $signed_request
     * @return bool
     */
    public function check_signed_request( string $signed_request )
    {
        return false !== strpos( $signed_request, '.' );
    }

    /**
     * User deauthorization callback.
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function deauth( WP_REST_Request $request )
    {
        $signed_request = $this->get_signed_request( $request );

        if ( is_wp_error( $signed_request ) ) {
            return $signed_request;
        }

        /**
         * @var Plugin $innocode_instagram
         */
        global $innocode_instagram;

        $app_site = $innocode_instagram->get_app_site();

        if ( $app_site && $app_site->is_current_site() ) {
            $urls = $app_site->get_sites_storage()
                ->get( $signed_request['user_id'] );

            foreach ( $urls as $url ) {
                wp_remote_post(
                    "$url{$this->path( 'deauth' )}",
                    [
                        'blocking'  => false,
                        'body'      => [
                            'signed_request' => $request->get_param( 'signed_request' ),
                        ],
                        'sslverify' => false,
                    ]
                );
            }
        }

        $user_id_setting = $innocode_instagram->get_options_page()
            ->get_sections()[ Plugin::SECTION_USER ]
            ->get_fields()['id']
            ->get_setting();
        $user_id = (string) $signed_request['user_id'];

        if ( ! is_multisite() ) {
            if ( $user_id_setting->get_value() == $user_id ) {
                $innocode_instagram->delete_all_data();
            }

            return rest_ensure_response( [
                'user_id' => $user_id,
                'url'     => home_url(),
            ] );
        }

        /**
         * @var WP_Site[] $sites
         */
        $sites = get_sites( [
            'meta_query' => [
                [
                    'key'   => $user_id_setting->get_name(),
                    'value' => $user_id,
                ],
            ],
        ] );
        $response = [
            'user_id' => $user_id,
            'url'     => [],
        ];

        foreach ( $sites as $site ) {
            $innocode_instagram->delete_all_data( $site->blog_id );
            $response['url'][] = $site->home;
        }

        return rest_ensure_response( $response );
    }

    /**
     * Updates site user_id on app site.
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update_site( WP_REST_Request $request )
    {
        $signed_request = $this->get_site_signed_request( $request );

        if ( is_wp_error( $signed_request ) ) {
            return $signed_request;
        }

        /**
         * @var Plugin $innocode_instagram
         */
        global $innocode_instagram;

        $user_id = (string) $signed_request['user_id'];
        $url = untrailingslashit( esc_url_raw( (string) $signed_request['url'] ) );

        if ( isset( $signed_request['previous_user_id'] ) ) {
            $previous_user_id = (string) $signed_request['previous_user_id'];
            $innocode_instagram->get_app_site()
                ->get_sites_storage()
                ->move( $previous_user_id, $user_id, $url );
        } else {
            $innocode_instagram->get_app_site()
                ->get_sites_storage()
                ->add( $user_id, $url );
        }

        return rest_ensure_response( [
            'user_id' => $user_id,
            'url'     => $url,
        ] );
    }

    /**
     * Deletes site with user_id from app site.
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function delete_site( WP_REST_Request $request )
    {
        $signed_request = $this->get_site_signed_request( $request );

        if ( is_wp_error( $signed_request ) ) {
            return $signed_request;
        }

        /**
         * @var Plugin $innocode_instagram
         */
        global $innocode_instagram;

        $user_id = (string) $signed_request['user_id'];
        $url = untrailingslashit( esc_url_raw( (string) $signed_request['url'] ) );
        $innocode_instagram->get_app_site()
            ->get_sites_storage()
            ->remove( $user_id, $url );

        return rest_ensure_response( [
            'user_id' => $user_id,
            'url'     => $url,
        ] );
    }

    /**
     * Returns parsed signed request.
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    protected function get_signed_request( WP_REST_Request $request )
    {
        /**
         * @var Plugin $innocode_instagram
         */
        global $innocode_instagram;

        $signed_request = Helpers::parse_signed_request(
            $request->get_param( 'signed_request' ),
            $innocode_instagram->get_client_secret()
        );

        if ( is_wp_error( $signed_request ) ) {
            $signed_request->add_data( [
                'status' => WP_Http::FORBIDDEN,
            ] );

            return $signed_request;
        }

        if ( empty( $signed_request['user_id'] ) ) {
            return new WP_Error(
                'rest_innocode_instagram_invalid_signed_request',
                __( 'Invalid signed request.', 'innocode-instagram' ),
                [
                    'status' => WP_Http::BAD_REQUEST,
                ]
            );
        }

        return $signed_request;
    }

    /**
     * Returns parsed signed request with site data.
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    protected function get_site_signed_request( WP_REST_Request $request )
    {
        $signed_request = $this->get_signed_request( $request );

        if ( is_wp_error( $signed_request ) ) {
            return $signed_request;
        }

        if ( empty( $signed_request['url'] ) ) {
            return new WP_Error(
                'rest_innocode_instagram_invalid_signed_request',
                __( 'Invalid signed request.', 'innocode-instagram' ),
                [
                    'status' => WP_Http::BAD_REQUEST,
                ]
            );
        }

        return $signed_request;
    }
}
