<?php

namespace Innocode\Instagram;

use Innocode\Instagram\Admin\Field;
use Innocode\Instagram\Admin\OptionsPage;
use Innocode\Instagram\Admin\Section;
use Innocode\Instagram\Admin\Setting;
use MetzWeb\Instagram\Instagram;
use Exception;
use WP_Http;
use stdClass;

/**
 * Class Plugin
 * @package Innocode\Instagram
 */
final class Plugin
{
    /**
     * @var string
     */
    private $_path;
    /**
     * @var Query
     */
    private $_query;
    /**
     * @var OptionsPage
     */
    private $_options_page;
    /**
     * @var Instagram
     */
    private $_instagram;

    /**
     * Plugin constructor.
     * @param string $path
     * @throws Exception
     */
    public function __construct( $path )
    {
        $this->_path = $path;
        $this->_init_query();
        $this->_init_options_page();
        $this->_init_sections();
        $this->_init_fields();
        $this->_init_instagram();
    }

    public function run()
    {
        if ( is_main_site() ) {
            add_action( 'init', function () {
                $this->_add_rewrite_endpoints();
            } );
            add_action( 'template_redirect', function () {
                $this->get_query()->handle_request();
            } );
        }

        add_action( 'admin_menu', function () {
            $this->_add_options_page();
        } );
        add_action( 'admin_init', function () {
            $this->_add_sections();
            $this->_add_fields();
            $options_page_hook = $this->get_options_page()->get_hook();

            add_action( "load-$options_page_hook", function () {
                $this->_verify_access_token();
            } );
        } );
    }

    /**
     * @return string
     */
    public function get_path()
    {
        return $this->_path;
    }

    /**
     * @return OptionsPage
     */
    public function get_options_page()
    {
        return $this->_options_page;
    }

    /**
     * @return Query
     */
    public function get_query()
    {
        return $this->_query;
    }

    /**
     * @return Instagram
     */
    public function get_instagram()
    {
        return $this->_instagram;
    }

    /**
     * @return string
     */
    public function get_views_dir()
    {
        return "$this->_path/resources/views";
    }

    /**
     * @param string $name
     * @return string
     */
    public function get_view_file( $name )
    {
        return "{$this->get_views_dir()}/$name";
    }

    /**
     * @param int $blog_id
     * @return string
     */
    public function get_api_callback( $blog_id = 0 )
    {
        $blog_id = $blog_id ? $blog_id : get_current_blog_id();
        $url = apply_filters( 'innocode_instagram_auth_url', $this->get_query()->url( 'auth' ) );

        return add_query_arg( 'blog_id', $blog_id, $url );
    }

    /**
     * @return array
     */
    public function get_scope()
    {
        return apply_filters( 'innocode_instagram_scope', [ 'basic' ] );
    }

