-- ============================================================================
--  014 - AI articles and videos can carry several issue categories
-- ============================================================================
--  Adds one nullable column to each table: a JSON list of the issue
--  categories the Tafakari Agent judged the article or video also addresses,
--  beyond its main one (AgentArticle.category / AgentVideo.category). When it
--  is published, Post.issueCategory holds the main category first, then these.
--
--  ensure_agent_article_table() and ensure_agent_video_table() add the columns
--  automatically; this file is the written record and the manual fallback.
--  If phpMyAdmin reports "Duplicate column name 'categories'", that column has
--  already been added. Run it in phpMyAdmin with the site's database selected.
-- ============================================================================

ALTER TABLE `AgentArticle` ADD COLUMN `categories` TEXT NULL;
ALTER TABLE `AgentVideo`   ADD COLUMN `categories` TEXT NULL;
