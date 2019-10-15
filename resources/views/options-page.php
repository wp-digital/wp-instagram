<?php
/**
 * @var \Innocode\Instagram\Plugin $this
 */
$options_page = $this->get_options_page();
$options_page_name = $options_page->get_name();
$instagram = $this->get_instagram();

try {
    $login_url = $instagram->getLoginUrl( $this->get_scope() );
} catch ( Exception $exception ) {
    wp_die( $exception->getMessage(), WP_Http::BAD_REQUEST );
}

$access_token = $options_page->get_sections()['']
    ->get_fields()['access_token']
    ->get_setting()
    ->get_value();
?>
<div class="wrap">
    <h2><?= $options_page->get_title() ?></h2>
    <p class="submit">
        <a href="<?= $login_url ?>" class="button <?= $access_token === '' ? 'button-primary' : '' ?>">
            <?= $access_token !== '' ? __( 'Log in as another user', 'innocode-instagram' ) : __( 'Log in' ) ?>
        </a>
    </p>
    <?php if ( $access_token !== '' ) : ?>
        <form action="<?= admin_url( 'options.php' ) ?>" method="post">
            <?php settings_fields( $options_page_name );
            do_settings_sections( $options_page->get_menu_slug() );

            if ( $options_page->has_enabled_fields() ) :
                submit_button();
            endif; ?>
        </form>
    <?php endif ?>
</div>