    public function auth()
    {
        $blog_id = isset( $_GET['blog_id'] ) ? intval( $_GET['blog_id'] ) : 0;

        if ( ! $blog_id || false === ( $blog = get_blog_details( $blog_id ) ) ) {
            wp_die(
                '<h1>' . __( 'Something went wrong.' ) . '</h1>' .
                '<p>' . __( 'Invalid blog ID.', 'innocode-instagram' ) . '</p>',
                WP_Http::BAD_REQUEST
            );
        }

        $options_page = $this->get_options_page();
        $options_page_url = $options_page->get_admin_url( $blog->blog_id );
        $return_link = sprintf(
            '<p><a href="%s">%s</a>.</p>',
            $options_page_url,
            sprintf( __( 'Return to %s', 'innocode-instagram' ), $blog->blogname )
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
        $api_callback = $this->get_api_callback( $blog_id );
        $instagram->setApiCallback( $api_callback );
        $oauth_token = $instagram->getOAuthToken( $code );

        if ( isset( $oauth_token->error_type ) ) {
            wp_die(
                '<h1>' . __( 'Something went wrong.' ) . '</h1>' .
                sprintf(
                    '<p>%s</p>%s',
                    $oauth_token->error_message,
                    $return_link
                ),
                $oauth_token->code
            );
        }

        $sections = $options_page->get_sections();
        $general_fields = $sections['']->get_fields();

        if ( isset( $oauth_token->access_token ) ) {
            $name = $general_fields['access_token']->get_setting()->get_name();

            update_blog_option( $blog->blog_id, $name, $oauth_token->access_token );
        }

        $user_fields = $sections['user']->get_fields();

        if ( isset( $oauth_token->user ) && $oauth_token->user instanceof stdClass ) {
            foreach ( $oauth_token->user as $param => $value ) {
                if ( ! isset( $user_fields[ $param ] ) ) {
                    continue;
                }

                $name = $user_fields[ $param ]->get_setting()->get_name();

                update_blog_option( $blog->blog_id, $name, $value );
            }
        }

        wp_redirect( $options_page_url );
    }

    private function _init_query()
    {
        $this->_query = new Query(
            defined( 'INNOCODE_INSTAGRAM_ENDPOINT' ) ? INNOCODE_INSTAGRAM_ENDPOINT : 'instagram'
        );
        $this->_query->add_route( 'auth', [ $this, 'auth' ] );
    }

    /**
     * @throws Exception
     */
    private function _init_instagram()
    {
        $this->_instagram = new Instagram( [
            'apiKey'      => defined( 'INSTAGRAM_CLIENT_ID' ) ? INSTAGRAM_CLIENT_ID : '',
            'apiSecret'   => defined( 'INSTAGRAM_CLIENT_SECRET' ) ? INSTAGRAM_CLIENT_SECRET : '',
            'apiCallback' => $this->get_api_callback(),
        ] );
        $access_token = $this->get_options_page()
            ->get_sections()['']
            ->get_fields()['access_token']
            ->get_setting()
            ->get_value();
        $this->_instagram->setAccessToken( $access_token );
    }

    private function _init_options_page()
    {
        $this->_options_page = new OptionsPage(
            Helpers::key( INNOCODE_INSTAGRAM ),
            'innocode-instagram',
            __( 'Instagram Settings', 'agrol-membership' )
        );
        $this->_options_page->set_menu_title( __( 'Instagram', 'agrol-membership' ) );
        $this->_options_page->set_view( 'options-page.php' );
    }

    private function _init_sections()
    {
        $options_page = $this->get_options_page();
        $options_page_name = $options_page->get_name();

        foreach ( [
            ''     => __( 'General Settings', 'innocode-instagram' ),
            'user' => __( 'User', 'innocode-instagram' ),
        ] as $name => $title ) {
            $section = new Section( Helpers::key( $name, $options_page_name ), $title );
            $options_page->add_section( $name, $section );
        }
    }

    private function _init_fields()
    {
        $options_page = $this->get_options_page();
        $sections = $options_page->get_sections();

        $general_section_name = $sections['']->get_name();

        $name = 'access_token';
        $setting = new Setting( Helpers::key( $name, $general_section_name ), __( 'Access Token', 'innocode-instagram' ) );
        $field = new Field();
        $field->set_setting( $setting );
        $field->add_attr( 'disabled', true );
        $sections['']->add_field( $name, $field );

        $user_section_name = $sections['user']->get_name();

        foreach ( [
            'id'              => __( 'ID', 'innocode-instagram' ),
            'username'        => __( 'Username', 'innocode-instagram' ),
            'full_name'       => __( 'Full Name', 'innocode-instagram' ),
            'profile_picture' => __( 'Profile Picture', 'innocode-instagram' ),
        ] as $name => $title ) {
            $setting = new Setting( Helpers::key( $name, $user_section_name ), $title );
            $field = new Field();
            $field->set_setting( $setting );
            $field->add_attr( 'disabled', true );
            $sections['user']->add_field( $name, $field );
        }

        $name = 'profile_picture';
        $setting = new Setting(
            Helpers::key( $name, $user_section_name ),
            __( 'Profile Picture', 'innocode-instagram' )
        );
        $field = new Field();
        $field->set_setting( $setting );
        $field->add_attr( 'disabled', true );
        $field->set_callback( function ( Field $field ) {
            $avatar = $field->get_setting()->get_value();

            if ( $avatar ) {
                $username = $this->get_options_page()
                    ->get_sections()['user']
                    ->get_fields()['username']
                    ->get_setting()->get_value();

                return "<img src=\"$avatar\" alt=\"{$username}\">";
            }

            return '';
        } );
        $sections['user']->add_field( $name, $field );
    }

    private function _add_rewrite_endpoints()
    {
        $endpoint = $this->get_query()->get_endpoint();

        add_rewrite_endpoint(
            $endpoint,
            apply_filters( 'innocode_instagram_endpoint_mask', EP_ROOT, $endpoint )
        );
    }

    private function _add_options_page()
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

    private function _add_sections()
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

    private function _add_fields()
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

    private function _verify_access_token()
    {
        $instagram = $this->get_instagram();
        $access_token = $instagram->getAccessToken();

        if ( ! $access_token ) {
            return;
        }

        $user = $instagram->getUser();

        if ( isset( $user->meta ) && isset( $user->meta->error_type ) ) {
            $instagram->setAccessToken( '' );
            $name = $this->get_options_page()
                ->get_sections()['']
                ->get_fields()['access_token']
                ->get_setting()->get_name();

            delete_blog_option( get_current_blog_id(), $name );
            add_action( 'admin_notices', function () use ( $user ) {
                $message = sprintf(
                    '<b>%s:</b> %s',
                    $user->meta->error_type,
                    $user->meta->error_message
                );
                $file = $this->get_view_file( 'notice-error.php' );

                require_once $file;
            } );
        }
    }
}
