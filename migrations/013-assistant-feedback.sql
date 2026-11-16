-- ============================================================================
--  013 - Ask Tafakari: visitor feedback on answers
-- ============================================================================
--  Adds one column to AssistantLog: the visitor's rating of an answer,
--  1 (helpful) or -1 (not helpful), NULL when not rated. Questions and answers
--  are still NOT stored; the rating is attached only to the usage row.
--
--  ensure_assistant_table() adds this automatically; this file is the written
--  record and the manual fallback. If phpMyAdmin reports "Duplicate column
--  name 'rating'", it has already been added.
--  Run it in phpMyAdmin with the site's database selected.
-- ============================================================================

ALTER TABLE `AssistantLog` ADD COLUMN `rating` TINYINT NULL;
