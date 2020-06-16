<?php

namespace Innocode\Instagram;

use EspressoDev\InstagramBasicDisplay\InstagramBasicDisplay;
use EspressoDev\InstagramBasicDisplay\InstagramBasicDisplayException;
use Innocode\Instagram\Admin\Field;
use Innocode\Instagram\Admin\OptionsPage;
use Innocode\Instagram\Admin\Section;
use Innocode\Instagram\Admin\Setting;
use WP_Http;

/**
 * Class Plugin
 * @package Innocode\Instagram
 */
final class Plugin
{
    const SECTION_GENERAL = '';
    const SECTION_USER = 'user';

    /**
     * @var string
     */
    private $client_id;
    /**
     * @var string
     */
    private $client_secret;
    /**
     * @var string
     */
    private $path;
    /**
     * @var Query
     */
    private $query;
    /**
     * @var OptionsPage
     */
    private $options_page;
    /**
     * @var InstagramBasicDisplay
     */
    private $instagram;

    /**
     * Plugin constructor.
     * @param string $client_id
     * @param string $client_secret
     * @param string $path
     * @throws InstagramBasicDisplayException
     */
    public function __construct( string $client_id, string $client_secret, string $path )
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->path = $path;

        $this->init_options_page();
        $this->init_sections();
        $this->init_fields();
        $this->init_query();
        $this->init_instagram();
    }

    public function run()
    {
        if ( is_main_site() ) {
            add_action( 'init', [ $this, 'add_rewrite_endpoints' ] );
            add_action( 'template_redirect', [ $this, 'handle_request' ] );
        }

        add_action( 'admin_menu', [ $this, 'add_options_page' ] );
        add_action( 'admin_init', [ $this, 'admin_init' ] );
        add_action( 'innocode_instagram_refresh', [ $this, 'refresh' ] );
    }

    /**
     * @return string
     */
    public function get_path()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function get_client_id()
    {
        return $this->client_id;
    }

    /**
     * @return string
     */
    public function get_client_secret()
    {
        return $this->client_secret;
    }

    /**
     * @return OptionsPage
     */
    public function get_options_page()
    {
        return $this->options_page;
    }

    /**
     * @return Query
     */
    public function get_query()
    {
        return $this->query;
    }

    /**
     * @return InstagramBasicDisplay
     */
    public function get_instagram()
    {
        return $this->instagram;
    }

    /**
     * @return string
     */
    public function get_views_dir()
    {
        return "{$this->get_path()}/resources/views";
    }

    /**
     * @param string $name
     * @return string
     */
    public function get_view_file( string $name )
    {
        return "{$this->get_views_dir()}/$name";
    }

    /**
     * @return string
     */
    public function get_redirect_uri()
    {
        return apply_filters(
            'innocode_instagram_auth_url',
            $this->get_query()->url( 'auth' )
        );
    }

    /**
     * @return array
     */
    public function get_scope()
    {
        return apply_filters(
            'innocode_instagram_scope',
            [ 'user_profile', 'user_media' ]
        );
    }

    /**
     * @return array
     */
    public function get_state()
    {
        return apply_filters(
            'innocode_instagram_state',
            get_current_blog_id() . ':' . wp_create_nonce( 'innocode_instagram-auth' )
        );
    }

    public function auth()
    {
        // WordPress clears $_GET['error'], so use $_REQUEST['error'] instead.
        $error = isset( $_REQUEST['error'] ) ? $_REQUEST['error'] : '';

        if ( $error ) {
            wp_die(
                '<h1>' . __( 'Error' ) . '</h1>' .
                sprintf(
                    '<p>%s</p>',
                    isset( $_GET['error_description'] )
                        ? esc_html( $_GET['error_description'] )
                        : __( 'Unknown error.', 'innocode-instagram' )
                ),
                WP_Http::FORBIDDEN
            );
        }

        $state = isset( $_GET['state'] ) ? wp_unslash( $_GET['state'] ) : '';

        list( $blog_id, $nonce ) = explode( ':', $state, 2 );

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'innocode_instagram-auth' ) ) {
            wp_die(
                '<h1>' . __( 'This link has expired.' ) . '</h1>' .
                '<p>' . __( 'Please try again.' ) . '</p>',
                WP_Http::FORBIDDEN
            );
        }

        if (
            ! $blog_id ||
            is_multisite() && false === ( $blog = get_blog_details( $blog_id ) )
        ) {
            wp_die(
                '<h1>' . __( 'Something went wrong.' ) . '</h1>' .
                '<p>' . __( 'Invalid blog ID.', 'innocode-instagram' ) . '</p>',
                WP_Http::BAD_REQUEST
            );
        }

        $blogname = isset( $blog ) ? $blog->blogname : get_bloginfo( 'name' );

        $options_page = $this->get_options_page();
        $options_page_url = $options_page->get_admin_url( $blog_id );
        $return_link = sprintf(
            '<p><a href="%s">%s</a>.</p>',
            $options_page_url,
            sprintf( __( 'Return to %s', 'innocode-instagram' ), $blogname )
        );

        $code = isset( $_GET['code'] ) ? $_GET['code'] : '';

        if ( ! $code ) {
            wp_die(
                '<h1>' . __( 'Something went wrong.' ) . '</h1>' .
                sprintf(
                    '<p>%s</p>%s',
                    __( 'Invalid code.', 'innocode-instagram' ),
                    $return_link
                ),
                WP_Http::BAD_REQUEST
            );
        }

        $instagram = $this->get_instagram();

        try {
            $oauth_token = $instagram->getOAuthToken( $code, true );
            $long_lived_token = $instagram->getLongLivedToken( $oauth_token );
        } catch ( InstagramBasicDisplayException $exception ) {
            wp_die(
                '<h1>' . __( 'Something went wrong.' ) . '</h1>' .
                sprintf(
                    '<p>%s</p>%s',
                    $exception->getMessage(),
                    $return_link
                ),
                WP_Http::INTERNAL_SERVER_ERROR
            );
        }

        $instagram->setAccessToken( $long_lived_token->access_token );

        $sections = $options_page->get_sections();
        $general_fields = $sections[ static::SECTION_GENERAL ]->get_fields();
        $general_fields['access_token']->get_setting()
            ->update_value( $long_lived_token->access_token, $blog_id );
        $general_fields['expires_on']->get_setting()
            ->update_value( time() + $long_lived_token->expires_in, $blog_id );

        try {
            $profile = $instagram->getUserProfile();
        } catch ( InstagramBasicDisplayException $exception ) {
            $this->delete_all_data( $blog_id );

            wp_die(
                '<h1>' . __( 'Something went wrong.' ) . '</h1>' .
                sprintf(
                    '<p>%s</p>%s',
                    $exception->getMessage(),
                    $return_link
                ),
                WP_Http::INTERNAL_SERVER_ERROR
            );
        }

        $user_fields = $sections[ static::SECTION_USER ]->get_fields();

        foreach ( $profile as $param => $value ) {
            if ( isset( $user_fields[ $param ] ) ) {
                $user_fields[ $param ]->get_setting()
                    ->update_value( $value, $blog_id );
            }
        }

        wp_redirect( $options_page_url );
    }

    private function init_options_page()
    {
        $this->options_page = new OptionsPage(
            Helpers::key( INNOCODE_INSTAGRAM ),
            'innocode-instagram',
            __( 'Instagram Settings', 'innocode-instagram' )
        );
        $this->options_page->set_menu_title( __( 'Instagram', 'innocode-instagram' ) );
        $this->options_page->set_view( 'options-page.php' );
    }

    private function init_sections()
    {
        $options_page = $this->get_options_page();
        $options_page_name = $options_page->get_name();

        foreach ( [
            static::SECTION_GENERAL => __( 'General Settings', 'innocode-instagram' ),
            static::SECTION_USER    => __( 'User', 'innocode-instagram' ),
        ] as $name => $title ) {
            $section = new Section( Helpers::key( $name, $options_page_name ), $title );
            $options_page->add_section( $name, $section );
        }
    }

    private function init_fields()
    {
        $options_page = $this->get_options_page();
        $sections = $options_page->get_sections();
        $general_section = $sections[ static::SECTION_GENERAL ];
        $general_section_name = $general_section->get_name();

        foreach ( [
            'access_token' => __( 'Access Token', 'innocode-instagram' ),
            'expires_on'   => __( 'Expires on', 'innocode-instagram' ),
        ] as $name => $title ) {
            $setting = new Setting( Helpers::key( $name, $general_section_name ), $title );
            $field = new Field();
            $field->set_setting( $setting );
            $field->add_attr( 'disabled', true );

            if ( $name == 'expires_on' ) {
                $field->set_callback( function ( Field $field ) {
                    if ( false === ( $expires_on = $field->get_setting()->get_value() ) ) {
                        return '';
                    }

                    if ( $expires_on - DAY_IN_SECONDS < time() ) {
                        return '<strong class="error-message">' . __( 'Missed schedule' ) . '</strong>';
                    }

                    return sprintf(
                        '<p><code>%s</code></p><p class="description"><strong>%s</strong> %s</p>',
                        date_i18n(
                            'Y-m-d H:i:s',
                            $expires_on
                        ),
                        __( 'Note:', 'innocode-instagram' ),
                        __( 'You don\'t need to worry about this date and login again. This will be done automatically a few days before the expiration.', 'innocode-instagram' )
                    );
                } );
            }

            $general_section->add_field( $name, $field );
        }

        $user_section = $sections[ static::SECTION_USER ];
        $user_section_name = $user_section->get_name();

        foreach ( [
            'account_type' => __( 'Account Type', 'innocode-instagram' ),
            'id'           => __( 'ID', 'innocode-instagram' ),
            'username'     => __( 'Username', 'innocode-instagram' ),
        ] as $name => $title ) {
            $setting = new Setting( Helpers::key( $name, $user_section_name ), $title );
            $field = new Field();
            $field->set_setting( $setting );
            $field->add_attr( 'disabled', true );
            $user_section->add_field( $name, $field );
        }
    }

    private function init_query()
    {
        $this->query = new Query(
            defined( 'INNOCODE_INSTAGRAM_ENDPOINT' )
                ? INNOCODE_INSTAGRAM_ENDPOINT
                : 'instagram'
        );
        $this->query->add_route(
            'auth',
            [ $this, 'auth' ],
            $this->get_options_page()->get_capability()
        );
    }

    /**
     * @throws InstagramBasicDisplayException
     */
    private function init_instagram()
    {
        $this->instagram = new InstagramBasicDisplay( [
            'appId'       => $this->get_client_id(),
            'appSecret'   => $this->get_client_secret(),
            'redirectUri' => $this->get_redirect_uri(),
        ] );
        $access_token = $this->get_options_page()
            ->get_sections()[ static::SECTION_GENERAL ]
            ->get_fields()['access_token']
            ->get_setting()
            ->get_value();

        if ( $access_token ) {
            $this->instagram->setAccessToken( $access_token );
        }
    }

    public function add_rewrite_endpoints()
    {
        $endpoint = $this->get_query()->get_endpoint();

        add_rewrite_endpoint(
            $endpoint,
            apply_filters( 'innocode_instagram_endpoint_mask', EP_ROOT, $endpoint )
        );
    }

    public function handle_request()
    {
        $this->get_query()->handle_request();
    }

    public function add_options_page()
    {
        $options_page = $this->get_options_page();

        add_options_page(
            $options_page->get_title(),
            $options_page->get_menu_title(),
            $options_page->get_capability(),
            $options_page->get_menu_slug(),
            function () {
                $view = $this->get_options_page()->get_view();
                $file = $this->get_view_file( $view );

                require_once $file;
            }
        );
    }

    public function admin_init()
    {
        $this->add_sections();
        $this->add_fields();
        $options_page_hook = $this->get_options_page()->get_hook();

        add_action( "load-$options_page_hook", function () {
            $this->verify_access_token();
        } );
    }

    public function add_sections()
    {
        $options_page = $this->get_options_page();
        $options_page_slug = $options_page->get_menu_slug();

        foreach ( $options_page->get_sections() as $section ) {
            add_settings_section(
                $section->get_name(),
                $section->get_title(),
                null,
                $options_page_slug
            );
        }
    }

    public function add_fields()
    {
        $options_page = $this->get_options_page();
        $options_page_slug = $options_page->get_menu_slug();

        foreach ( $options_page->get_sections() as $section ) {
            $section_name = $section->get_name();

            foreach ( $section->get_fields() as $field ) {
                $setting = $field->get_setting();
                $setting_name = $setting->get_name();

                register_setting( $options_page_slug, $setting_name, $setting->get_args() );
                add_settings_field(
                    $setting_name,
                    $setting->get_title(),
                    function () use ( $field ) {
                        echo $field->get_html();
                    },
                    $options_page_slug,
                    $section_name,
                    [
                        'label_for' => $setting_name,
                    ]
                );
            }
        }
    }

    // @TODO: move error check to REST and JS
    public function verify_access_token()
    {
        $instagram = $this->get_instagram();
        $access_token = $instagram->getAccessToken();

        if ( ! $access_token ) {
            $this->delete_all_data();

            return;
        }

        $profile = $instagram->getUserProfile();

        if ( isset( $profile->error ) ) {
            add_action( 'admin_notices', function () use ( $profile ) {
                $message = sprintf(
                    '<b>%s:</b> %s',
                    $profile->error->type,
                    $profile->error->message
                );
                $file = $this->get_view_file( 'notice-error.php' );

                require_once $file;
            } );
        }
    }

    /**
     * @param int|null $blog_id
     */
    public function delete_all_data( int $blog_id = null )
    {
        foreach ( $this->get_options_page()->get_sections() as $section ) {
            foreach ( $section->get_fields() as $field ) {
                $field->get_setting()->delete_value( $blog_id );
            }
        }
    }

    public function refresh()
    {
        $instagram = $this->get_instagram();
        $access_token = $instagram->getAccessToken();

        if ( ! $access_token ) {
            $this->delete_all_data();

            return;
        }

        $options_page = $this->get_options_page();
        $sections = $options_page->get_sections();
        $general_fields = $sections[ static::SECTION_GENERAL ]->get_fields();
        $expires_on_setting = $general_fields['expires_on']->get_setting();
        $expires_on = $expires_on_setting->get_value();

        // Starts to try refresh a few days before expiration.
        if ( false === $expires_on || $expires_on - 3 * DAY_IN_SECONDS < time() ) {
            $long_lived_token = $instagram->refreshToken( $access_token );

            $instagram->setAccessToken( $long_lived_token->access_token );

            $general_fields['access_token']->get_setting()
                ->update_value( $long_lived_token->access_token );
            $expires_on_setting
                ->update_value( time() + $long_lived_token->expires_in );
        }

        try {
            $profile = $instagram->getUserProfile();
        } catch ( InstagramBasicDisplayException $exception ) {
            return;
        }

        $user_fields = $sections[ static::SECTION_USER ]->get_fields();

        foreach ( $profile as $param => $value ) {
            if ( isset( $user_fields[ $param ] ) ) {
                $user_fields[ $param ]->get_setting()
                    ->update_value( $value );
            }
        }
    }
}
