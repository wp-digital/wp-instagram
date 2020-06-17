# WordPress Instagram integration

### Description

Enables Instagram API for developers.

The idea of plugin is to use [Instagram Basic Display PHP API](https://github.com/espresso-dev/instagram-basic-display-php)
with ability to get access token through WordPress admin panel.

### Install

- Preferable way is to use [Composer](https://getcomposer.org/):

    ````
    composer require innocode-digital/wp-instagram
    ````

- Alternate way is to clone this repo to `wp-content/plugins/`:

    ````
    cd wp-content/plugins/
    git clone git@github.com:innocode-digital/wp-instagram.git
    cd wp-instagram/
    composer install
    ````

Activate **Instagram** with [WP-CLI](https://make.wordpress.org/cli/handbook/)
`wp plugin activate wp-instagram` or from Plugins page.

### Usage

1. Check [Facebook Manual](https://developers.facebook.com/docs/instagram-basic-display-api/getting-started) on how to
create an APP.

2. Add required constants (usually to `wp-config.php`):

    ````
    define( 'INSTAGRAM_CLIENT_ID', '' );
    define( 'INSTAGRAM_CLIENT_SECRET', '' );
    ````
    
3. Add site auth URL `https://site.com/instagram/auth/` to **Valid OAuth Redirect URIs** in **Basic Display**.

4. Open settings page in WordPress admin panel **Settings** -> **Instagram** 
`/wp-admin/options-general.php?page=innocode-instagram`

5. Click on **Log in** button or **Log in as another user** in case when should change
account.

6. Start use [Instagram Basic Display PHP API](https://github.com/cosenary/Instagram-PHP-API) through
`innocode_instagram();` function. E.g. `innocode_instagram()->getUserProfile();`.

7. (optional) Add site deauth REST API endpoint `https://site.com/wp-json/innocode/v1/instagram/deauth` to
**Deauthorize Callback URL** in **Basic Display**. 

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
add_filter( 'innocode_instagram_redirect_uri', function ( string $url ) {
    return $url;
} );
````

---

It's possible to change Instagram permission:

````
add_filter( 'innocode_instagram_scope', function ( array $scope ) {
    return $scope; // Default is array containing 'user_profile' and 'user_media'.
} );
````

---

It's possible to change state parameter which is sending with auth request:

````
add_filter( 'innocode_instagram_state', function ( string $state ) {
    return $scope; // Default is string in format '$blog_id:$nonce'.
} );
````

---

It's possible to change place where endpoint should be added:

````
add_filter( 'innocode_instagram_endpoint_mask', function ( $mask, $endpoint ) {
    return $mask; // Default is EP_ROOT constant.
}, 10, 2 );
````
