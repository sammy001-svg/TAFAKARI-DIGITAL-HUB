-- ============================================================================
--  005 — Multi-country attribution and intensity-score provenance on Post
-- ============================================================================
--
--  WHY
--  ---
--  1. Cross-border items. Reporting on a cross-border armed group, cross-border
--     displacement or a regional peace initiative concerns more than one
--     country. Forcing a single country understates transnational
--     developments, so such items are now attributed to every country they
--     concern.
--
--       additionalCountries  JSON array of countries beyond the primary one,
--                            e.g. ["South Sudan","Uganda"]. Post.country stays
--                            the primary country, so every existing listing,
--                            search and detail page keeps working unchanged.
--
--  2. Corroboration. The aggregation weights a report by the number of
--     independent sources behind it (see /methodology).
--
--       sourceCount          number of independent corroborating sources.
--                            NULL is read as 1.
--
--  3. Provenance. Every intensity score records how it was produced, so the
--     methodology can say who scored what, and AI-proposed scores (Tafakari
--     Agent, later phase) can never be mistaken for analyst judgements.
--
--       scoreSource          ANALYST | AI | AI_REVIEWED   (NULL = not recorded)
--       scoredBy             User.id of the analyst who last set the scores
--       scoredAt             when the scores were last set
--
--  YOU PROBABLY DO NOT NEED TO RUN THIS
--  ------------------------------------
--  includes/functions.php::ensure_post_columns() adds these columns
--  automatically, once, on the first admin content page load. This file is
--  the written record, and the fallback where the database user has no ALTER
--  privilege (check the PHP error log for "[ensure_post_columns]").
--
--  SAFE TO RUN
--  -----------
--  Adds five nullable columns. Nothing is read, changed or removed. Existing
--  posts get NULL: one country, one source, provenance not recorded.
--  If phpMyAdmin reports "#1060 - Duplicate column name 'additionalCountries'",
--  ensure_post_columns() has ALREADY added all five columns automatically —
--  you do not need to run the ALTER TABLE again.
--
--  HOW TO RUN (cPanel)
--  -------------------
--  phpMyAdmin -> select your database -> SQL tab -> paste -> Go.
-- ============================================================================

ALTER TABLE `Post`
  ADD COLUMN `additionalCountries` TEXT         NULL,
  ADD COLUMN `sourceCount`         SMALLINT     NULL,
  ADD COLUMN `scoreSource`         VARCHAR(16)  NULL,
  ADD COLUMN `scoredBy`            VARCHAR(191) NULL,
  ADD COLUMN `scoredAt`            DATETIME(3)  NULL;

CREATE TABLE IF NOT EXISTS `SiteSetting` (
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT NULL,
  `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  `updatedBy` VARCHAR(191) NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `SiteSetting` (`key`, `value`, `updatedAt`)
VALUES ('schema_post_columns_v2', 'ok', NOW(3))
ON DUPLICATE KEY UPDATE `value` = 'ok', `updatedAt` = NOW(3);

-- Verify all five columns exist:
SHOW FULL COLUMNS FROM `Post`
WHERE `Field` IN ('additionalCountries', 'sourceCount', 'scoreSource', 'scoredBy', 'scoredAt');
