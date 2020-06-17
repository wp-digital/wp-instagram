<?php
/**
 * Plugin Name: WordPress Instagram integration
 * Description: Enables Instagram API for developers.
 * Version: 2.0.0
 * Author: Innocode
 * Author URI: https://innocode.com
 * Requires at least: 4.9
 * Tested up to: 5.4.2
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

use Innocode\Instagram;
use EspressoDev\InstagramBasicDisplay\InstagramBasicDisplay;

define( 'INNOCODE_INSTAGRAM', 'innocode_instagram' );

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if ( defined( 'INSTAGRAM_CLIENT_ID' ) && defined( 'INSTAGRAM_CLIENT_SECRET' ) ) {
    $GLOBALS['innocode_instagram'] = new Instagram\Plugin(
        INSTAGRAM_CLIENT_ID,
        INSTAGRAM_CLIENT_SECRET,
        __FILE__
    );
    $GLOBALS['innocode_instagram']->run();
}

if ( ! function_exists( 'innocode_instagram' ) ) {
    /**
     * @return InstagramBasicDisplay
     */
    function innocode_instagram() : InstagramBasicDisplay {
        /**
         * @var Instagram\Plugin $innocode_instagram
         */
        global $innocode_instagram;

        if ( is_null( $innocode_instagram ) ) {
            trigger_error(
                'Missing required constants INSTAGRAM_CLIENT_ID and INSTAGRAM_CLIENT_SECRET.',
                E_USER_ERROR
            );
        }

        return $innocode_instagram->get_instagram();
    }
}
