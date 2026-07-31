-- ============================================================================
--  001 — Widen Post columns that were overflowing VARCHAR(191)
-- ============================================================================
--
--  WHY
--  ---
--  `Post` stored title, URLs and the issue-category list in VARCHAR(191).
--  That was too small in practice:
--
--    * issueCategory holds a COMMA-JOINED list from the multi-select.
--      All 12 built-in categories join to 233 characters — 11 or more
--      overflowed the column.
--    * thumbnailUrl / mediaUrl hold URLs. Upload URLs and external links
--      (YouTube, CDNs with query strings) routinely exceed 191 characters.
--
--  Because PDO runs in ERRMODE_EXCEPTION and the admin save handlers had no
--  try/catch, an overflow threw an uncaught PDOException. With display_errors
--  off that produced a blank response — the edit appeared to "not save", with
--  no error shown to the user.
--
--  SAFE TO RUN
--  -----------
--  None of these columns are indexed (Post has only PRIMARY KEY (`id`) and a
--  foreign key on `authorId`), so the utf8mb4 767-byte index limit — the usual
--  reason for choosing 191 — does not apply here.
--
--  Widening a VARCHAR is an online, non-destructive change. No data is
--  truncated or lost. Re-running it is harmless.
--
--  HOW TO RUN (cPanel)
--  -------------------
--  phpMyAdmin -> select your database -> SQL tab -> paste -> Go.
-- ============================================================================

ALTER TABLE `Post`
  MODIFY `title`         VARCHAR(255)  NOT NULL,
  MODIFY `issueCategory` VARCHAR(512)  NOT NULL,
  MODIFY `thumbnailUrl`  VARCHAR(1024) NULL,
  MODIFY `mediaUrl`      VARCHAR(1024) NULL;

-- Verify:
--   SHOW FULL COLUMNS FROM `Post`
--     WHERE Field IN ('title','issueCategory','thumbnailUrl','mediaUrl');
