<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (is_logged_in()) { header('Location: /admin/dashboard'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (auth_login(trim($_POST['username'] ?? ''), trim($_POST['password'] ?? ''))) {
        header('Location: /admin/dashboard'); exit;
    }
    $error = 'Invalid username or password.';
}
// Reuse the public login page template
require_once dirname(__DIR__) . '/login.php';
