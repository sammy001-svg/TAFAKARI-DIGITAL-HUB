-- ============================================================================
--  006 — Tafakari Agent: run log, review queue, source keys
-- ============================================================================
--
--  WHY
--  ---
--  The Tafakari Agent (includes/agent.php) searches public sources with
--  Claude and proposes findings and intensity scores. Nothing it produces is
--  published: findings wait in a review queue until an analyst promotes one
--  into a DRAFT report, which then follows the normal approval workflow.
--
--    AgentRun        one row per agent run: country, status, token usage,
--                    web searches, estimated cost (drives the monthly budget
--                    guard), errors. The audit trail of every API call made.
--    AgentItem       the review queue: one proposed finding each, with its
--                    sources, element scores and the agent's rationale, and
--                    who reviewed it (PROPOSED / PROMOTED / REJECTED /
--                    DUPLICATE). Rows are never deleted.
--    AgentSourceKey  one row per (source URL, finding), so the same article
--                    is never queued twice.
--
--  YOU PROBABLY DO NOT NEED TO RUN THIS
--  ------------------------------------
--  ensure_agent_tables() creates these tables once, automatically, the first
--  time the agent page or the cron script runs. This file is the written
--  record, and the fallback where the database user cannot CREATE tables
--  (check the PHP error log for "[ensure_agent_tables]").
--
--  SAFE TO RUN
--  -----------
--  CREATE TABLE IF NOT EXISTS only: nothing existing is read or changed.
--
--  HOW TO RUN (cPanel)
--  -------------------
--  phpMyAdmin -> select your database -> SQL tab -> paste -> Go.
--  Then set the SiteSetting row `schema_agent_v1` to `ok` (or delete it).
--  Keep this file in step with agent_table_ddl() in includes/agent.php.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `AgentRun` (
    `id`             VARCHAR(191) NOT NULL,
    `triggeredBy`    VARCHAR(16)  NOT NULL,
    `startedBy`      VARCHAR(191) NULL,
    `country`        VARCHAR(100) NOT NULL,
    `status`         VARCHAR(16)  NOT NULL,
    `startedAt`      DATETIME(3)  NOT NULL,
    `finishedAt`     DATETIME(3)  NULL,
    `model`          VARCHAR(64)  NOT NULL,
    `servedBy`       VARCHAR(64)  NULL,
    `apiCalls`       INT NOT NULL DEFAULT 0,
    `inputTokens`    INT NOT NULL DEFAULT 0,
    `outputTokens`   INT NOT NULL DEFAULT 0,
    `cacheReadTokens`  INT NOT NULL DEFAULT 0,
    `cacheWriteTokens` INT NOT NULL DEFAULT 0,
    `webSearches`    INT NOT NULL DEFAULT 0,
    `webFetches`     INT NOT NULL DEFAULT 0,
    `costUsd`        DECIMAL(10,4) NOT NULL DEFAULT 0,
    `itemsProposed`  INT NOT NULL DEFAULT 0,
    `itemsDuplicate` INT NOT NULL DEFAULT 0,
    `itemsDiscarded` INT NOT NULL DEFAULT 0,
    `stopReason`     VARCHAR(32)  NULL,
    `error`          TEXT NULL,
    `notes`          TEXT NULL,
    PRIMARY KEY (`id`),
    KEY `AgentRun_startedAt_idx` (`startedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `AgentItem` (
    `id`           VARCHAR(191) NOT NULL,
    `runId`        VARCHAR(191) NOT NULL,
    `status`       VARCHAR(16)  NOT NULL,
    `title`        VARCHAR(255) NOT NULL,
    `summary`      TEXT NOT NULL,
    `countries`    TEXT NOT NULL,
    `region`       VARCHAR(191) NOT NULL DEFAULT '',
    `eventDate`    DATE NULL,
    `sources`      TEXT NOT NULL,
    `sourceCount`  SMALLINT NOT NULL DEFAULT 1,
    `scores`       TEXT NOT NULL,
    `rationales`   TEXT NOT NULL,
    `categories`   TEXT NOT NULL,
    `confidence`   VARCHAR(8) NOT NULL,
    `createdAt`    DATETIME(3) NOT NULL,
    `reviewedBy`   VARCHAR(191) NULL,
    `reviewedAt`   DATETIME(3) NULL,
    `reviewNotes`  TEXT NULL,
    `postId`       VARCHAR(191) NULL,
    PRIMARY KEY (`id`),
    KEY `AgentItem_status_idx` (`status`, `createdAt`),
    KEY `AgentItem_runId_idx` (`runId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `AgentSourceKey` (
    `urlKey` CHAR(40)     NOT NULL,
    `itemId` VARCHAR(191) NOT NULL,
    PRIMARY KEY (`urlKey`, `itemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `SiteSetting` (
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `updatedBy` VARCHAR(191) NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `SiteSetting` (`key`, `value`, `updatedAt`)
VALUES ('schema_agent_v1', 'ok', NOW(3))
ON DUPLICATE KEY UPDATE `value` = 'ok', `updatedAt` = NOW(3);
