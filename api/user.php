<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$me     = api_require_auth();
$isSuper = ($me['role'] === 'SUPER_ADMIN');
$method  = request_method();
$id      = $_GET['id'] ?? '';

if (!$id) json_error('User ID required');
if (!$isSuper && $me['id'] !== $id) json_error('Forbidden', 403);

$pdo  = db();
$stmt = $pdo->prepare('SELECT id,name,email,username,role,createdAt FROM User WHERE id=? LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) json_error('User not found', 404);

if ($method === 'GET') {
    json_response($user);
}

if ($method === 'PUT') {
    $body     = request_body();
    $name     = trim($body['name']     ?? $user['name']     ?? '');
    $email    = trim($body['email']    ?? $user['email']    ?? '');
    $username = trim($body['username'] ?? $user['username'] ?? '');
    $password = trim($body['password'] ?? '');
    $role     = $user['role'];

    // Only super admin can change role; prevent self-demotion
    if ($isSuper && isset($body['role']) && $id !== $me['id']) {
        if (in_array($body['role'], ['SUPER_ADMIN','ADMIN'])) $role = $body['role'];
    }

    if ($email || $username) {
        $dup = $pdo->prepare('SELECT id FROM User WHERE (email=? OR username=?) AND id!=? LIMIT 1');
        $dup->execute([$email, $username, $id]);
        if ($dup->fetch()) json_error('Email or username already in use', 409);
    }

    if ($password) {
        if (strlen($password) < 8) json_error('Password must be at least 8 characters');
        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('UPDATE User SET name=?,email=?,username=?,password=?,role=?,updatedAt=NOW(3) WHERE id=?')
            ->execute([$name,$email,$username,$hashed,$role,$id]);
    } else {
        $pdo->prepare('UPDATE User SET name=?,email=?,username=?,role=?,updatedAt=NOW(3) WHERE id=?')
            ->execute([$name,$email,$username,$role,$id]);
    }

    $stmt->execute([$id]);
    json_response($stmt->fetch());
}

if ($method === 'DELETE') {
    if (!$isSuper) json_error('Forbidden', 403);
    if ($id === $me['id']) json_error('Cannot delete your own account');
    $count = (int)$pdo->prepare('SELECT COUNT(*) FROM Post WHERE authorId=?')->execute([$id]) && (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE authorId='$id'")->fetchColumn();
    $postCount = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE authorId='$id'")->fetchColumn();
    if ($postCount > 0) json_error('Cannot delete user with existing posts');
    $pdo->prepare('DELETE FROM User WHERE id=?')->execute([$id]);
    json_response(['success' => true]);
}

json_error('Method not allowed', 405);
