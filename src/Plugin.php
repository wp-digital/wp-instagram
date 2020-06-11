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
     * @var Instagram
     */
    private $instagram;

    /**
     * Plugin constructor.
     * @param string $path
     * @throws Exception
     */
    public function __construct( $path )
    {
        $this->path = $path;
        $this->init_query();
        $this->init_options_page();
        $this->init_sections();
        $this->init_fields();
        $this->init_instagram();
    }

    public function run()
    {
        if ( is_main_site() ) {
            add_action( 'init', function () {
                $this->add_rewrite_endpoints();
            } );
            add_action( 'template_redirect', function () {
                $this->get_query()->handle_request();
            } );
        }

        add_action( 'admin_menu', function () {
            $this->add_options_page();
        } );
        add_action( 'admin_init', function () {
            $this->add_sections();
            $this->add_fields();
            $options_page_hook = $this->get_options_page()->get_hook();

            add_action( "load-$options_page_hook", function () {
                $this->verify_access_token();
            } );
        } );
    }

    /**
     * @return string
     */
    public function get_path()
    {
        return $this->path;
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
     * @return Instagram
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
        return "$this->path/resources/views";
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

            if ( is_multisite() ) {
                update_blog_option( $blog_id, $name, $oauth_token->access_token );
            } else {
                update_option( $name, $oauth_token->access_token );
            }
        }

        $user_fields = $sections['user']->get_fields();

        if ( isset( $oauth_token->user ) && $oauth_token->user instanceof stdClass ) {
            foreach ( $oauth_token->user as $param => $value ) {
                if ( ! isset( $user_fields[ $param ] ) ) {
                    continue;
                }

                $name = $user_fields[ $param ]->get_setting()->get_name();

                if ( is_multisite() ) {
                    update_blog_option( $blog_id, $name, $value );
                } else {
                    update_option( $name, $value );
                }
            }
        }

        wp_redirect( $options_page_url );
    }

    private function init_query()
    {
        $this->query = new Query(
            defined( 'INNOCODE_INSTAGRAM_ENDPOINT' ) ? INNOCODE_INSTAGRAM_ENDPOINT : 'instagram'
        );
        $this->query->add_route( 'auth', [ $this, 'auth' ] );
    }

    /**
     * @throws Exception
     */
    private function init_instagram()
    {
        $this->instagram = new Instagram( [
            'apiKey'      => defined( 'INSTAGRAM_CLIENT_ID' ) ? INSTAGRAM_CLIENT_ID : '',
            'apiSecret'   => defined( 'INSTAGRAM_CLIENT_SECRET' ) ? INSTAGRAM_CLIENT_SECRET : '',
            'apiCallback' => $this->get_api_callback(),
        ] );
        $access_token = $this->get_options_page()
            ->get_sections()['']
            ->get_fields()['access_token']
            ->get_setting()
            ->get_value();
        $this->instagram->setAccessToken( $access_token );
    }

    private function init_options_page()
    {
        $this->options_page = new OptionsPage(
            Helpers::key( INNOCODE_INSTAGRAM ),
            'innocode-instagram',
            __( 'Instagram Settings', 'agrol-membership' )
        );
        $this->options_page->set_menu_title( __( 'Instagram', 'agrol-membership' ) );
        $this->options_page->set_view( 'options-page.php' );
    }

    private function init_sections()
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

    private function init_fields()
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

    private function add_rewrite_endpoints()
    {
        $endpoint = $this->get_query()->get_endpoint();

        add_rewrite_endpoint(
            $endpoint,
            apply_filters( 'innocode_instagram_endpoint_mask', EP_ROOT, $endpoint )
        );
    }

    private function add_options_page()
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

    private function add_sections()
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

    private function add_fields()
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

    private function verify_access_token()
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

            delete_option( $name );
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
