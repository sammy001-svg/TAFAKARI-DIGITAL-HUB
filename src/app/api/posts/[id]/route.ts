import { NextRequest, NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import prisma from "@/lib/prisma";
import { ContentType } from "@prisma/client";

export const dynamic = "force-dynamic";

type RouteContext = { params: Promise<{ id: string }> };

async function getPostOrError(id: string, userId: string, role: string) {
  const post = await prisma.post.findUnique({
    where: { id },
    include: {
      author: { select: { id: true, name: true, username: true } },
      approver: { select: { id: true, name: true } },
    },
  });
  if (!post) return { error: "Not found", status: 404, post: null };
  if (role !== "SUPER_ADMIN" && post.authorId !== userId)
    return { error: "Forbidden", status: 403, post: null };
  return { post, error: null, status: 200 };
}

export async function GET(_req: NextRequest, ctx: RouteContext) {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await ctx.params;
  const { post, error, status } = await getPostOrError(id, session.user.id, session.user.role ?? "");
  if (error) return NextResponse.json({ error }, { status });
  return NextResponse.json({ data: post });
}

export async function PUT(req: NextRequest, ctx: RouteContext) {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await ctx.params;
  const { post, error, status } = await getPostOrError(id, session.user.id, session.user.role ?? "");
  if (error) return NextResponse.json({ error }, { status });

  // ADMIN cannot edit published/archived posts
  if (
    session.user.role !== "SUPER_ADMIN" &&
    (post!.status === "PUBLISHED" || post!.status === "ARCHIVED")
  ) {
    return NextResponse.json({ error: "Cannot edit a published or archived post" }, { status: 403 });
  }

  const body = await req.json();
  const { title, content, description, type, country, region, issueCategory, thumbnailUrl, mediaUrl } = body;

  const updated = await prisma.post.update({
    where: { id },
    data: {
      ...(title !== undefined && { title }),
      ...(content !== undefined && { content }),
      ...(description !== undefined && { description }),
      ...(type !== undefined && { type: type as ContentType }),
      ...(country !== undefined && { country }),
      ...(region !== undefined && { region }),
      ...(issueCategory !== undefined && { issueCategory }),
      ...(thumbnailUrl !== undefined && { thumbnailUrl }),
      ...(mediaUrl !== undefined && { mediaUrl }),
    },
    include: {
      author: { select: { id: true, name: true, username: true } },
      approver: { select: { id: true, name: true } },
    },
  });

  return NextResponse.json({ data: updated });
}

export async function DELETE(_req: NextRequest, ctx: RouteContext) {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await ctx.params;
  const { post, error, status } = await getPostOrError(id, session.user.id, session.user.role ?? "");
  if (error) return NextResponse.json({ error }, { status });

  // ADMIN can only delete their own DRAFT or REJECTED posts
  if (
    session.user.role !== "SUPER_ADMIN" &&
    post!.status !== "DRAFT" &&
    post!.status !== "REJECTED"
  ) {
    return NextResponse.json(
      { error: "Can only delete DRAFT or REJECTED posts" },
      { status: 403 }
    );
  }

  await prisma.post.delete({ where: { id } });
  return NextResponse.json({ data: { success: true } });
}
