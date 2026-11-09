-- ============================================================================
--  011 - Videos found by the Tafakari Agent, awaiting approval
-- ============================================================================
--  An approved video becomes an ordinary VIDEO row in Post. Rejected videos
--  stay here so the agent does not suggest them again.
--
--  ensure_agent_video_table() creates this automatically; this file is the
--  written record and the manual fallback. Safe to run more than once.
--  Run it in phpMyAdmin with the site's database selected.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `AgentVideo` (
  `id`         VARCHAR(191) NOT NULL,
  `runId`      VARCHAR(191) NOT NULL,
  `status`     VARCHAR(16)  NOT NULL,
  `videoId`    VARCHAR(16)  NOT NULL,
  `title`      VARCHAR(255) NOT NULL,
  `channel`    VARCHAR(191) NOT NULL,
  `summary`    TEXT NOT NULL,
  `regionName` VARCHAR(191) NOT NULL,
  `category`   VARCHAR(191) NOT NULL,
  `countries`  TEXT NOT NULL,
  `createdAt`  DATETIME(3) NOT NULL,
  `reviewedBy` VARCHAR(191) NULL,
  `reviewedAt` DATETIME(3) NULL,
  `postId`     VARCHAR(191) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `AgentVideo_videoId_key` (`videoId`),
  KEY `AgentVideo_status_idx` (`status`, `createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
