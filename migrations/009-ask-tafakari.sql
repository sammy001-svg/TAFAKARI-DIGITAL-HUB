-- ============================================================================
--  009 - Ask Tafakari: usage log for the public assistant
-- ============================================================================
--  Counts, tokens and estimated cost per question, for rate limits and the
--  budget. Questions and answers are NOT stored. ipHash is keyed with a daily
--  salt, so a visitor cannot be followed from one day to the next.
--
--  ensure_assistant_table() creates this automatically; this file is the
--  written record and the manual fallback. Safe to run more than once.
--  Run it in phpMyAdmin with the site's database selected.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `AssistantLog` (
  `id`           VARCHAR(191) NOT NULL,
  `ipHash`       CHAR(64)     NOT NULL,
  `status`       VARCHAR(16)  NOT NULL,
  `createdAt`    DATETIME(3)  NOT NULL,
  `sources`      INT NOT NULL DEFAULT 0,
  `inputTokens`  INT NOT NULL DEFAULT 0,
  `outputTokens` INT NOT NULL DEFAULT 0,
  `cacheReadTokens`  INT NOT NULL DEFAULT 0,
  `cacheWriteTokens` INT NOT NULL DEFAULT 0,
  `costUsd`      DECIMAL(10,4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `AssistantLog_createdAt_idx` (`createdAt`),
  KEY `AssistantLog_ip_idx` (`ipHash`, `createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
