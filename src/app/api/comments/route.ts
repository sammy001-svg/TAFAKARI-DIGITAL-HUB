import { NextRequest, NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import prisma from "@/lib/prisma";

export const dynamic = "force-dynamic";

export async function GET(req: NextRequest) {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  if (session.user.role !== "SUPER_ADMIN")
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const { searchParams } = req.nextUrl;
  const isFlagged = searchParams.get("isFlagged");
  const isModerated = searchParams.get("isModerated");
  const postId = searchParams.get("postId");
  const page = Math.max(1, parseInt(searchParams.get("page") ?? "1"));
  const limit = Math.min(100, parseInt(searchParams.get("limit") ?? "20"));

  const where = {
    ...(isFlagged !== null && { isFlagged: isFlagged === "true" }),
    ...(isModerated !== null && { isModerated: isModerated === "true" }),
    ...(postId && { postId }),
  };

  const [comments, total] = await Promise.all([
    prisma.comment.findMany({
      where,
      include: { post: { select: { id: true, title: true } } },
      orderBy: { createdAt: "desc" },
      skip: (page - 1) * limit,
      take: limit,
    }),
    prisma.comment.count({ where }),
  ]);

  return NextResponse.json({ data: { comments, total, page, limit } });
}

export async function POST(req: NextRequest) {
  const body = await req.json().catch(() => null);
  if (!body) return NextResponse.json({ error: "Invalid JSON" }, { status: 400 });

  const { content, name, email, postId } = body;

  if (!content?.trim() || !postId) {
    return NextResponse.json({ error: "content and postId are required" }, { status: 400 });
  }

  const post = await prisma.post.findFirst({
    where: { id: postId, status: "PUBLISHED" },
    select: { id: true },
  });
  if (!post) return NextResponse.json({ error: "Post not found" }, { status: 404 });

  const comment = await prisma.comment.create({
    data: {
      content: content.trim().slice(0, 2000),
      name: name?.trim().slice(0, 100) ?? null,
      email: email?.trim().slice(0, 200) ?? null,
      postId,
    },
  });

  return NextResponse.json({ data: comment }, { status: 201 });
}
