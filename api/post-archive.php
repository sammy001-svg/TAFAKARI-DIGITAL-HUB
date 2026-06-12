<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

api_require_super_admin();
$id = $_GET['id'] ?? '';

if (request_method() !== 'POST') json_error('Method not allowed', 405);
if (!$id) json_error('Post ID required');

$pdo  = db();
$stmt = $pdo->prepare('SELECT id, status FROM Post WHERE id=? LIMIT 1');
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) json_error('Post not found', 404);
if (!in_array($post['status'], ['PUBLISHED','ARCHIVED'])) json_error('Post must be PUBLISHED or ARCHIVED to toggle');

$newStatus = $post['status'] === 'PUBLISHED' ? 'ARCHIVED' : 'PUBLISHED';
$pdo->prepare('UPDATE Post SET status=?,updatedAt=NOW(3) WHERE id=?')->execute([$newStatus, $id]);
json_response(['success' => true, 'status' => $newStatus]);
