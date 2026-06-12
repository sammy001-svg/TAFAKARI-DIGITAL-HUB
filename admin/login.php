<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (is_logged_in()) { header('Location: /admin/dashboard'); exit; }

// Constant tells login.php to skip its own POST handling block
define('_LOGIN_HANDLED', true);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (auth_login(trim($_POST['username'] ?? ''), trim($_POST['password'] ?? ''))) {
        header('Location: /admin/dashboard'); exit;
    }
    $error = !empty($GLOBALS['auth_db_error'])
        ? 'Database connection failed — please contact the site administrator.'
        : 'Invalid username or password.';
}

// Delegate rendering to the shared login template
require_once dirname(__DIR__) . '/login.php';
