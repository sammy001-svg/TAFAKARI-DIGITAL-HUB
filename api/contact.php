<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (request_method() !== 'POST') json_error('Method not allowed', 405);

$body     = request_body();
$fullName = trim($body['fullName'] ?? '');
$email    = trim($body['email']    ?? '');
$country  = trim($body['country']  ?? '');
$interest = trim($body['interest'] ?? '');
$message  = trim($body['message']  ?? '');

if (!$fullName) json_error('Full name is required');
if (!$email)    json_error('Email is required');
if (!$message)  json_error('Message is required');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_error('Invalid email address');

$id = generate_id();
db()->prepare(
    'INSERT INTO ContactMessage (id,fullName,email,country,interest,message,isRead,createdAt) VALUES (?,?,?,?,?,?,0,NOW(3))'
)->execute([$id, $fullName, $email, $country, $interest, $message]);

$stmt = db()->prepare('SELECT * FROM ContactMessage WHERE id=? LIMIT 1');
$stmt->execute([$id]);
json_response($stmt->fetch(), 201);
