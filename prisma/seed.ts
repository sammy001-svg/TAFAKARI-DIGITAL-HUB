/**
 * Tafakari Digital Hub — Database Seed
 *
 * Creates the initial admin accounts.
 * Run with:  npm run seed   (or: npx prisma db seed)
 *
 * Admin credentials that will be created:
 *   SUPER ADMIN
 *     Username : superadmin
 *     Email    : superadmin@tafakari.co.ke
 *     Password : Tafakari@2025!
 *
 *   EDITOR (ADMIN)
 *     Username : editor
 *     Email    : editor@tafakari.co.ke
 *     Password : Editor@2025!
 *
 * ⚠  Change passwords immediately after first login.
 */

import { PrismaClient } from "@prisma/client";
import bcrypt from "bcryptjs";

const prisma = new PrismaClient();

async function main() {
  console.log("🌱  Seeding database...\n");

  const SALT_ROUNDS = 12;

  const superAdminHash = await bcrypt.hash("Tafakari@2025!", SALT_ROUNDS);
  const editorHash     = await bcrypt.hash("Editor@2025!",   SALT_ROUNDS);

  // ── Super Admin ────────────────────────────────────────────────────────────
  const superAdmin = await prisma.user.upsert({
    where:  { username: "superadmin" },
    update: { password: superAdminHash },
    create: {
      name:     "Super Administrator",
      email:    "superadmin@tafakari.co.ke",
      username: "superadmin",
      password: superAdminHash,
      role:     "SUPER_ADMIN",
    },
  });

  console.log(`✅  Super Admin created`);
  console.log(`    Username : ${superAdmin.username}`);
  console.log(`    Email    : ${superAdmin.email}`);
  console.log(`    Password : Tafakari@2025!\n`);

  // ── Content Editor ─────────────────────────────────────────────────────────
  const editor = await prisma.user.upsert({
    where:  { username: "editor" },
    update: { password: editorHash },
    create: {
      name:     "Content Editor",
      email:    "editor@tafakari.co.ke",
      username: "editor",
      password: editorHash,
      role:     "ADMIN",
    },
  });

  console.log(`✅  Editor (Admin) created`);
  console.log(`    Username : ${editor.username}`);
  console.log(`    Email    : ${editor.email}`);
  console.log(`    Password : Editor@2025!\n`);

  console.log("🎉  Seeding complete!");
  console.log("────────────────────────────────────────");
  console.log("⚠   Change all passwords after first login.");
  console.log("    Admin panel → /admin/login");
  console.log("────────────────────────────────────────");
}

main()
  .catch((e) => {
    console.error("❌  Seed failed:", e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
