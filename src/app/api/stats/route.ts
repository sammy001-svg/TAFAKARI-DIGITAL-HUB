import { NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import prisma from "@/lib/prisma";

export const dynamic = "force-dynamic";

export async function GET() {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const isSuper = session.user.role === "SUPER_ADMIN";
  const postWhere = isSuper ? {} : { authorId: session.user.id };

  const [
    totalPosts,
    publishedPosts,
    pendingPosts,
    draftPosts,
    rejectedPosts,
    archivedPosts,
    viewsAgg,
    recentPosts,
    totalComments,
    flaggedComments,
    totalUsers,
  ] = await prisma.$transaction([
    prisma.post.count({ where: postWhere }),
    prisma.post.count({ where: { ...postWhere, status: "PUBLISHED" } }),
    prisma.post.count({ where: { ...postWhere, status: "PENDING" } }),
    prisma.post.count({ where: { ...postWhere, status: "DRAFT" } }),
    prisma.post.count({ where: { ...postWhere, status: "REJECTED" } }),
    prisma.post.count({ where: { ...postWhere, status: "ARCHIVED" } }),
    prisma.post.aggregate({
      where: postWhere,
      _sum: { viewCount: true, downloadCount: true },
    }),
    prisma.post.findMany({
      where: postWhere,
      take: 5,
      orderBy: { createdAt: "desc" },
      include: { author: { select: { id: true, name: true, username: true } } },
    }),
    isSuper ? prisma.comment.count() : prisma.comment.count({ where: { post: { authorId: session.user.id } } }),
    isSuper ? prisma.comment.count({ where: { isFlagged: true } }) : prisma.comment.count({ where: { isFlagged: true, post: { authorId: session.user.id } } }),
    isSuper ? prisma.user.count() : prisma.user.count({ where: { id: session.user.id } }),
  ]);

  return NextResponse.json({
    data: {
      totalPosts,
      publishedPosts,
      pendingPosts,
      draftPosts,
      rejectedPosts,
      archivedPosts,
      totalViews: viewsAgg._sum.viewCount ?? 0,
      totalDownloads: viewsAgg._sum.downloadCount ?? 0,
      totalComments,
      flaggedComments,
      totalUsers,
      recentPosts,
    },
  });
}
