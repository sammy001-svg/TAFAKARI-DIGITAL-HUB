<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (request_method() !== 'POST') json_error('Method not allowed', 405);

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    // Only allow if no users exist
    $count = (int)$pdo->query('SELECT COUNT(*) FROM User')->fetchColumn();
    if ($count > 0) json_error('Setup already complete', 403);
} catch (PDOException $e) {
    // Tables may not exist yet — proceed
}

$sqls = [
    'User' => "CREATE TABLE IF NOT EXISTS `User` (
      `id` VARCHAR(191) NOT NULL, `name` VARCHAR(191) NULL, `email` VARCHAR(191) NULL,
      `username` VARCHAR(191) NULL, `password` VARCHAR(191) NOT NULL,
      `role` ENUM('SUPER_ADMIN','ADMIN') NOT NULL DEFAULT 'ADMIN',
      `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
      PRIMARY KEY (`id`), UNIQUE KEY `User_email_key`(`email`), UNIQUE KEY `User_username_key`(`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'Post' => "CREATE TABLE IF NOT EXISTS `Post` (
      `id` VARCHAR(191) NOT NULL, `title` VARCHAR(255) NOT NULL, `content` TEXT NULL,
      `description` TEXT NULL, `thumbnailUrl` VARCHAR(1024) NULL, `mediaUrl` VARCHAR(1024) NULL,
      `type` ENUM('ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT') NOT NULL DEFAULT 'ARTICLE',
      `status` ENUM('DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
      `authorId` VARCHAR(191) NOT NULL, `approverId` VARCHAR(191) NULL, `rejectionNotes` TEXT NULL,
      -- issueCategory holds a comma-joined list from the multi-select, so it
      -- needs far more room than a single value would
      `country` VARCHAR(191) NOT NULL, `region` VARCHAR(191) NOT NULL, `issueCategory` VARCHAR(512) NOT NULL,
      -- Per-element 1-10 intensity scores, JSON keyed by element name
      `intensityScores` TEXT NULL,
      `viewCount` INT NOT NULL DEFAULT 0, `downloadCount` INT NOT NULL DEFAULT 0,
      `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
      PRIMARY KEY (`id`),
      CONSTRAINT `Post_authorId_fkey` FOREIGN KEY (`authorId`) REFERENCES `User`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'Comment' => "CREATE TABLE IF NOT EXISTS `Comment` (
      `id` VARCHAR(191) NOT NULL, `content` TEXT NOT NULL, `name` VARCHAR(191) NULL,
      `email` VARCHAR(191) NULL, `isModerated` TINYINT(1) NOT NULL DEFAULT 0,
      `isFlagged` TINYINT(1) NOT NULL DEFAULT 0, `postId` VARCHAR(191) NOT NULL,
      `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      PRIMARY KEY (`id`),
      CONSTRAINT `Comment_postId_fkey` FOREIGN KEY (`postId`) REFERENCES `Post`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'ContactMessage' => "CREATE TABLE IF NOT EXISTS `ContactMessage` (
      `id` VARCHAR(191) NOT NULL, `fullName` VARCHAR(191) NOT NULL, `email` VARCHAR(191) NOT NULL,
      `country` VARCHAR(191) NOT NULL, `interest` VARCHAR(191) NOT NULL, `message` TEXT NOT NULL,
      `isRead` TINYINT(1) NOT NULL DEFAULT 0, `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'SiteSetting' => "CREATE TABLE IF NOT EXISTS `SiteSetting` (
      `key` VARCHAR(100) NOT NULL,
      `value` TEXT NULL,
      `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
      `updatedBy` VARCHAR(191) NULL,
      PRIMARY KEY (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'Partner' => "CREATE TABLE IF NOT EXISTS `Partner` (
      `id` VARCHAR(191) NOT NULL,
      `name` VARCHAR(200) NOT NULL,
      `subtitle` VARCHAR(200) NULL,
      `description` TEXT NULL,
      `logoUrl` VARCHAR(500) NULL,
      `websiteUrl` VARCHAR(500) NULL,
      `sortOrder` INT NOT NULL DEFAULT 0,
      `isActive` TINYINT(1) NOT NULL DEFAULT 1,
      `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'TeamMember' => "CREATE TABLE IF NOT EXISTS `TeamMember` (
      `id` VARCHAR(191) NOT NULL,
      `name` VARCHAR(200) NOT NULL,
      `role` VARCHAR(200) NULL,
      `bio` TEXT NULL,
      `photoUrl` VARCHAR(500) NULL,
      `sortOrder` INT NOT NULL DEFAULT 0,
      `isActive` TINYINT(1) NOT NULL DEFAULT 1,
      `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

$created = [];
$errors  = [];

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    foreach ($sqls as $table => $sql) {
        try { $pdo->exec($sql); $created[] = $table; }
        catch (PDOException $e) { $errors[] = $table . ': ' . $e->getMessage(); }
    }
} catch (PDOException $e) {
    json_error('DB connection failed: ' . $e->getMessage(), 503);
}

json_response(['success' => empty($errors), 'created' => $created, 'errors' => $errors]);
