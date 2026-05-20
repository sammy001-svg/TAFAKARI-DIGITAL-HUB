import { NextRequest, NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import prisma from "@/lib/prisma";

export const dynamic = "force-dynamic";

type RouteContext = { params: Promise<{ id: string }> };

export async function POST(req: NextRequest, ctx: RouteContext) {
  const session = await getServerSession(authOptions);
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  if (session.user.role !== "SUPER_ADMIN")
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const { id } = await ctx.params;
  const post = await prisma.post.findUnique({ where: { id } });
  if (!post) return NextResponse.json({ error: "Not found" }, { status: 404 });

  if (post.status !== "PENDING") {
    return NextResponse.json(
      { error: "Post is not in PENDING status" },
      { status: 409 }
    );
  }

  const body = await req.json();
  const { action, rejectionNotes } = body;

  if (action !== "APPROVE" && action !== "REJECT") {
    return NextResponse.json({ error: "action must be APPROVE or REJECT" }, { status: 400 });
  }

  if (action === "REJECT" && !rejectionNotes?.trim()) {
    return NextResponse.json(
      { error: "rejectionNotes is required when rejecting" },
      { status: 400 }
    );
  }

  const updated = await prisma.post.update({
    where: { id },
    data:
      action === "APPROVE"
        ? { status: "PUBLISHED", approverId: session.user.id, rejectionNotes: null }
        : { status: "REJECTED", approverId: session.user.id, rejectionNotes },
    include: {
      author: { select: { id: true, name: true, username: true } },
      approver: { select: { id: true, name: true } },
    },
  });

  return NextResponse.json({ data: updated });
}
