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
$id      = $_GET['id'] ?? '';

if (!$id) json_error('Post ID required', 400);

$pdo  = db();
$stmt = $pdo->prepare('SELECT * FROM Post WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) json_error('Post not found', 404);

// Ownership check
$isOwner = ($post['authorId'] === $uid);
if (!$isSuper && !$isOwner) json_error('Forbidden', 403);

if ($method === 'GET') {
    json_response($post);
}

if ($method === 'PUT') {
    // Owners may edit their own PUBLISHED content; it returns to the approval
    // queue below. ARCHIVED stays locked to super admins.
    if (!$isSuper && $post['status'] === 'ARCHIVED')
        json_error('Cannot edit an archived post', 403);

    $body          = request_body();
    $title         = trim($body['title']         ?? $post['title']);
    $type          = trim($body['type']          ?? $post['type']);
    $description   = trim($body['description']   ?? $post['description'] ?? '');
    $content       = trim($body['content']       ?? $post['content'] ?? '');
    $thumbnailUrl  = trim($body['thumbnailUrl']  ?? $post['thumbnailUrl'] ?? '');
    $mediaUrl      = trim($body['mediaUrl']      ?? $post['mediaUrl'] ?? '');
    $country       = trim($body['country']       ?? $post['country']);
    $region        = trim($body['region']        ?? $post['region']);
    $issueCategory = trim($body['issueCategory'] ?? $post['issueCategory']);

    if ($type === 'POLICY_BRIEF') ensure_policy_brief_post_type();

    $pdo->prepare(
        'UPDATE Post SET title=?,type=?,description=?,content=?,thumbnailUrl=?,mediaUrl=?,country=?,region=?,issueCategory=?,updatedAt=NOW(3) WHERE id=?'
    )->execute([$title,$type,$description,$content,$thumbnailUrl,$mediaUrl,$country,$region,$issueCategory,$id]);

    // Non-super admin edited live content — pull it back for re-approval
    if (!$isSuper && $post['status'] === 'PUBLISHED') {
        $pdo->prepare("UPDATE Post SET status='PENDING',rejectionNotes=NULL,updatedAt=NOW(3) WHERE id=?")
            ->execute([$id]);
    }

    $stmt->execute([$id]);
    json_response($stmt->fetch());
}

if ($method === 'DELETE') {
    if (!$isSuper && !in_array($post['status'], ['DRAFT','REJECTED']))
        json_error('Cannot delete a post that is not draft or rejected', 403);

    $pdo->prepare('DELETE FROM Post WHERE id=?')->execute([$id]);
    json_response(['success' => true]);
}

json_error('Method not allowed', 405);
