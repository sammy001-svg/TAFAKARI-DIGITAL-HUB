<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user    = api_require_auth();
$isSuper = ($user['role'] === 'SUPER_ADMIN');
$uid     = $user['id'];
$id      = $_GET['id'] ?? '';

if (request_method() !== 'POST') json_error('Method not allowed', 405);
if (!$id) json_error('Post ID required');

$pdo  = db();
$stmt = $pdo->prepare('SELECT id, authorId, status FROM Post WHERE id=? LIMIT 1');
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) json_error('Post not found', 404);
if (!$isSuper && $post['authorId'] !== $uid) json_error('Forbidden', 403);
if (!in_array($post['status'], ['DRAFT','REJECTED'])) json_error('Only DRAFT or REJECTED posts can be submitted');

$pdo->prepare("UPDATE Post SET status='PENDING',rejectionNotes=NULL,updatedAt=NOW(3) WHERE id=?")->execute([$id]);
json_response(['success' => true, 'status' => 'PENDING']);
