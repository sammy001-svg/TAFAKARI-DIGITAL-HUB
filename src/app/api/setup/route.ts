/**
 * One-time setup endpoint.
 * GET  — reports DB connectivity, schema status, and whether setup is needed.
 * POST — creates the first Super Admin account (disabled once any user exists).
 */
import { NextRequest, NextResponse } from "next/server";
import prisma from "@/lib/prisma";
import bcrypt from "bcryptjs";

export const dynamic = "force-dynamic";

type TableRow = { TABLE_NAME: string };

export async function GET() {
  try {
    await prisma.$queryRaw`SELECT 1`;

    const rows = await prisma.$queryRaw<TableRow[]>`
      SELECT TABLE_NAME
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME IN ('User','Post','Comment','ContactMessage')
    `;

    const existingTables = rows.map((r) => r.TABLE_NAME);
    const required = ["User", "Post", "Comment", "ContactMessage"];
    const missingTables = required.filter((t) => !existingTables.includes(t));
    const tablesReady = missingTables.length === 0;

    let userCount = 0;
    if (tablesReady) {
      userCount = await prisma.user.count();
    }

    return NextResponse.json({
      dbConnected: true,
      tablesReady,
      missingTables,
      setupRequired: tablesReady && userCount === 0,
      userCount,
    });
  } catch (err) {
    console.error("[setup/GET]", err);
    return NextResponse.json(
      {
        dbConnected: false,
        tablesReady: false,
        missingTables: ["User", "Post", "Comment", "ContactMessage"],
        setupRequired: false,
        userCount: 0,
        error:
          err instanceof Error ? err.message : "Cannot connect to the database.",
      },
      { status: 503 }
    );
  }
}

export async function POST(req: NextRequest) {
  try {
    // Guard: only allowed when no users exist
    const count = await prisma.user.count();
    if (count > 0) {
      return NextResponse.json(
        { error: "Setup already complete. Use the login page instead." },
        { status: 403 }
      );
    }

    const body = await req.json();
    const { name, email, username, password } = body;

    if (!name || !email || !username || !password) {
      return NextResponse.json(
        { error: "All fields are required." },
        { status: 400 }
      );
    }

    if (password.length < 8) {
      return NextResponse.json(
        { error: "Password must be at least 8 characters." },
        { status: 400 }
      );
    }

    const hashedPassword = await bcrypt.hash(password, 12);

    const user = await prisma.user.create({
      data: {
        name,
        email,
        username,
        password: hashedPassword,
        role: "SUPER_ADMIN",
      },
      select: { id: true, name: true, email: true, username: true, role: true },
    });

    return NextResponse.json({ data: user }, { status: 201 });
  } catch (err) {
    console.error("[setup/POST] Error:", err);
    return NextResponse.json(
      { error: "Database error. Ensure MySQL is running and DATABASE_URL is correct." },
      { status: 503 }
    );
  }
}
