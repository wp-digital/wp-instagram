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
     * App ID.
     * @var string
     */
    private $client_id;
    /**
     * App secret.
     * @var string
     */
    private $client_secret;
    /**
     * Plugin __FILE__.
     * @var string
     */
    private $file;
    /**
     * Query object.
     * @var Query
     */
    private $query;
    /**
     * Admin page.
     * @var OptionsPage
     */
    private $options_page;
    /**
     * Instagram client.
     * @var InstagramBasicDisplay
     */
    private $instagram;
    /**
     * REST controller.
     * @var RESTController
     */
    private $rest_controller;
    /**
     * App site.
     * @var AppSite
     */
    private $app_site;

    /**
     * Plugin constructor.
     * @param string $client_id
     * @param string $client_secret
     * @param string $file
     * @throws InstagramBasicDisplayException
     */
    public function __construct( string $client_id, string $client_secret, string $file )
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->file = $file;

        $this->init_options_page();
        $this->init_sections();
        $this->init_fields();
        $this->init_query();
        $this->init_instagram();
        $this->init_rest_controller();
    }

    /**
     * Adds hooks.
     */
    public function run()
    {
        add_action( 'init', [ $this, 'add_rewrite_endpoints' ] );
        add_action( 'template_redirect', [ $this, 'handle_request' ] );

        $user_id_setting_name = $this->get_options_page()
            ->get_sections()[ static::SECTION_USER ]
            ->get_fields()['id']
            ->get_setting()
            ->get_name();

        add_action( "add_option_$user_id_setting_name", [ $this, 'add_user_id' ], 10, 2 );
        add_action( "update_option_$user_id_setting_name", [ $this, 'update_user_id' ], 10, 3 );
        add_action( 'delete_option', [ $this, 'delete_user_id' ] );

        add_action( 'admin_menu', [ $this, 'add_options_page' ] );
        add_action( 'admin_init', [ $this, 'admin_init' ] );
        add_action( 'innocode_instagram_scheduled_refresh', [ $this, 'scheduled_refresh' ] );
        add_action( 'init', [ $this, 'schedule' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        $file = $this->get_file();

        register_activation_hook( $file, [ $this, 'activate' ] );
        register_deactivation_hook( $file, [ $this, 'deactivate' ] );
    }

    /**
     * Returns plugin __FILE__.
     * @return string
     */
    public function get_file()
    {
        return $this->file;
    }

    /**
     * Returns app ID.
     * @return string
     */
    public function get_client_id()
    {
        return $this->client_id;
    }

    /**
     * Returns app secret.
     * @return string
     */
    public function get_client_secret()
    {
        return $this->client_secret;
    }

    /**
     * Returns admin page.
     * @return OptionsPage
     */
    public function get_options_page()
    {
        return $this->options_page;
    }

    /**
     * Returns query object.
     * @return Query
     */
    public function get_query()
    {
        return $this->query;
    }

    /**
     * Returns Instagram client.
     * @return InstagramBasicDisplay
     */
    public function get_instagram()
    {
        return $this->instagram;
    }

    /**
     * Returns REST controller.
     * @return RESTController
     */
    public function get_rest_controller()
    {
        return $this->rest_controller;
    }

    /**
     * Returns app site.
     * @return string
     */
    public function get_app_site()
    {
        return $this->app_site;
    }

    /**
     * Returns plugin path.
     * @return string
     */
    public function get_path()
    {
        return dirname( $this->get_file() );
    }

    /**
     * Returns plugin path to views dir.
     * @return string
     */
    public function get_views_dir()
    {
        return "{$this->get_path()}/resources/views";
    }

    /**
     * Returns plugin view file.
     * @param string $name
     * @return string
     */
    public function get_view_file( string $name )
    {
        return "{$this->get_views_dir()}/$name";
    }

    /**
     * Returns redirect_uri parameter.
     * @return string
     */
    public function get_redirect_uri()
    {
        return apply_filters(
            'innocode_instagram_redirect_uri',
            $this->get_query()->url( 'auth' )
        );
    }

    /**
     * Returns scope parameter.
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
     * Returns state parameter.
     * @return array
     */
    public function get_state()
    {
        $original_user_id = get_current_user_id();
        wp_set_current_user( 0 );
        $nonce = wp_create_nonce( 'innocode_instagram-auth' );
        wp_set_current_user( $original_user_id );

        return apply_filters(
            'innocode_instagram_state',
            get_current_blog_id() . ":$nonce"
        );
    }

    /**
     * User authorization.
     */
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

        if ( false === strpos( $state, ':' ) ) {
            wp_die(
                '<h1>' . __( 'Something went wrong.' ) . '</h1>' .
                '<p>' . __( 'Invalid state.', 'innocode-instagram' ) . '</p>',
                WP_Http::BAD_REQUEST
            );
        }

        list( $blog_id, $nonce ) = explode( ':', $state, 2 );

        if ( ! wp_verify_nonce( $nonce, 'innocode_instagram-auth' ) ) {
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

    /**
     * User deauthorization.
     */
    public function deauth()
    {
        $instagram = $this->get_instagram();
        $access_token = $instagram->getAccessToken();

        if ( $access_token ) {
            $this->delete_all_data();
        }

        wp_redirect( $this->get_options_page()->get_admin_url() );
    }

    /**
     * Creates admin page.
     */
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

    /**
     * Creates admin page sections.
     */
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

    /**
     * Creates admin page fields.
     */
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

    /**
     * Create query object.
     */
    private function init_query()
    {
        $this->query = new Query(
            defined( 'INNOCODE_INSTAGRAM_ENDPOINT' )
                ? INNOCODE_INSTAGRAM_ENDPOINT
                : 'instagram'
        );
        $capability = $this->get_options_page()->get_capability();
        $this->query->add_route( 'auth', [ $this, 'auth' ] );
        $this->query->add_route( 'deauth', [ $this, 'deauth' ], $capability );
    }

    /**
     * Creates Instagram client.
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

    /**
     * Creates REST controller.
     */
    private function init_rest_controller()
    {
        $this->rest_controller = new RESTController();
    }

    /**
     * Sets app site.
     * @param string $url
     */
    public function init_app_site( string $url )
    {
        $this->app_site = new AppSite( $url );
    }

    /**
     * Adds rewrite endpoints.
     */
    public function add_rewrite_endpoints()
    {
        $endpoint = $this->get_query()->get_endpoint();

        add_rewrite_endpoint(
            $endpoint,
            apply_filters( 'innocode_instagram_endpoint_mask', EP_ROOT, $endpoint )
        );
    }

    /**
     * Handles request.
     */
    public function handle_request()
    {
        $this->get_query()->handle_request();
    }

    /**
     * Adds admin page.
     */
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

    /**
     * Adds admin page sections and fields.
     */
    public function admin_init()
    {
        $this->add_sections();
        $this->add_fields();
    }

    /**
     * Adds admin page sections.
     */
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

    /**
     * Adds admin page fields.
     */
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

    /**
     * Deletes all data including token and profile.
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

    /**
     * Refreshes token and profile data.
     */
    public function scheduled_refresh()
    {
        $instagram = $this->get_instagram();
        $access_token = $instagram->getAccessToken();

        if ( ! $access_token ) {
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

    /**
     * Schedules or unschedules refresh token and profile data action.
     */
    public function schedule()
    {
        $instagram = $this->get_instagram();
        $access_token = $instagram->getAccessToken();

        if ( ! $access_token ) {
            $this->unschedule();

            return;
        }

        if (
            ! wp_next_scheduled( 'innocode_instagram_scheduled_refresh' ) &&
            ! wp_installing()
        ) {
            wp_schedule_event(
                time(),
                'daily',
                'innocode_instagram_scheduled_refresh'
            );
        }
    }

    /**
     * Unschedules refresh token and profile data action.
     */
    public function unschedule()
    {
        if (
            false !== ( $timestamp = wp_next_scheduled( 'innocode_instagram_scheduled_refresh' ) ) &&
            ! wp_installing()
        ) {
            wp_unschedule_event( $timestamp, 'innocode_instagram_scheduled_refresh' );
        }
    }

    /**
     * Adds routes.
     */
    public function register_rest_routes()
    {
        $rest_controller = $this->get_rest_controller();
        $rest_controller->register_routes();
        $app_site = $this->get_app_site();

        if ( $app_site && $app_site->is_current_site() ) {
            $rest_controller->register_app_site_routes();
        }
    }

    /**
     * Handles user_id setting create.
     * @param string $option
     * @param mixed  $value
     */
    public function add_user_id( string $option, $value )
    {
        if ( is_multisite() ) {
            add_site_meta( get_current_blog_id(), $option, $value );
        }

        $app_site = $this->get_app_site();

        if ( $app_site && ! $app_site->is_current_site() ) {
            $app_site->add_current_site( $value );
        }
    }

    /**
     * Handles user_id setting update.
     * @param mixed  $old_value
     * @param mixed  $value
     * @param string $option
     */
    public function update_user_id( $old_value, $value, string $option )
    {
        if ( is_multisite() ) {
            update_site_meta( get_current_blog_id(), $option, $value );
        }

        $app_site = $this->get_app_site();

        if ( $app_site && ! $app_site->is_current_site() ) {
            $app_site->update_current_site( $old_value, $value );
        }
    }

    /**
     * Handles user_id setting delete.
     * @param string $option
     */
    public function delete_user_id( string $option )
    {
        $user_id_setting = $this->get_options_page()
            ->get_sections()[ static::SECTION_USER ]
            ->get_fields()['id']
            ->get_setting();

        if ( $option != $user_id_setting->get_name() ) {
            return;
        }

        if ( is_multisite() ) {
            delete_site_meta( get_current_blog_id(), $option );
        }

        $app_site = $this->get_app_site();

        if ( $app_site && ! $app_site->is_current_site() ) {
            $app_site->delete_current_site( $user_id_setting->get_value() );
        }
    }

    /**
     * Plugin activation action.
     */
    public function activate()
    {
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation action.
     */
    public function deactivate()
    {
        $this->unschedule();
    }
}
