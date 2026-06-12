<?php
declare(strict_types=1);

// Parse .env file
$_envFile = __DIR__ . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (str_starts_with(trim($_line), '#')) continue;
        [$_k, $_v] = array_pad(explode('=', $_line, 2), 2, '');
        $_k = trim($_k);
        $_v = trim($_v, " \t\n\r\0\x0B\"'");
        if ($_k !== '') $_ENV[$_k] = $_ENV[$_k] ?? $_v;
    }
}

// Parse DATABASE_URL: mysql://user:pass@host:port/dbname
$_dbUrl = $_ENV['DATABASE_URL'] ?? 'mysql://root:@localhost:3306/tafakari_hub';
preg_match('|mysql://([^:]*):([^@]*)@([^:/]+)(?::(\d+))?/(.+)|', $_dbUrl, $_m);
define('DB_USER', $_m[1] ?? 'root');
define('DB_PASS', $_m[2] ?? '');
define('DB_HOST', $_m[3] ?? 'localhost');
define('DB_PORT', $_m[4] ?? '3306');
define('DB_NAME', $_m[5] ?? 'tafakari_hub');
define('DB_DSN',  'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4');

define('APP_URL',    rtrim($_ENV['NEXTAUTH_URL'] ?? 'http://localhost', '/'));
define('APP_SECRET', $_ENV['NEXTAUTH_SECRET'] ?? 'changeme');
define('APP_ENV',    $_ENV['NODE_ENV'] ?? 'production');

// Start session once
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
