<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

api_require_auth();
if (request_method() !== 'POST') json_error('Method not allowed', 405);

$body = request_body();
$text = $body['text'] ?? '';
json_response(['html' => markdown_to_html($text)]);
