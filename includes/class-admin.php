<?php

namespace InnocodeInstagram;

/**
 * Class Admin
 *
 * @package InnocodeInstagram
 */
final class Admin
{
    const PAGE = 'innocode-instagram';

    public static function register()
    {
        foreach ( Settings::get_sections() as $section => $title ) {
            static::add_section( $section, $title );
        }

        static::add_field( Settings::SECTION_GENERAL, 'access_token', __( 'Access Token', 'innocode-instagram' ), [
            'attrs' => [
                'disabled' => true,
            ],
        ] );
        static::add_field( Settings::SECTION_USER, 'id', __( 'ID' ), [
            'attrs' => [
                'disabled' => true,
            ],
        ] );
        static::add_field( Settings::SECTION_USER, 'username', __( 'Username' ), [
            'attrs' => [
                'disabled' => true,
            ],
        ] );
        static::add_field( Settings::SECTION_USER, 'full_name', __( 'Full Name', 'innocode-instagram' ), [
            'attrs' => [
                'disabled' => true,
            ],
        ] );
        static::add_field( Settings::SECTION_USER, 'profile_picture', __( 'Profile Picture' ), [
            'attrs' => [
                'disabled' => true,
            ],
        ], function () {
            $avatar = Settings::get( get_current_blog_id(), Settings::SECTION_USER, 'profile_picture' );

            if ( !empty( $avatar ) ) {
                $username = (string) Settings::get( get_current_blog_id(), Settings::SECTION_USER, 'username' );
                echo "<img src=\"$avatar\" alt=\"" . esc_attr( $username ) . '">';
            }
        } );
    }

    public static function register_menu_page()
    {
        add_action( 'admin_menu', function () {
            static::_add_menu_page();
        } );
    }

    /**
     * @param string        $key
     * @param string        $title
     * @param callable|null $callback
     */
    public static function add_section( $key, $title, callable $callback = null )
    {
        add_settings_section( innocode_instagram_sanitize_key( $key ), $title, $callback, static::PAGE );
    }

    /**
     * @param string        $section
     * @param string        $key
     * @param string        $title
     * @param array         $args
     * @param callable|null $callback
     */
    public static function add_field( $section, $key, $title, array $args = [], callable $callback = null )
    {
        $id = sanitize_key( static::PAGE . ( $section !== '' ? "-$section" : $section ) . "-$key" );
        $name = Settings::sanitize_key( $section, $key );
        $args = wp_parse_args( $args, [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );
        $attrs = wp_parse_args( isset( $args['attrs'] ) ? $args['attrs'] : [], [
            'type' => 'text',
        ] );
        unset( $args['attrs'] );

        if ( empty( $attrs['disabled'] ) ) {
            register_setting( innocode_instagram_sanitize_key(), $name, $args );
        }

        add_settings_field( $name, $title, !is_null( $callback ) ? $callback : function () use ( $section, $key, $name, $id, $args, $attrs ) {
            $type = $attrs['type'];
            unset( $attrs['type'] );
            $attrs = implode( ' ', array_map( function ( $name, $value ) {
                return esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
            }, array_keys( $attrs ), $attrs ) );

            switch ( $type ) {
                case 'textarea':
                    echo "<textarea id=\"$id\" name=\"$name\" cols=\"45\" rows=\"5\" $attrs>" . esc_html( Settings::get( get_current_blog_id(), $section, $key ) ) . '</textarea>';
                    break;
                default:
                    echo "<input id=\"$id\" type=\"" . esc_attr( $type ) . "\" name=\"$name\" value=\"" . esc_attr( Settings::get( get_current_blog_id(), $section, $key ) ) . "\" class=\"regular-text\" $attrs>";
                    break;
            }

            if ( !empty( $args['description'] ) ) {
                echo "<p class=\"description\">{$args['description']}</p>";
            }
        }, static::PAGE, innocode_instagram_sanitize_key( $section ), [
            'label_for' => $id,
        ] );
    }

    private static function _add_menu_page()
    {
        add_submenu_page( 'options-general.php', __( 'Instagram Settings', 'agrol-membership' ), __( 'Instagram', 'agrol-membership' ), 'manage_options', static::PAGE, function () {
            static::_render_page();
        } );
    }

    private static function _render_page()
    {
        try {
            $login_url = innocode_instagram()->getLoginUrl( innocode_instagram_scope() );
        } catch ( \Exception $exception ) {
            wp_die( $exception->getMessage(), \WP_Http::BAD_REQUEST );
        }

        $access_token = Settings::get_access_token();

        if ( $access_token !== '' ) {
            innocode_instagram()->setAccessToken( $access_token );
            $user = innocode_instagram()->getUser();

            if ( isset( $user->meta ) && isset( $user->meta->error_type ) ) {
                Settings::delete( get_current_blog_id(), Settings::SECTION_GENERAL, 'access_token' );
                $access_token = '';
                add_action( 'admin_notices', function () use ( $user ) {
                    static::_render_notice( sprintf( __( '<b>%s:</b> %s', 'innocode-instagram' ), $user->meta->error_type, $user->meta->error_message ) );
                } );
            }
        } ?>
        <div class="wrap">
            <h2><?php _e( 'Instagram Settings', 'innocode-instagram' ) ?></h2>
            <?php do_action( 'admin_notices' ) ?>
            <p class="submit">
                <a href="<?= $login_url ?>" class="button <?= $access_token === '' ? 'button-primary' : '' ?>">
                    <?= $access_token !== '' ? __( 'Log in as another user', 'innocode-instagram' ) : __( 'Log in' ) ?>
                </a>
            </p>
            <?php if ( $access_token !== '' ) :
                global $wp_registered_settings; ?>
                <form action="<?= admin_url( 'options.php' ) ?>" method="post">
                    <?php settings_fields( innocode_instagram_sanitize_key() );
                    do_settings_sections( static::PAGE );

                    if ( count( array_filter( $wp_registered_settings, function ( $setting ) {
                        return strpos( $setting, innocode_instagram_sanitize_key() ) === 0;
                    }, ARRAY_FILTER_USE_KEY ) ) > 0 ) :
                        submit_button();
                    endif; ?>
                </form>
            <?php endif ?>
        </div>
        <?php
    }

    private static function _render_notice( $message )
    {
        ?>
        <div class="notice notice-error">
            <p>
                <?= $message ?>
            </p>
        </div>
        <?php
    }
}