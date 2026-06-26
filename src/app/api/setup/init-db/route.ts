/**
 * Database initialisation endpoint.
 * POST — creates all required tables using CREATE TABLE IF NOT EXISTS.
 * Only callable when no users exist (setup not yet complete).
 */
import { NextResponse } from "next/server";
import prisma from "@/lib/prisma";

export const dynamic = "force-dynamic";

// Statements executed in dependency order (User before Post, Post before Comment)
const DDL_STATEMENTS: { table: string; sql: string }[] = [
  {
    table: "User",
    sql: `CREATE TABLE IF NOT EXISTS \`User\` (
      \`id\`        VARCHAR(191)               NOT NULL,
      \`name\`      VARCHAR(191)               NULL,
      \`email\`     VARCHAR(191)               NULL,
      \`username\`  VARCHAR(191)               NULL,
      \`password\`  VARCHAR(191)               NOT NULL,
      \`role\`      ENUM('SUPER_ADMIN','ADMIN') NOT NULL DEFAULT 'ADMIN',
      \`createdAt\` DATETIME(3)                NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      \`updatedAt\` DATETIME(3)                NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
      PRIMARY KEY (\`id\`),
      UNIQUE KEY \`User_email_key\`    (\`email\`),
      UNIQUE KEY \`User_username_key\` (\`username\`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,
  },
  {
    table: "Post",
    sql: `CREATE TABLE IF NOT EXISTS \`Post\` (
      \`id\`             VARCHAR(191) NOT NULL,
      \`title\`          VARCHAR(191) NOT NULL,
      \`content\`        TEXT         NULL,
      \`description\`    TEXT         NULL,
      \`thumbnailUrl\`   VARCHAR(191) NULL,
      \`mediaUrl\`       VARCHAR(191) NULL,
      \`type\`           ENUM('ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT') NOT NULL DEFAULT 'ARTICLE',
      \`status\`         ENUM('DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED')    NOT NULL DEFAULT 'DRAFT',
      \`authorId\`       VARCHAR(191) NOT NULL,
      \`approverId\`     VARCHAR(191) NULL,
      \`rejectionNotes\` TEXT         NULL,
      \`country\`        VARCHAR(191) NOT NULL,
      \`region\`         VARCHAR(191) NOT NULL,
      \`issueCategory\`  VARCHAR(191) NOT NULL,
      \`viewCount\`      INT          NOT NULL DEFAULT 0,
      \`downloadCount\`  INT          NOT NULL DEFAULT 0,
      \`createdAt\`      DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      \`updatedAt\`      DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
      PRIMARY KEY (\`id\`),
      INDEX \`Post_authorId_idx\`   (\`authorId\`),
      INDEX \`Post_approverId_idx\` (\`approverId\`),
      INDEX \`Post_status_idx\`     (\`status\`),
      INDEX \`Post_country_idx\`    (\`country\`),
      CONSTRAINT \`Post_authorId_fkey\`
        FOREIGN KEY (\`authorId\`)   REFERENCES \`User\`(\`id\`) ON DELETE RESTRICT  ON UPDATE CASCADE,
      CONSTRAINT \`Post_approverId_fkey\`
        FOREIGN KEY (\`approverId\`) REFERENCES \`User\`(\`id\`) ON DELETE SET NULL  ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,
  },
  {
    table: "Comment",
    sql: `CREATE TABLE IF NOT EXISTS \`Comment\` (
      \`id\`          VARCHAR(191) NOT NULL,
      \`content\`     TEXT         NOT NULL,
      \`name\`        VARCHAR(191) NULL,
      \`email\`       VARCHAR(191) NULL,
      \`isModerated\` TINYINT(1)   NOT NULL DEFAULT 0,
      \`isFlagged\`   TINYINT(1)   NOT NULL DEFAULT 0,
      \`postId\`      VARCHAR(191) NOT NULL,
      \`createdAt\`   DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      PRIMARY KEY (\`id\`),
      INDEX \`Comment_postId_idx\`    (\`postId\`),
      INDEX \`Comment_isFlagged_idx\` (\`isFlagged\`),
      CONSTRAINT \`Comment_postId_fkey\`
        FOREIGN KEY (\`postId\`) REFERENCES \`Post\`(\`id\`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,
  },
  {
    table: "ContactMessage",
    sql: `CREATE TABLE IF NOT EXISTS \`ContactMessage\` (
      \`id\`        VARCHAR(191) NOT NULL,
      \`fullName\`  VARCHAR(191) NOT NULL,
      \`email\`     VARCHAR(191) NOT NULL,
      \`country\`   VARCHAR(191) NOT NULL,
      \`interest\`  VARCHAR(191) NOT NULL,
      \`message\`   TEXT         NOT NULL,
      \`isRead\`    TINYINT(1)   NOT NULL DEFAULT 0,
      \`createdAt\` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      PRIMARY KEY (\`id\`),
      INDEX \`ContactMessage_isRead_idx\` (\`isRead\`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,
  },
  {
    table: "HeatmapCountry",
    sql: `CREATE TABLE IF NOT EXISTS \`HeatmapCountry\` (
      \`id\`        VARCHAR(191) NOT NULL,
      \`name\`      VARCHAR(191) NOT NULL,
      \`code\`      VARCHAR(10)  NOT NULL,
      \`centerLat\` DOUBLE       NOT NULL,
      \`centerLng\` DOUBLE       NOT NULL,
      \`zoom\`      INT          NOT NULL DEFAULT 6,
      \`isActive\`  TINYINT(1)   NOT NULL DEFAULT 1,
      \`createdAt\` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      \`updatedAt\` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
      PRIMARY KEY (\`id\`),
      UNIQUE KEY \`HeatmapCountry_code_key\` (\`code\`),
      INDEX \`HeatmapCountry_isActive_idx\` (\`isActive\`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,
  },
  {
    table: "HeatmapCategory",
    sql: `CREATE TABLE IF NOT EXISTS \`HeatmapCategory\` (
      \`id\`        VARCHAR(191) NOT NULL,
      \`name\`      VARCHAR(191) NOT NULL,
      \`isActive\`  TINYINT(1)   NOT NULL DEFAULT 1,
      \`createdAt\` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      \`updatedAt\` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
      PRIMARY KEY (\`id\`),
      UNIQUE KEY \`HeatmapCategory_name_key\` (\`name\`),
      INDEX \`HeatmapCategory_isActive_idx\` (\`isActive\`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,
  },
];

export async function POST() {
  try {
    // Safety: only callable before any user exists
    let userCount = 0;
    try {
      userCount = await prisma.user.count();
    } catch {
      // User table might not exist yet — that's fine, proceed with init
    }
    if (userCount > 0) {
      return NextResponse.json(
        { error: "Setup already complete." },
        { status: 403 }
      );
    }

    const created: string[] = [];
    const errors: string[] = [];

    for (const { table, sql } of DDL_STATEMENTS) {
      try {
        await prisma.$executeRawUnsafe(sql);
        created.push(table);
      } catch (err) {
        errors.push(
          `${table}: ${err instanceof Error ? err.message : String(err)}`
        );
      }
    }

    if (errors.length > 0) {
      return NextResponse.json({ success: false, created, errors }, { status: 500 });
    }

    return NextResponse.json({ success: true, created, errors: [] });
  } catch (err) {
    console.error("[setup/init-db]", err);
    return NextResponse.json(
      { error: err instanceof Error ? err.message : "Failed to initialize database." },
      { status: 500 }
    );
  }
}
