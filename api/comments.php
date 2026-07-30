<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$method = request_method();

if ($method === 'POST') {
    // Public: no auth required
    ensure_comment_rating_column();

    $body    = request_body();
    $content = trim(substr($body['content'] ?? '', 0, 2000));
    $name    = trim(substr($body['name']    ?? '', 0, 100));
    $email   = trim(substr($body['email']   ?? '', 0, 200));
    $postId  = trim($body['postId'] ?? '');
    $rating  = isset($body['rating']) ? (int)$body['rating'] : 0;
    $rating  = ($rating >= 1 && $rating <= 5) ? $rating : null;

    if (!$content) json_error('Content is required');
    if (!$postId)  json_error('Post ID is required');

    $pdo  = db();
    $chk  = $pdo->prepare("SELECT id FROM Post WHERE id=? AND status='PUBLISHED' LIMIT 1");
    $chk->execute([$postId]);
    if (!$chk->fetch()) json_error('Post not found or not published', 404);

    $id = generate_id();
    $pdo->prepare(
        'INSERT INTO Comment (id,content,rating,name,email,postId,isModerated,isFlagged,createdAt) VALUES (?,?,?,?,?,?,0,0,NOW(3))'
    )->execute([$id, $content, $rating, $name ?: null, $email ?: null, $postId]);

    $stmt = $pdo->prepare('SELECT * FROM Comment WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    json_response($stmt->fetch(), 201);
}

if ($method === 'GET') {
    $user = api_require_super_admin();
    $pdo = db();

    $isFlagged   = isset($_GET['isFlagged'])   ? (int)$_GET['isFlagged']   : null;
    $isModerated = isset($_GET['isModerated']) ? (int)$_GET['isModerated'] : null;
    $postId      = $_GET['postId'] ?? null;
    $page        = max(1, (int)($_GET['page']  ?? 1));
    $limit       = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    $skip        = ($page - 1) * $limit;

    $where  = [];
    $params = [];
    // isFlagged/isModerated are already int-cast; postId must be bound.
    if ($isFlagged !== null)   { $where[] = 'isFlagged = ?';   $params[] = $isFlagged; }
    if ($isModerated !== null) { $where[] = 'isModerated = ?'; $params[] = $isModerated; }
    if ($postId)               { $where[] = 'postId = ?';      $params[] = $postId; }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stCount = $pdo->prepare("SELECT COUNT(*) FROM Comment $w");
    $stCount->execute($params);
    $total = (int)$stCount->fetchColumn();

    $stCmt = $pdo->prepare("SELECT * FROM Comment $w ORDER BY createdAt DESC LIMIT $limit OFFSET $skip");
    $stCmt->execute($params);
    $comments = $stCmt->fetchAll();

    json_response(['data' => ['comments' => $comments, 'total' => $total, 'page' => $page, 'limit' => $limit]]);
}

json_error('Method not allowed', 405);
