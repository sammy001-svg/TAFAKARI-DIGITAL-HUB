-- ============================================================================
--  012 - Country monitoring updates by the Tafakari Agent
-- ============================================================================
--  One row per country and monitoring cycle (kind CYCLE), or per country and
--  quarter (kind QUARTER), following CRTP's Bi-weekly Country Conflict and
--  Peace Monitoring template: overall score and justification, key
--  developments, the four heat-map clusters, early warning and early response
--  indicators, analysis of the period, executive summary, proposed element
--  scores, a fact check of the key claims, and the sources.
--
--    data        JSON: the template's tables, by section
--    scores      JSON: element => proposed score (1-10)
--    rationales  JSON: element => why
--    factCheck   JSON: [{claim, status, sources, note}]
--    sources     JSON: [{url, publisher, title, published}]
--    status      PROPOSED -> PUBLISHED (by a super admin) | REJECTED
--    postId      the published Post (a scored DOCUMENT, so the heat map and
--                the analysis reports use it)
--
--  ensure_agent_update_table() creates this automatically; this file is the
--  written record and the manual fallback. Safe to run more than once.
--  Run it in phpMyAdmin with the site's database selected.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `AgentCountryUpdate` (
  `id`               VARCHAR(191) NOT NULL,
  `runId`            VARCHAR(191) NOT NULL,
  `kind`             VARCHAR(16)  NOT NULL,
  `country`          VARCHAR(100) NOT NULL,
  `periodStart`      DATE         NOT NULL,
  `periodEnd`        DATE         NOT NULL,
  `status`           VARCHAR(16)  NOT NULL,
  `overallScore`     DECIMAL(3,1) NOT NULL,
  `justification`    TEXT NOT NULL,
  `executiveSummary` TEXT NOT NULL,
  `analysis`         MEDIUMTEXT NOT NULL,
  `data`             MEDIUMTEXT NOT NULL,
  `scores`           TEXT NOT NULL,
  `rationales`       MEDIUMTEXT NOT NULL,
  `factCheck`        MEDIUMTEXT NOT NULL,
  `sources`          MEDIUMTEXT NOT NULL,
  `confidence`       VARCHAR(8)  NOT NULL,
  `sourceCount`      SMALLINT    NOT NULL DEFAULT 1,
  `notes`            TEXT NULL,
  `createdAt`        DATETIME(3) NOT NULL,
  `updatedAt`        DATETIME(3) NOT NULL,
  `editedBy`         VARCHAR(191) NULL,
  `reviewedBy`       VARCHAR(191) NULL,
  `reviewedAt`       DATETIME(3) NULL,
  `reviewNotes`      TEXT NULL,
  `postId`           VARCHAR(191) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `AgentCountryUpdate_period_key` (`kind`, `country`, `periodStart`),
  KEY `AgentCountryUpdate_status_idx` (`status`, `createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
