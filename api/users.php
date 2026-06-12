<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$me     = api_require_super_admin();
$method = request_method();
$pdo    = db();

if ($method === 'GET') {
    $page  = max(1, (int)($_GET['page']  ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $skip  = ($page - 1) * $limit;
    $users = $pdo->query(
        "SELECT u.id,u.name,u.email,u.username,u.role,u.createdAt,COUNT(p.id) AS postCount
         FROM User u LEFT JOIN Post p ON p.authorId=u.id
         GROUP BY u.id ORDER BY u.createdAt DESC LIMIT $limit OFFSET $skip"
    )->fetchAll();
    json_response(['data' => $users]);
}

if ($method === 'POST') {
    $body     = request_body();
    $name     = trim($body['name']     ?? '');
    $email    = trim($body['email']    ?? '');
    $username = trim($body['username'] ?? '');
    $password = trim($body['password'] ?? '');
    $role     = in_array($body['role'] ?? '', ['SUPER_ADMIN','ADMIN']) ? $body['role'] : 'ADMIN';

    if (!$name || !$email || !$username || !$password) json_error('All fields are required');
    if (strlen($password) < 8) json_error('Password must be at least 8 characters');

    $dup = $pdo->prepare('SELECT id FROM User WHERE email=? OR username=? LIMIT 1');
    $dup->execute([$email, $username]);
    if ($dup->fetch()) json_error('Email or username already in use', 409);

    $id     = generate_id();
    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare(
        'INSERT INTO User (id,name,email,username,password,role,createdAt,updatedAt) VALUES (?,?,?,?,?,?,NOW(3),NOW(3))'
    )->execute([$id,$name,$email,$username,$hashed,$role]);

    $stmt = $pdo->prepare('SELECT id,name,email,username,role,createdAt FROM User WHERE id=?');
    $stmt->execute([$id]);
    json_response($stmt->fetch(), 201);
}

json_error('Method not allowed', 405);
