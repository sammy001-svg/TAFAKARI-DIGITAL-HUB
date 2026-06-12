<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

api_require_super_admin();
$method = request_method();
$id     = $_GET['id'] ?? '';
if (!$id) json_error('Message ID required');

$pdo  = db();
$stmt = $pdo->prepare('SELECT id FROM ContactMessage WHERE id=? LIMIT 1');
$stmt->execute([$id]);
if (!$stmt->fetch()) json_error('Message not found', 404);

if ($method === 'PATCH') {
    $pdo->prepare('UPDATE ContactMessage SET isRead=1 WHERE id=?')->execute([$id]);
    json_response(['success' => true]);
}

if ($method === 'DELETE') {
    $pdo->prepare('DELETE FROM ContactMessage WHERE id=?')->execute([$id]);
    json_response(['success' => true]);
}

json_error('Method not allowed', 405);
