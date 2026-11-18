-- 015: Document grades (table AgentGrade). Optional: the site creates this table by itself.
-- Safe to run more than once. To run it by hand, either:
--   phpMyAdmin > your database > Import > choose this file > Go   (recommended), or
--   phpMyAdmin > your database > SQL > paste everything from CREATE TABLE to the end > Go.

CREATE TABLE IF NOT EXISTS `AgentGrade` (
  `id`          VARCHAR(191) NOT NULL,
  `postId`      VARCHAR(191) NOT NULL,
  `runId`       VARCHAR(191) NULL,
  `status`      VARCHAR(16)  NOT NULL,
  `scores`      TEXT NOT NULL,
  `reasons`     TEXT NOT NULL,
  `finalScores` TEXT NULL,
  `confidence`  VARCHAR(8)   NOT NULL DEFAULT '',
  `summary`     TEXT NULL,
  `notes`       TEXT NULL,
  `basis`       VARCHAR(255) NOT NULL DEFAULT '',
  `contentHash` CHAR(40)     NOT NULL,
  `attempts`    INT NOT NULL DEFAULT 0,
  `createdAt`   DATETIME(3)  NOT NULL,
  `updatedAt`   DATETIME(3)  NOT NULL,
  `reviewedBy`  VARCHAR(191) NULL,
  `reviewedAt`  DATETIME(3)  NULL,
  `reviewNotes` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `AgentGrade_postId_key` (`postId`),
  KEY `AgentGrade_status_idx` (`status`, `updatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `SiteSetting` (`key`, `value`, `updatedAt`)
VALUES ('schema_agent_grades_v1', 'ok', NOW(3))
ON DUPLICATE KEY UPDATE `value` = 'ok', `updatedAt` = NOW(3);

-- Check: this should list one table named AgentGrade.
SHOW TABLES LIKE 'AgentGrade';

-- NOTES
--
-- What the table is for: five minutes after a document is posted without
-- intensity scores, the Tafakari Agent reads it and proposes a 1-10 score for
-- each heat-map element it reports on, with a reason for each. The proposal
-- waits here until a person accepts it (as proposed or adjusted) or rejects
-- it. Only accepted scores are written to Post.intensityScores and count on
-- the heat map. One row per document: the latest grading of it.
--
-- status values:
--   QUEUED      asked to be graded again
--   PROPOSED    waiting for review
--   ACCEPTED    accepted, and finalScores holds the scores as saved
--   REJECTED    rejected by a reviewer (reviewNotes says why)
--   DECLINED    nothing to grade (too little text, or not about a conflict or
--               peace situation), tried again once the document is edited
--   FAILED      an error, retried an hour later, up to three times
--   SUPERSEDED  someone scored the document by hand in the meantime
--
-- When you need this file: includes/agent-grades.php (ensure_agent_grade_table)
-- creates the table automatically, once, the first time grading runs or the
-- Document grades page is opened. Run this file only if the database user has
-- no CREATE privilege. The PHP error log then contains the line
-- "[ensure_agent_grade_table]".
