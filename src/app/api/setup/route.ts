/**
 * One-time setup endpoint.
 * Creates the first Super Admin account.
 * Automatically disabled once any user exists in the database.
 */
import { NextRequest, NextResponse } from "next/server";
import prisma from "@/lib/prisma";
import bcrypt from "bcryptjs";

export const dynamic = "force-dynamic";

export async function GET() {
  try {
    const count = await prisma.user.count();
    return NextResponse.json({ setupRequired: count === 0 });
  } catch (err) {
    console.error("[setup/GET] Database error:", err);
    return NextResponse.json(
      { error: "Cannot connect to the database. Check DATABASE_URL and ensure MySQL is running.", setupRequired: false },
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
