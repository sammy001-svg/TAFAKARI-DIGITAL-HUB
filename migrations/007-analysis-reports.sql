-- ============================================================================
--  007 — Analysis reports (bi-weekly and quarterly)
-- ============================================================================
--
--  WHY
--  ---
--  CRTP publishes bi-weekly updates (one per monitoring cycle) and quarterly
--  analyses. Each report freezes the heat-map figures for its period and scope
--  (all countries, a monitoring region, or one country) as a JSON snapshot, so a
--  published report keeps its numbers as later reports arrive. Drafts can be
--  refreshed until they are published.
--
--    snapshot         every figure, list and chart series the report shows
--    narrative        the analysis text (Markdown), written by an analyst or
--                     drafted by the Tafakari Agent and then edited
--    narrativeSource  ANALYST | AI | AI_REVIEWED, shown to readers
--    status           DRAFT -> PENDING (submitted) -> PUBLISHED (super admin)
--
--  One report per (kind, period, scope): the unique key stops duplicates.
--
--  YOU PROBABLY DO NOT NEED TO RUN THIS
--  ------------------------------------
--  ensure_report_tables() creates the table once, automatically, the first time
--  the Analysis Reports admin page or cron/report-generate.php runs. This file
--  is the written record and the fallback where the database user cannot
--  CREATE tables (check the PHP error log for "[ensure_report_tables]").
--
--  HOW TO RUN (cPanel)
--  -------------------
--  phpMyAdmin -> select your database -> SQL tab -> paste -> Go.
--  Then set the SiteSetting row `schema_reports_v1` to `ok` (or delete it).
--  Keep this file in step with report_table_ddl() in includes/reports.php.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `AnalysisReport` (
    `id`              VARCHAR(191) NOT NULL,
    `kind`            VARCHAR(16)  NOT NULL,
    `periodStart`     DATE         NOT NULL,
    `periodEnd`       DATE         NOT NULL,
    `scope`           VARCHAR(150) NOT NULL,
    `title`           VARCHAR(255) NOT NULL,
    `status`          VARCHAR(16)  NOT NULL,
    `snapshot`        MEDIUMTEXT   NOT NULL,
    `snapshotAt`      DATETIME(3)  NOT NULL,
    `narrative`       MEDIUMTEXT   NULL,
    `narrativeSource` VARCHAR(16)  NULL,
    `narrativeAt`     DATETIME(3)  NULL,
    `createdBy`       VARCHAR(191) NULL,
    `createdAt`       DATETIME(3)  NOT NULL,
    `updatedAt`       DATETIME(3)  NOT NULL,
    `submittedBy`     VARCHAR(191) NULL,
    `approvedBy`      VARCHAR(191) NULL,
    `publishedAt`     DATETIME(3)  NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `AnalysisReport_period_scope` (`kind`, `periodStart`, `scope`),
    KEY `AnalysisReport_status_idx` (`status`, `periodStart`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
