// ─────────────────────────────────────────────────────────────────────────────
// Tafakari Digital Hub — cPanel Node.js App startup entry point
//
// cPanel Node.js App Manager should be configured with:
//   Application startup file: server.js
//
// The actual server is the Next.js standalone build produced by `npm run build`.
// This file simply validates the build exists and hands off to it.
// ─────────────────────────────────────────────────────────────────────────────

"use strict";

const path = require("path");
const fs   = require("fs");

const standaloneServer = path.join(__dirname, ".next", "standalone", "server.js");

if (!fs.existsSync(standaloneServer)) {
  console.error("\n─────────────────────────────────────────────────────────");
  console.error("  ERROR: .next/standalone/server.js not found.");
  console.error("  Run `npm run build` to generate the production build,");
  console.error("  then restart this application.");
  console.error("─────────────────────────────────────────────────────────\n");
  process.exit(1);
}

// cPanel Node.js App Manager sets PORT automatically.
// HOSTNAME must be 0.0.0.0 so cPanel's reverse proxy can reach it.
process.env.HOSTNAME    = process.env.HOSTNAME    ?? "0.0.0.0";
process.env.NODE_ENV    = process.env.NODE_ENV    ?? "production";

require(standaloneServer);
