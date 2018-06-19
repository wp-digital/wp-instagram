<?php

namespace InnocodeInstagram;

use MetzWeb\Instagram\Instagram;

/**
 * Class API
 *
 * @package InnocodeInstagram
 */
final class API extends Instagram
{
    /**
     * API constructor.
     */
    public function __construct()
    {
        parent::__construct( [
            'apiKey'      => INSTAGRAM_CLIENT_ID,
            'apiSecret'   => INSTAGRAM_CLIENT_SECRET,
            'apiCallback' => innocode_instagram_callback(),
        ] );

        if ( '' !== ( $access_token = Settings::get_access_token() ) ) {
            $this->setAccessToken( $access_token );
        }
    }
}