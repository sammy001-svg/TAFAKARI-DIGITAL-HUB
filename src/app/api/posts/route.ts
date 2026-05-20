import { NextRequest, NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import prisma from "@/lib/prisma";
import { ContentType, ContentStatus } from "@prisma/client";

export const dynamic = "force-dynamic";

export async function GET(req: NextRequest) {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { searchParams } = req.nextUrl;
  const status = searchParams.get("status") as ContentStatus | null;
  const type = searchParams.get("type") as ContentType | null;
  const country = searchParams.get("country");
  const issueCategory = searchParams.get("issueCategory");
  const page = Math.max(1, parseInt(searchParams.get("page") ?? "1"));
  const limit = Math.min(100, parseInt(searchParams.get("limit") ?? "20"));

  // ADMIN can only see their own posts; SUPER_ADMIN sees all
  const authorId =
    session.user.role === "SUPER_ADMIN"
      ? (searchParams.get("authorId") ?? undefined)
      : session.user.id;

  const where = {
    ...(authorId && { authorId }),
    ...(status && { status }),
    ...(type && { type }),
    ...(country && { country }),
    ...(issueCategory && { issueCategory }),
  };

  const [posts, total] = await Promise.all([
    prisma.post.findMany({
      where,
      include: { author: { select: { id: true, name: true, username: true } } },
      orderBy: { createdAt: "desc" },
      skip: (page - 1) * limit,
      take: limit,
    }),
    prisma.post.count({ where }),
  ]);

  return NextResponse.json({
    data: { posts, total, page, limit, totalPages: Math.ceil(total / limit) },
  });
}

export async function POST(req: NextRequest) {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const body = await req.json();
  const { title, content, description, type, country, region, issueCategory, thumbnailUrl, mediaUrl } = body;

  if (!title || !country || !region || !issueCategory) {
    return NextResponse.json(
      { error: "Missing required fields: title, country, region, issueCategory" },
      { status: 400 }
    );
  }

  const post = await prisma.post.create({
    data: {
      title,
      content: content ?? null,
      description: description ?? null,
      type: (type as ContentType) ?? "ARTICLE",
      status: "DRAFT",
      authorId: session.user.id,
      country,
      region,
      issueCategory,
      thumbnailUrl: thumbnailUrl ?? null,
      mediaUrl: mediaUrl ?? null,
    },
    include: {
      author: { select: { id: true, name: true, username: true } },
      approver: { select: { id: true, name: true } },
    },
  });

  return NextResponse.json({ data: post }, { status: 201 });
}
