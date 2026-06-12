<?php
declare(strict_types=1);

// ══════════════════════════════════════════════════════════════
//  DATABASE — fill in your cPanel MySQL credentials here
// ══════════════════════════════════════════════════════════════
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'your_cpanel_db_name');   // e.g. crtptafa_tafakari
define('DB_USER', 'your_cpanel_db_user');   // e.g. crtptafa_admin
define('DB_PASS', 'your_db_password');      // the password you set in cPanel

// ══════════════════════════════════════════════════════════════
//  APPLICATION
// ══════════════════════════════════════════════════════════════
define('APP_URL',    'https://your-domain.com');   // no trailing slash
define('APP_SECRET', 'replace-with-any-long-random-string');
define('APP_ENV',    'production');

// ══════════════════════════════════════════════════════════════
//  DO NOT EDIT BELOW THIS LINE
// ══════════════════════════════════════════════════════════════
define('DB_DSN', 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 7,
        'path'     => '/',
        'secure'   => (APP_ENV === 'production'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
