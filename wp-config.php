<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'mywordpress' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'U+o]wb )vr&vpWMQ}SeHual_ @mfrJ.2T 1>RuI5A5*DKSr80b$H&39vN^>@/)l9' );
define( 'SECURE_AUTH_KEY',  '~njH`gc+H]yY%Rs.9bvo=-5HsDGM<flL,>8kTF`pvlR;gxWY7]@N^TfK5hN@/1o=' );
define( 'LOGGED_IN_KEY',    'l+VE{Zngf.N1EGnr4/6ig3L=mfd@QYE%ReDd-GD1@W;tP 9@~&(! 2;WNd[`RI~;' );
define( 'NONCE_KEY',        'U*VPCYEE18T8)ybG?O:@khac(;BHLzE}&l/,7Y89dx 1Hh@5+Kekb:jlrK>`L,dY' );
define( 'AUTH_SALT',        '0gUZ8XJF&OxK[G-;zSX9$*a;iV8z| :-Iz~Vw3]cx~As|FWG039G}nMFWLat0t(b' );
define( 'SECURE_AUTH_SALT', 'i8aji9W{p)]sFD_$.>iuk`]B14pw1Oq-YMIDEiV|+uHQtMW]qsxu*f`.ReEGti8P' );
define( 'LOGGED_IN_SALT',   'fOax3wcY`<:9f8q-Ni5YC>HDM{33.7Atly$bryrYY)Jke_/uq&g4}k7}:1Rj @4+' );
define( 'NONCE_SALT',       'r~|Ma_s3jstT;2!M{9o1D8!8PQkGo-ragpar7ncGp0{xkNPt00rh[MqA6S*(U@Ug' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
