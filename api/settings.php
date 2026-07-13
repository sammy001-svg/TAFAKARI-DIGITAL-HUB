<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

api_require_super_admin();

$allowed = [
    'announcement_active', 'announcement_text', 'announcement_url', 'announcement_color', 'announcement_image_url',
    'carousel_slides', 'featured_article_id',
    'show_podcasts', 'show_videos', 'show_documents',
];

if (request_method() === 'GET') {
    $rows = [];
    try {
        $rows = db()->query('SELECT `key`, `value` FROM SiteSetting')->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {}
    json_response(['settings' => $rows]);
}

if (request_method() === 'POST') {
    $body = request_body();
    $user = current_user();
    $uid  = $user['id'] ?? '';

    foreach ($body as $key => $value) {
        if (!in_array($key, $allowed, true)) continue;
        set_setting($key, (string)$value, $uid);
    }
    json_response(['success' => true]);
}

json_error('Method not allowed', 405);
