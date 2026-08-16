-- ============================================================================
--  002 — Add POLICY_BRIEF to the Post.type ENUM
-- ============================================================================
--
--  WHY
--  ---
--  New "Policy Brief" content type, alongside Article/Gallery/Podcast/Video/
--  Document. `Post.type` is a MySQL ENUM, so a new value requires widening
--  the column definition — a plain INSERT with type='POLICY_BRIEF' is
--  rejected (or silently blanked outside strict mode) until this runs.
--
--  NOTE
--  ----
--  The app widens this automatically at runtime via
--  ensure_policy_brief_post_type() in includes/functions.php (same pattern
--  as ensure_comment_rating_column()), so manually running this file is
--  optional — it's here for documentation and for anyone who prefers to
--  apply schema changes by hand before deploying.
--
--  SAFE TO RUN
--  -----------
--  Adding an ENUM value is non-destructive. Existing rows are unaffected.
--  Re-running it is harmless.
--
--  HOW TO RUN (cPanel)
--  -------------------
--  phpMyAdmin -> select your database -> SQL tab -> paste -> Go.
-- ============================================================================

ALTER TABLE `Post`
  MODIFY `type` ENUM('ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT','POLICY_BRIEF') NOT NULL DEFAULT 'ARTICLE';

-- Verify:
--   SHOW COLUMNS FROM `Post` LIKE 'type';
