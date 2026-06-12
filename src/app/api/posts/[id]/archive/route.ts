/**
 * POST /api/posts/[id]/archive
 * Super Admin only. Toggles a post between PUBLISHED ↔ ARCHIVED.
 */
import { NextRequest, NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import prisma from "@/lib/prisma";

export const dynamic = "force-dynamic";

export async function POST(
  _req: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getServerSession(authOptions);
  if (!session) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }
  if (session.user.role !== "SUPER_ADMIN") {
    return NextResponse.json({ error: "Super Admin access required" }, { status: 403 });
  }

  const { id } = await params;
  const post = await prisma.post.findUnique({ where: { id }, select: { id: true, status: true } });

  if (!post) {
    return NextResponse.json({ error: "Post not found" }, { status: 404 });
  }

  if (post.status !== "PUBLISHED" && post.status !== "ARCHIVED") {
    return NextResponse.json(
      { error: "Only PUBLISHED or ARCHIVED posts can be toggled." },
      { status: 400 }
    );
  }

  const newStatus = post.status === "PUBLISHED" ? "ARCHIVED" : "PUBLISHED";

  const updated = await prisma.post.update({
    where: { id },
    data: { status: newStatus },
    select: { id: true, status: true },
  });

  return NextResponse.json({ data: updated });
}
