-- ============================================================================
--  008 — Partners: table, type, country, featured, partner-since
-- ============================================================================
--
--  WHY
--  ---
--  The Partners module groups partners by type on the public /partners
--  directory, spotlights featured partners first, and shows each partner's
--  country and the year the partnership began.
--
--    category      ACADEMIC | FAITH | CIVIL | REGIONAL | UN | GOVERNMENT |
--                  FUNDING | MEDIA | NETWORK (default)
--    country       a country name from the site's list, "International", or NULL
--    featured      1 = listed first and given a spotlight card
--    partnerSince  four-digit year, or NULL
--
--  SAFE TO RUN, IN ANY STATE, AS OFTEN AS YOU LIKE
--  ------------------------------------------------
--  Creates the Partner table if it is missing, adds only the columns that are
--  missing, and removes nothing. Works on MySQL and MariaDB.
--
--  HOW TO RUN (cPanel)
--  -------------------
--  phpMyAdmin -> click the SITE'S database in the left panel (the DB_NAME in
--  config.php) -> SQL tab -> paste -> Go.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `Partner` (
  `id`           VARCHAR(191) NOT NULL,
  `name`         VARCHAR(200) NOT NULL,
  `subtitle`     VARCHAR(200) NULL,
  `description`  TEXT         NULL,
  `logoUrl`      VARCHAR(500) NULL,
  `websiteUrl`   VARCHAR(500) NULL,
  `sortOrder`    INT          NOT NULL DEFAULT 0,
  `isActive`     TINYINT(1)   NOT NULL DEFAULT 1,
  `createdAt`    DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updatedAt`    DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Each block adds one column only when it is not already there.

SET @need := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Partner' AND COLUMN_NAME = 'category');
SET @sql := IF(@need, 'ALTER TABLE `Partner` ADD COLUMN `category` VARCHAR(20) NOT NULL DEFAULT ''NETWORK''', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @need := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Partner' AND COLUMN_NAME = 'country');
SET @sql := IF(@need, 'ALTER TABLE `Partner` ADD COLUMN `country` VARCHAR(100) NULL', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @need := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Partner' AND COLUMN_NAME = 'featured');
SET @sql := IF(@need, 'ALTER TABLE `Partner` ADD COLUMN `featured` TINYINT(1) NOT NULL DEFAULT 0', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @need := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Partner' AND COLUMN_NAME = 'partnerSince');
SET @sql := IF(@need, 'ALTER TABLE `Partner` ADD COLUMN `partnerSince` SMALLINT NULL', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Tell the application the update is done, so it does not try again.
CREATE TABLE IF NOT EXISTS `SiteSetting` (
  `key`       VARCHAR(100) NOT NULL,
  `value`     TEXT         NULL,
  `updatedAt` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  `updatedBy` VARCHAR(191) NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `SiteSetting` (`key`, `value`, `updatedAt`)
VALUES ('schema_partner_v2', 'ok', NOW(3))
ON DUPLICATE KEY UPDATE `value` = 'ok', `updatedAt` = NOW(3);

-- Verify: should list category, country, featured and partnerSince.
SHOW COLUMNS FROM `Partner`;
