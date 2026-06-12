<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = api_require_super_admin();
$id   = $_GET['id'] ?? '';

if (request_method() !== 'POST') json_error('Method not allowed', 405);
if (!$id) json_error('Post ID required');

$body   = request_body();
$action = strtoupper(trim($body['action'] ?? ''));
$notes  = trim($body['rejectionNotes'] ?? '');

if (!in_array($action, ['APPROVE','REJECT'])) json_error('Invalid action');

$pdo  = db();
$stmt = $pdo->prepare('SELECT id, status FROM Post WHERE id=? LIMIT 1');
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) json_error('Post not found', 404);
if ($post['status'] !== 'PENDING') json_error('Post is not pending');

if ($action === 'APPROVE') {
    $pdo->prepare("UPDATE Post SET status='PUBLISHED',approverId=?,rejectionNotes=NULL,updatedAt=NOW(3) WHERE id=?")
        ->execute([$user['id'], $id]);
} else {
    if (!$notes) json_error('Rejection notes are required');
    $pdo->prepare("UPDATE Post SET status='REJECTED',approverId=?,rejectionNotes=?,updatedAt=NOW(3) WHERE id=?")
        ->execute([$user['id'], $notes, $id]);
}

json_response(['success' => true, 'status' => $action === 'APPROVE' ? 'PUBLISHED' : 'REJECTED']);
