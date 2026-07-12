-- =============================================================
-- Tafakari Digital Hub — MySQL Database Initialization
-- Import this file in phpMyAdmin (XAMPP) to set up the database.
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
-- =============================================================

-- =============================================================
-- TABLE: User
-- =============================================================
CREATE TABLE IF NOT EXISTS `User` (
  `id`        VARCHAR(191)                  NOT NULL,
  `name`      VARCHAR(191)                  NULL,
  `email`     VARCHAR(191)                  NULL,
  `username`  VARCHAR(191)                  NULL,
  `password`  VARCHAR(191)                  NOT NULL,
  `role`      ENUM('SUPER_ADMIN','ADMIN')    NOT NULL DEFAULT 'ADMIN',
  `createdAt` DATETIME(3)                   NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updatedAt` DATETIME(3)                   NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),

  PRIMARY KEY (`id`),
  UNIQUE KEY `User_email_key`    (`email`),
  UNIQUE KEY `User_username_key` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: Post
-- =============================================================
CREATE TABLE IF NOT EXISTS `Post` (
  `id`             VARCHAR(191)                                                 NOT NULL,
  `title`          VARCHAR(191)                                                 NOT NULL,
  `content`        TEXT                                                         NULL,
  `description`    TEXT                                                         NULL,
  `thumbnailUrl`   VARCHAR(191)                                                 NULL,
  `mediaUrl`       VARCHAR(191)                                                 NULL,
  `type`           ENUM('ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT') NOT NULL DEFAULT 'ARTICLE',
  `status`         ENUM('DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED')    NOT NULL DEFAULT 'DRAFT',
  `authorId`       VARCHAR(191)                                                 NOT NULL,
  `approverId`     VARCHAR(191)                                                 NULL,
  `rejectionNotes` TEXT                                                         NULL,
  `country`        VARCHAR(191)                                                 NOT NULL,
  `region`         VARCHAR(191)                                                 NOT NULL,
  `issueCategory`  VARCHAR(191)                                                 NOT NULL,
  `viewCount`      INT                                                          NOT NULL DEFAULT 0,
  `downloadCount`  INT                                                          NOT NULL DEFAULT 0,
  `createdAt`      DATETIME(3)                                                  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updatedAt`      DATETIME(3)                                                  NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),

  PRIMARY KEY (`id`),
  INDEX `Post_authorId_idx`    (`authorId`),
  INDEX `Post_approverId_idx`  (`approverId`),
  INDEX `Post_status_idx`      (`status`),
  INDEX `Post_country_idx`     (`country`),

  CONSTRAINT `Post_authorId_fkey`
    FOREIGN KEY (`authorId`)   REFERENCES `User`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `Post_approverId_fkey`
    FOREIGN KEY (`approverId`) REFERENCES `User`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: Comment
