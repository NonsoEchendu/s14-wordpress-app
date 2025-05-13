<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Tell WordPress it's behind an SSL-terminating proxy **
// This is crucial for Dokku and similar setups.
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) {
    $_SERVER['HTTPS'] = 'on';
}
// Some proxies might use X-Forwarded-Ssl
if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
    $_SERVER['HTTPS'] = 'on';
}


// ** WordPress Site URL and Home URL from Environment Variables **
// Dokku sets these via `dokku config:set`
if (getenv('WP_HOME')) {
    define('WP_HOME', getenv('WP_HOME'));
}
if (getenv('WP_SITEURL')) {
    define('WP_SITEURL', getenv('WP_SITEURL'));
}

// ** Database settings from DATABASE_URL environment variable (set by Dokku data-link) **
$db_url = getenv('DATABASE_URL');
if ($db_url) {
    $db_parts = parse_url($db_url);
    define('DB_NAME',     ltrim($db_parts['path'], '/'));
    define('DB_USER',     $db_parts['user']);
    define('DB_PASSWORD', $db_parts['pass']);
    define('DB_HOST',     $db_parts['host'] . (isset($db_parts['port']) ? ':' . $db_parts['port'] : ''));
} else {
    // Fallback if DATABASE_URL is somehow not set (should not happen in Dokku with a linked database)
    // You might want to log an error or die here if DATABASE_URL is critical and missing.
    define('DB_NAME', 'wordpress_default_db');
    define('DB_USER', 'user');
    define('DB_PASSWORD', 'password');
    define('DB_HOST', 'localhost');
}

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', getenv('WORDPRESS_DB_CHARSET') ?: 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', getenv('WORDPRESS_DB_COLLATE') ?: '');

/**#@+
 * Authentication Unique Keys and Salts.
 * These are read from Dokku environment variables.
 * Fallbacks are provided but should not be used if Dokku config is set.
 */
define('AUTH_KEY',         getenv('AUTH_KEY')        ?: 'put your unique phrase here');
define('SECURE_AUTH_KEY',  getenv('SECURE_AUTH_KEY') ?: 'put your unique phrase here');
define('LOGGED_IN_KEY',    getenv('LOGGED_IN_KEY')   ?: 'put your unique phrase here');
define('NONCE_KEY',        getenv('NONCE_KEY')       ?: 'put your unique phrase here');
define('AUTH_SALT',        getenv('AUTH_SALT')       ?: 'put your unique phrase here');
define('SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT')?: 'put your unique phrase here');
define('LOGGED_IN_SALT',   getenv('LOGGED_IN_SALT')  ?: 'put your unique phrase here');
define('NONCE_SALT',       getenv('NONCE_SALT')      ?: 'put your unique phrase here');
/**#@-*/

/**
 * WordPress Database Table prefix.
 * Read from environment variable if available.
 */
$table_prefix = getenv('WORDPRESS_TABLE_PREFIX') ?: 'wp_';

/**
 * For developers: WordPress debugging mode.
 * Set WP_DEBUG to 'true' in Dokku env vars to enable.
 */
define('WP_DEBUG', getenv('WP_DEBUG') === 'true' ? true : false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
