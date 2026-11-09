-- ============================================================================
--  010 - AI Articles: drafts by the Tafakari Agent, awaiting human verification
-- ============================================================================
--  Each row is one drafted article for a monitoring region and indicator.
--  A published article becomes an ordinary row in Post; this table keeps the
--  draft, its sources and who verified it.
--
--  ensure_agent_article_table() creates this automatically; this file is the
--  written record and the manual fallback. Safe to run more than once.
--  Run it in phpMyAdmin with the site's database selected.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `AgentArticle` (
  `id`           VARCHAR(191) NOT NULL,
  `runId`        VARCHAR(191) NOT NULL,
  `status`       VARCHAR(16)  NOT NULL,
  `regionName`   VARCHAR(191) NOT NULL,
  `category`     VARCHAR(191) NOT NULL,
  `title`        VARCHAR(255) NOT NULL,
  `standfirst`   TEXT NOT NULL,
  `body`         MEDIUMTEXT NOT NULL,
  `countries`    TEXT NOT NULL,
  `sources`      MEDIUMTEXT NOT NULL,
  `verifyPoints` TEXT NOT NULL,
  `confidence`   VARCHAR(8) NOT NULL,
  `createdAt`    DATETIME(3) NOT NULL,
  `updatedAt`    DATETIME(3) NOT NULL,
  `editedBy`     VARCHAR(191) NULL,
  `reviewedBy`   VARCHAR(191) NULL,
  `reviewedAt`   DATETIME(3) NULL,
  `reviewNotes`  TEXT NULL,
  `postId`       VARCHAR(191) NULL,
  PRIMARY KEY (`id`),
  KEY `AgentArticle_status_idx` (`status`, `createdAt`),
  KEY `AgentArticle_pair_idx` (`regionName`, `category`, `createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
