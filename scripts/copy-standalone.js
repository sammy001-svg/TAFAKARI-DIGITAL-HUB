#!/usr/bin/env node
// ─────────────────────────────────────────────────────────────────────────────
// Post-build script: copies static assets into .next/standalone so the
// Next.js standalone server can serve them without a separate CDN.
//
// Next.js deliberately omits these from the standalone bundle; they must be
// copied manually (or via this script) before the server is started.
//
// Copies:
//   .next/static/   →  .next/standalone/.next/static/
//   public/         →  .next/standalone/public/
// ─────────────────────────────────────────────────────────────────────────────

"use strict";

const { cpSync, existsSync } = require("fs");
const { join } = require("path");

const root       = process.cwd();
const standalone = join(root, ".next", "standalone");

if (!existsSync(standalone)) {
  // output:standalone not configured, or build hasn't run yet — skip silently.
  process.exit(0);
}

const pairs = [
  [join(root, ".next", "static"), join(standalone, ".next", "static")],
  [join(root, "public"),          join(standalone, "public")],
];

let copied = 0;
for (const [src, dest] of pairs) {
  if (existsSync(src)) {
    cpSync(src, dest, { recursive: true, force: true });
    console.log(`  copied  ${src.replace(root + "/", "")}  →  ${dest.replace(root + "/", "")}`);
    copied++;
  }
}

if (copied > 0) {
  console.log(`\n✓  Standalone static assets ready (${copied} director${copied === 1 ? "y" : "ies"} copied)\n`);
}
