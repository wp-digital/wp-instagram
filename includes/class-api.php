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
     *
     * @param int $blog_id
     */
    public function __construct( $blog_id = 0 )
    {
        parent::__construct( [
            'apiKey'      => INSTAGRAM_CLIENT_ID,
            'apiSecret'   => INSTAGRAM_CLIENT_SECRET,
            'apiCallback' => innocode_instagram_callback( $blog_id ),
        ] );

        if ( '' !== ( $access_token = Settings::get_access_token() ) ) {
            $this->setAccessToken( $access_token );
        }
    }
}