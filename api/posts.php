<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user    = api_require_auth();
$isSuper = ($user['role'] === 'SUPER_ADMIN');
$uid     = $user['id'];
$method  = request_method();

if ($method === 'GET') {
    $status   = $_GET['status']        ?? null;
    $type     = $_GET['type']          ?? null;
    $country  = $_GET['country']       ?? null;
    $page     = max(1, (int)($_GET['page']  ?? 1));
    $limit    = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    $skip     = ($page - 1) * $limit;

    // Bind every user-supplied value; whitelist the enum-backed ones.
    $where  = [];
    $params = [];
    if (!$isSuper) { $where[] = 'p.authorId = ?'; $params[] = $uid; }

    $validStatuses = ['DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED'];
    $validTypes    = ['ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT'];

    if ($status !== null && in_array($status, $validStatuses, true)) {
        $where[] = 'p.status = ?';  $params[] = $status;
    }
    if ($type !== null && in_array($type, $validTypes, true)) {
        $where[] = 'p.type = ?';    $params[] = $type;
    }
    if ($country !== null && $country !== '') {
        $where[] = 'p.country = ?'; $params[] = $country;
    }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // $limit/$skip are already int-cast and clamped above, so they are safe to inline
    // (PDO cannot bind LIMIT/OFFSET as placeholders with emulation disabled).
    $pdo = db();

    $stCount = $pdo->prepare("SELECT COUNT(*) FROM Post p $w");
    $stCount->execute($params);
    $total = (int)$stCount->fetchColumn();

    $stPosts = $pdo->prepare(
        "SELECT p.*, u.name AS authorName, u.username AS authorUsername
         FROM Post p LEFT JOIN User u ON p.authorId = u.id
         $w ORDER BY p.createdAt DESC LIMIT $limit OFFSET $skip"
    );
    $stPosts->execute($params);
    $posts = $stPosts->fetchAll();

    json_response(['data' => ['posts' => $posts, 'total' => $total, 'page' => $page, 'limit' => $limit,
        'totalPages' => (int)ceil($total / $limit)]]);
}

if ($method === 'POST') {
    $body          = request_body();
    $title         = trim($body['title']         ?? '');
    $type          = trim($body['type']          ?? 'ARTICLE');
    $description   = trim($body['description']   ?? '');
    $content       = trim($body['content']       ?? '');
    $thumbnailUrl  = trim($body['thumbnailUrl']  ?? '');
    $mediaUrl      = trim($body['mediaUrl']      ?? '');
    $country       = trim($body['country']       ?? '');
    $region        = trim($body['region']        ?? '');
    $issueCategory = trim($body['issueCategory'] ?? '');

    if (!$title)         json_error('Title is required');
    if (!$country)       json_error('Country is required');
    if (!$region)        json_error('Region is required');
    if (!$issueCategory) json_error('Issue category is required');

    $validTypes = ['ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT'];
    if (!in_array($type, $validTypes)) $type = 'ARTICLE';

    $id = generate_id();
    db()->prepare(
        'INSERT INTO Post (id,title,type,description,content,thumbnailUrl,mediaUrl,country,region,issueCategory,status,authorId,viewCount,downloadCount,createdAt,updatedAt)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,0,NOW(3),NOW(3))'
    )->execute([$id,$title,$type,$description,$content,$thumbnailUrl,$mediaUrl,$country,$region,$issueCategory,'DRAFT',$uid]);

    $post = db()->prepare('SELECT * FROM Post WHERE id=?');
    $post->execute([$id]);
    json_response($post->fetch(), 201);
}

json_error('Method not allowed', 405);
