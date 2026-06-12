<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/db.php';

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return isset($_SESSION['user']['id']);
}

function is_super_admin(): bool {
    return ($_SESSION['user']['role'] ?? '') === 'SUPER_ADMIN';
}

function require_auth(): array {
    if (!is_logged_in()) {
        header('Location: /admin/login');
        exit;
    }
    return $_SESSION['user'];
}

function require_super_admin(): array {
    $user = require_auth();
    if (!is_super_admin()) {
        header('Location: /admin/dashboard');
        exit;
    }
    return $user;
}

function auth_login(string $username, string $password): bool {
    $GLOBALS['auth_db_error'] = false;
    try {
        $stmt = db()->prepare('SELECT id, name, email, username, password, role FROM User WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password'])) return false;
        unset($user['password']);
        $_SESSION['user'] = $user;
        session_regenerate_id(true);
        return true;
    } catch (\PDOException $e) {
        $GLOBALS['auth_db_error'] = true;
        error_log('[Tafakari] auth_login DB error: ' . $e->getMessage());
        return false;
    }
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// For API routes: return 401 JSON if not authenticated
function api_require_auth(): array {
    require_once __DIR__ . '/functions.php';
    if (!is_logged_in()) json_error('Unauthorized', 401);
    return $_SESSION['user'];
}

function api_require_super_admin(): array {
    $user = api_require_auth();
    if ($user['role'] !== 'SUPER_ADMIN') json_error('Forbidden', 403);
    return $user;
}