-- =============================================================
CREATE TABLE IF NOT EXISTS `Comment` (
  `id`          VARCHAR(191) NOT NULL,
  `content`     TEXT         NOT NULL,
  `rating`      TINYINT      NULL,
  `name`        VARCHAR(191) NULL,
  `email`       VARCHAR(191) NULL,
  `isModerated` TINYINT(1)   NOT NULL DEFAULT 0,
  `isFlagged`   TINYINT(1)   NOT NULL DEFAULT 0,
  `postId`      VARCHAR(191) NOT NULL,
  `createdAt`   DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

  PRIMARY KEY (`id`),
  INDEX `Comment_postId_idx`    (`postId`),
  INDEX `Comment_isFlagged_idx` (`isFlagged`),

  CONSTRAINT `Comment_postId_fkey`
    FOREIGN KEY (`postId`) REFERENCES `Post`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: ContactMessage
-- =============================================================
CREATE TABLE IF NOT EXISTS `ContactMessage` (
  `id`        VARCHAR(191) NOT NULL,
  `fullName`  VARCHAR(191) NOT NULL,
  `email`     VARCHAR(191) NOT NULL,
  `country`   VARCHAR(191) NOT NULL,
  `interest`  VARCHAR(191) NOT NULL,
  `message`   TEXT         NOT NULL,
  `isRead`    TINYINT(1)   NOT NULL DEFAULT 0,
  `createdAt` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

  PRIMARY KEY (`id`),
  INDEX `ContactMessage_isRead_idx` (`isRead`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- ADMIN ACCOUNTS
-- Passwords hashed with bcrypt (cost 12) — DO NOT MODIFY the
-- hash values below; they must match exactly.
-- =============================================================
-- SUPER ADMIN
--   Username : superadmin
--   Password : Tafakari@2025!
--   Email    : superadmin@tafakari.co.ke
-- ─────────────────────────────────────────────────────────────
-- CONTENT EDITOR (ADMIN)
--   Username : editor
--   Password : Editor@2025!
--   Email    : editor@tafakari.co.ke
-- =============================================================

INSERT IGNORE INTO `User`
  (`id`, `name`, `email`, `username`, `password`, `role`, `createdAt`, `updatedAt`)
VALUES
(
  'cmpdlttlo0000fqwzwxudxyif',
  'Super Administrator',
  'superadmin@tafakari.co.ke',
  'superadmin',
  '$2b$12$KtX3/TMyVdt655L8.rz6.OZnNSOXcu1GLR/GmyN/jPF5XblWmv0P.',
  'SUPER_ADMIN',
  NOW(3),
  NOW(3)
),
(
  'cmpdlttly0001fqwztnzj6fdx',
  'Content Editor',
  'editor@tafakari.co.ke',
  'editor',
  '$2b$12$rfgWrF6rmIzxkYQUEGvlV.1wqAu.JVKvxfTkAVhNtbSY9MDiCTplq',
  'ADMIN',
  NOW(3),
  NOW(3)
);

-- =============================================================
-- TABLE: HeatmapCountry
-- =============================================================
CREATE TABLE IF NOT EXISTS `HeatmapCountry` (
  `id`        VARCHAR(191) NOT NULL,
  `name`      VARCHAR(191) NOT NULL,
  `code`      VARCHAR(10)  NOT NULL,
  `centerLat` DOUBLE       NOT NULL,
  `centerLng` DOUBLE       NOT NULL,
  `zoom`      INT          NOT NULL DEFAULT 6,
  `isActive`  TINYINT(1)   NOT NULL DEFAULT 1,
  `createdAt` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updatedAt` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),

  PRIMARY KEY (`id`),
  UNIQUE KEY `HeatmapCountry_code_key` (`code`),
  INDEX `HeatmapCountry_isActive_idx` (`isActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: HeatmapCategory
-- =============================================================
CREATE TABLE IF NOT EXISTS `HeatmapCategory` (
  `id`        VARCHAR(191) NOT NULL,
  `name`      VARCHAR(191) NOT NULL,
  `isActive`  TINYINT(1)   NOT NULL DEFAULT 1,
  `createdAt` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updatedAt` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),

  PRIMARY KEY (`id`),
  UNIQUE KEY `HeatmapCategory_name_key` (`name`),
  INDEX `HeatmapCategory_isActive_idx` (`isActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- SEED: Default Heatmap Countries (18 African nations)
-- =============================================================
INSERT IGNORE INTO `HeatmapCountry`
  (`id`, `name`, `code`, `centerLat`, `centerLng`, `zoom`, `createdAt`, `updatedAt`)
VALUES
  ('hc_ke', 'Kenya',        'KE',  0.0236,   37.9062,  6, NOW(3), NOW(3)),
  ('hc_et', 'Ethiopia',     'ET',  9.1450,   40.4897,  6, NOW(3), NOW(3)),
  ('hc_cd', 'DR Congo',     'CD', -4.0383,   21.7587,  5, NOW(3), NOW(3)),
  ('hc_ug', 'Uganda',       'UG',  1.3733,   32.2903,  7, NOW(3), NOW(3)),
  ('hc_tz', 'Tanzania',     'TZ', -6.3690,   34.8888,  6, NOW(3), NOW(3)),
  ('hc_rw', 'Rwanda',       'RW', -1.9403,   29.8739,  8, NOW(3), NOW(3)),
  ('hc_bi', 'Burundi',      'BI', -3.3731,   29.9189,  8, NOW(3), NOW(3)),
  ('hc_ss', 'South Sudan',  'SS',  6.8770,   31.3070,  6, NOW(3), NOW(3)),
  ('hc_so', 'Somalia',      'SO',  5.1521,   46.1996,  6, NOW(3), NOW(3)),
  ('hc_ng', 'Nigeria',      'NG',  9.0820,    8.6753,  6, NOW(3), NOW(3)),
  ('hc_gh', 'Ghana',        'GH',  7.9465,   -1.0232,  7, NOW(3), NOW(3)),
  ('hc_za', 'South Africa', 'ZA',-30.5595,   22.9375,  5, NOW(3), NOW(3)),
  ('hc_eg', 'Egypt',        'EG', 26.8206,   30.8025,  6, NOW(3), NOW(3)),
  ('hc_cm', 'Cameroon',     'CM',  3.8480,   11.5021,  6, NOW(3), NOW(3)),
  ('hc_sd', 'Sudan',        'SD', 12.8628,   30.2176,  5, NOW(3), NOW(3)),
  ('hc_zm', 'Zambia',       'ZM',-13.1339,   27.8493,  6, NOW(3), NOW(3)),
  ('hc_mz', 'Mozambique',   'MZ',-18.6657,   35.5296,  6, NOW(3), NOW(3)),
  ('hc_zw', 'Zimbabwe',     'ZW',-19.0154,   29.1549,  6, NOW(3), NOW(3));

-- =============================================================
-- SEED: Default Heatmap Categories (12 issue areas)
-- =============================================================
INSERT IGNORE INTO `HeatmapCategory`
  (`id`, `name`, `createdAt`, `updatedAt`)
VALUES
  ('cat_health',      'Health',                      NOW(3), NOW(3)),
  ('cat_education',   'Education',                   NOW(3), NOW(3)),
  ('cat_security',    'Security & Conflict',         NOW(3), NOW(3)),
  ('cat_climate',     'Climate & Environment',       NOW(3), NOW(3)),
  ('cat_economic',    'Economic Development',        NOW(3), NOW(3)),
  ('cat_policy',      'Policy & Governance',         NOW(3), NOW(3)),
  ('cat_humanrights', 'Human Rights',                NOW(3), NOW(3)),
  ('cat_migration',   'Migration & Refugees',        NOW(3), NOW(3)),
  ('cat_agriculture', 'Agriculture & Food Security', NOW(3), NOW(3)),
  ('cat_infra',       'Infrastructure & Energy',     NOW(3), NOW(3)),
  ('cat_tech',        'Technology & Innovation',     NOW(3), NOW(3)),
  ('cat_gender',      'Gender & Social Affairs',     NOW(3), NOW(3));

-- =============================================================
-- Verify the import was successful:
-- SELECT id, username, role, email FROM User;
-- SELECT code, name FROM HeatmapCountry ORDER BY name;
-- SELECT name FROM HeatmapCategory ORDER BY name;
-- =============================================================
