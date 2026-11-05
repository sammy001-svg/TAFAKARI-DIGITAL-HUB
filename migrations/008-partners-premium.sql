-- ============================================================================
--  008 — Partners: type, country, featured, partner-since
-- ============================================================================
--
--  WHY
--  ---
--  The redesigned Partners module groups partners by type on the public
--  /partners directory, spotlights featured partners first (there, on the
--  About page and in the footer), and shows each partner's country and the
--  year the partnership began.
--
--    category      ACADEMIC | FAITH | CIVIL | REGIONAL | UN | GOVERNMENT |
--                  FUNDING | MEDIA | NETWORK (default). Labels live in
--                  partner_types() in includes/partners.php.
--    country       a country name from the site's list, "International", or NULL
--    featured      1 = listed first and given a spotlight card
--    partnerSince  four-digit year, or NULL
--
--  YOU PROBABLY DO NOT NEED TO RUN THIS
--  ------------------------------------
--  ensure_partner_schema() adds these columns once, automatically, the first
--  time the admin Partners page or the public Partners page loads. This file is
--  the written record and the fallback where the database user has no ALTER
--  privilege (check the PHP error log for "[ensure_partner_schema]").
--
--  SAFE TO RUN
--  -----------
--  Adds four columns with safe defaults; existing partners become "Network
--  member", not featured. Nothing is removed. If phpMyAdmin reports a
--  duplicate column, the columns were already added automatically.
--
--  HOW TO RUN (cPanel)
--  -------------------
--  phpMyAdmin -> select your database -> SQL tab -> paste -> Go.
--  Then set the SiteSetting row `schema_partner_v2` to `ok` (or delete it).
-- ============================================================================

ALTER TABLE `Partner`
  ADD COLUMN `category`     VARCHAR(20)  NOT NULL DEFAULT 'NETWORK',
  ADD COLUMN `country`      VARCHAR(100) NULL,
  ADD COLUMN `featured`     TINYINT(1)   NOT NULL DEFAULT 0,
  ADD COLUMN `partnerSince` SMALLINT     NULL;
