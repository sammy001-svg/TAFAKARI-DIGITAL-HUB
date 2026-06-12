<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user    = api_require_auth();
$isSuper = ($user['role'] === 'SUPER_ADMIN');
$uid     = $user['id'];
$pdo     = db();

$w = $isSuper ? '1' : "authorId = '$uid'";

$totalPosts     = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $w")->fetchColumn();
$publishedPosts = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $w AND status='PUBLISHED'")->fetchColumn();
$pendingPosts   = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $w AND status='PENDING'")->fetchColumn();
$draftPosts     = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $w AND status='DRAFT'")->fetchColumn();
$rejectedPosts  = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $w AND status='REJECTED'")->fetchColumn();
$archivedPosts  = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $w AND status='ARCHIVED'")->fetchColumn();

$views = $pdo->query("SELECT COALESCE(SUM(viewCount),0) AS v, COALESCE(SUM(downloadCount),0) AS d FROM Post WHERE $w")->fetch();
$totalComments  = (int)$pdo->query("SELECT COUNT(*) FROM Comment")->fetchColumn();
$flaggedComments = (int)$pdo->query("SELECT COUNT(*) FROM Comment WHERE isFlagged=1")->fetchColumn();
$totalUsers     = $isSuper ? (int)$pdo->query("SELECT COUNT(*) FROM User")->fetchColumn() : 1;

$recentPosts = $pdo->query(
    "SELECT p.id,p.title,p.type,p.status,p.country,p.issueCategory,p.updatedAt,
            u.name AS authorName, u.username AS authorUsername
     FROM Post p LEFT JOIN User u ON p.authorId=u.id
     WHERE $w ORDER BY p.updatedAt DESC LIMIT 5"
)->fetchAll();

json_response([
    'totalPosts' => $totalPosts, 'publishedPosts' => $publishedPosts,
    'pendingPosts' => $pendingPosts, 'draftPosts' => $draftPosts,
    'rejectedPosts' => $rejectedPosts, 'archivedPosts' => $archivedPosts,
    'totalViews' => (int)$views['v'], 'totalDownloads' => (int)$views['d'],
    'totalComments' => $totalComments, 'flaggedComments' => $flaggedComments,
    'totalUsers' => $totalUsers, 'recentPosts' => $recentPosts,
]);
