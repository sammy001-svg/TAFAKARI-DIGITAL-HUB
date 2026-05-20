import { NextRequest, NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import prisma from "@/lib/prisma";

export const dynamic = "force-dynamic";

type RouteContext = { params: Promise<{ id: string }> };

export async function POST(_req: NextRequest, ctx: RouteContext) {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await ctx.params;
  const post = await prisma.post.findUnique({ where: { id } });
  if (!post) return NextResponse.json({ error: "Not found" }, { status: 404 });

  // ADMIN must own the post
  if (session.user.role !== "SUPER_ADMIN" && post.authorId !== session.user.id) {
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });
  }

  if (post.status !== "DRAFT" && post.status !== "REJECTED") {
    return NextResponse.json(
      { error: "Only DRAFT or REJECTED posts can be submitted for review" },
      { status: 409 }
    );
  }

  const updated = await prisma.post.update({
    where: { id },
    data: { status: "PENDING", rejectionNotes: null },
    include: {
      author: { select: { id: true, name: true, username: true } },
      approver: { select: { id: true, name: true } },
    },
  });

  return NextResponse.json({ data: updated });
}
