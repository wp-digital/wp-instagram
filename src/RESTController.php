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
     * Returns array of endpoint arguments for signed request.
     *
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
     *
     * @param string $signed_request
     * @return bool
     */
    public function check_signed_request( string $signed_request )
    {
        return false !== strpos( $signed_request, ':' );
    }

    /**
     * User deauthorization callback.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function deauth( WP_REST_Request $request )
    {
        /**
         * @var Plugin $innocode_instagram
         */
        global $innocode_instagram;

        $secret = $innocode_instagram->get_client_secret();

        if ( ! $secret ) {
            return new WP_Error(
                'rest_innocode_instagram_invalid_secret',
                __( 'Invalid APP secret.', 'innocode-instagram' ),
                [
                    'status' => WP_Http::INTERNAL_SERVER_ERROR,
                ]
            );
        }

        $parsed_signed_request = Helpers::parse_signed_request(
            $request->get_param( 'signed_request' ),
            $innocode_instagram->get_client_secret()
        );

        if ( is_wp_error( $parsed_signed_request ) ) {
            $parsed_signed_request->add_data( [
                'status' => WP_Http::FORBIDDEN,
            ] );

            return $parsed_signed_request;
        }

        if ( empty( $parsed_signed_request['user_id'] ) ) {
            return new WP_Error(
                'rest_innocode_instagram_invalid_signed_request',
                __( 'Invalid signed request.', 'innocode-instagram' ),
                [
                    'status' => WP_Http::INTERNAL_SERVER_ERROR,
                ]
            );
        }

        /**
         * @var WP_Site[] $sites
         */
        $sites = get_sites( [
            'meta_query' => [
                [
                    'key'   => $innocode_instagram->get_options_page()
                        ->get_sections()[ Plugin::SECTION_USER ]
                        ->get_fields()['id']
                        ->get_setting()
                        ->get_name(),
                    'value' => $parsed_signed_request['user_id'],
                ],
            ],
        ] );
        $response = [];

        foreach ( $sites as $site ) {
            $innocode_instagram->delete_all_data( $site->blog_id );
            $response[ $site->blog_id ] = $site->domain;
        }

        return rest_ensure_response( $response );
    }
}
