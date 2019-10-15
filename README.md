# Instagram

### Description

Enables Instagram API for developers.

The idea of plugin is to use [Instagram-PHP-API](https://github.com/cosenary/Instagram-PHP-API)
with ability to get access token through WordPress admin panel.

### Install

- Preferable way is to use [Composer](https://getcomposer.org/):

    ````
    composer require innocode-digital/wp-instagram
    ````

    By default it will be installed as [Must Use Plugin](https://codex.wordpress.org/Must_Use_Plugins).
    But it's possible to control with `extra.installer-paths` in `composer.json`.

- Alternate way is to clone this repo to `wp-content/mu-plugins/` or `wp-content/plugins/`:

    ````
    cd wp-content/plugins/
    git clone git@github.com:innocode-digital/wp-instagram.git
    cd wp-instagram/
    composer install
    ````

If plugin was installed as regular plugin then activate **Instagram** from Plugins page 
or [WP-CLI](https://make.wordpress.org/cli/handbook/): `wp plugin activate wp-instagram`.

### Usage

1. Add required constants (usually to `wp-config.php`):

    ````
    define( 'INSTAGRAM_CLIENT_ID', '' );
    define( 'INSTAGRAM_CLIENT_SECRET', '' );
    ````
    
2. Add site auth URL `https://site.com/instagram/auth/ ` to **Valid redirect URIs** 
in **Security** tab of client on [Instagram](https://www.instagram.com/developer/) 

3. Open settings page in WordPress admin panel **Settings** -> **Instagram** 
`/wp-admin/options-general.php?page=innocode-instagram`

4. Click on **Log in** button or **Log in as another user** in case when should change
account.

5. Start use [Instagram-PHP-API](https://github.com/cosenary/Instagram-PHP-API) through
`innocode_instagram();` function. E.g. `innocode_instagram()->getUser();`.

### Notes

If site is a part of [Multisite](https://wordpress.org/support/article/create-a-network/)
then main site auth URL should be added in **Valid redirect URIs**.

### Documentation

By default endpoint auth URL is using `instagram` as endpoint but it's possible to
change with constant:

````
define( 'INNOCODE_INSTAGRAM_ENDPOINT', '' );
````

---

It's possible to change full auth URL:

````
add_filter( 'innocode_instagram_auth_url', function ( $url ) {
    return $url;
} );
````

---

It's possible to change Instagram permission:

````
add_filter( 'innocode_instagram_scope', function ( array $scope ) {
    return $scope; // Default is array containing 'basic'.
} );
````

---

It's possible to change place where endpoint should be added:

````
add_filter( 'innocode_instagram_endpoint_mask', function ( $mask, $endpoint ) {
    return $mask; // Default is EP_ROOT constant.
}, 10, 2 );
````
