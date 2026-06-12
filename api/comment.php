<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

api_require_super_admin();
$method = request_method();
$id     = $_GET['id'] ?? '';
if (!$id) json_error('Comment ID required');

$pdo  = db();
$stmt = $pdo->prepare('SELECT * FROM Comment WHERE id=? LIMIT 1');
$stmt->execute([$id]);
$comment = $stmt->fetch();
if (!$comment) json_error('Comment not found', 404);

if ($method === 'PUT') {
    $body        = request_body();
    $isModerated = isset($body['isModerated']) ? (int)(bool)$body['isModerated'] : (int)$comment['isModerated'];
    $isFlagged   = isset($body['isFlagged'])   ? (int)(bool)$body['isFlagged']   : (int)$comment['isFlagged'];
    $pdo->prepare('UPDATE Comment SET isModerated=?,isFlagged=? WHERE id=?')->execute([$isModerated,$isFlagged,$id]);
    $stmt->execute([$id]);
    json_response($stmt->fetch());
}

if ($method === 'DELETE') {
    $pdo->prepare('DELETE FROM Comment WHERE id=?')->execute([$id]);
    json_response(['success' => true]);
}

json_error('Method not allowed', 405);
