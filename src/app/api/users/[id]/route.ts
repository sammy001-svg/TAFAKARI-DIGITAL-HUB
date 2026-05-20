import { NextRequest, NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import prisma from "@/lib/prisma";
import bcrypt from "bcryptjs";
import { Role } from "@prisma/client";

export const dynamic = "force-dynamic";

type RouteContext = { params: Promise<{ id: string }> };

const userSelect = {
  id: true,
  name: true,
  email: true,
  username: true,
  role: true,
  createdAt: true,
  _count: { select: { posts: true } },
} as const;

export async function GET(_req: NextRequest, ctx: RouteContext) {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await ctx.params;

  // Users can view their own profile; SUPER_ADMIN can view anyone
  if (session.user.role !== "SUPER_ADMIN" && session.user.id !== id) {
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });
  }

  const user = await prisma.user.findUnique({ where: { id }, select: userSelect });
  if (!user) return NextResponse.json({ error: "Not found" }, { status: 404 });
  return NextResponse.json({ data: user });
}

export async function PUT(req: NextRequest, ctx: RouteContext) {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await ctx.params;

  // Users can update themselves; SUPER_ADMIN can update anyone
  if (session.user.role !== "SUPER_ADMIN" && session.user.id !== id) {
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });
  }

  const body = await req.json();
  const { name, email, username, role, password } = body;

  // Prevent self-role change
  if (role && session.user.id === id && role !== session.user.role) {
    return NextResponse.json({ error: "Cannot change your own role" }, { status: 400 });
  }

  // Only SUPER_ADMIN can set role
  if (role && session.user.role !== "SUPER_ADMIN") {
    return NextResponse.json({ error: "Only Super Admin can change roles" }, { status: 403 });
  }

  // Check for conflicts on email/username if they're being changed
  if (email) {
    const conflict = await prisma.user.findFirst({ where: { email, NOT: { id } } });
    if (conflict) return NextResponse.json({ error: "Email already in use" }, { status: 409 });
  }
  if (username) {
    const conflict = await prisma.user.findFirst({ where: { username, NOT: { id } } });
    if (conflict) return NextResponse.json({ error: "Username already taken" }, { status: 409 });
  }

  const updateData: Record<string, unknown> = {};
  if (name !== undefined) updateData.name = name;
  if (email !== undefined) updateData.email = email;
  if (username !== undefined) updateData.username = username;
  if (role !== undefined) updateData.role = role as Role;
  if (password) {
    if (password.length < 8)
      return NextResponse.json({ error: "Password must be at least 8 characters" }, { status: 400 });
    updateData.password = await bcrypt.hash(password, 12);
  }

  const updated = await prisma.user.update({
    where: { id },
    data: updateData,
    select: userSelect,
  });

  return NextResponse.json({ data: updated });
}

export async function DELETE(_req: NextRequest, ctx: RouteContext) {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  if (session.user.role !== "SUPER_ADMIN")
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const { id } = await ctx.params;

  if (session.user.id === id) {
    return NextResponse.json({ error: "Cannot delete your own account" }, { status: 400 });
  }

  const user = await prisma.user.findUnique({
    where: { id },
    select: { id: true, _count: { select: { posts: true } } },
  });
  if (!user) return NextResponse.json({ error: "Not found" }, { status: 404 });

  if (user._count.posts > 0) {
    return NextResponse.json(
      { error: "User has existing posts. Delete or reassign posts first." },
      { status: 409 }
    );
  }

  await prisma.user.delete({ where: { id } });
  return NextResponse.json({ data: { success: true } });
}
