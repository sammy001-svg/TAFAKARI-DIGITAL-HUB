export const dynamic = "force-dynamic";

import { requireSuperAdmin } from "@/lib/auth-helpers";
import prisma from "@/lib/prisma";
import ModerationActions from "@/components/admin/ModerationActions";

export default async function CommentModerationPage() {
  await requireSuperAdmin();

  const [flaggedComments, totalComments, moderatedComments] = await prisma.$transaction([
    prisma.comment.findMany({
      where: { isFlagged: true, isModerated: false },
      include: { post: { select: { id: true, title: true } } },
      orderBy: { createdAt: "asc" },
    }),
    prisma.comment.count(),
    prisma.comment.count({ where: { isModerated: true } }),
  ]);

  const serialized = flaggedComments.map((c) => ({
    id: c.id,
    content: c.content,
    name: c.name,
    email: c.email,
    isFlagged: c.isFlagged,
    isModerated: c.isModerated,
    createdAt: c.createdAt.toISOString(),
    post: c.post,
  }));

  return (
    <div className="flex flex-col gap-10">
      <div className="flex justify-between items-end">
        <div>
          <h1 className="font-outfit text-3xl font-bold">Comment Moderation</h1>
          <p className="text-slate-500 mt-1 italic">Managing community engagement and safety filters</p>
        </div>
        <div className="flex flex-col items-end">
          <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-2">Auto-Moderation</span>
          <div className="flex items-center gap-2 bg-secondary/15 text-secondary px-3 py-1.5 rounded-full text-[10px] font-black uppercase ring-4 ring-secondary/5">
            Active
          </div>
        </div>
      </div>

      <ModerationActions
        comments={serialized}
        stats={{
          totalComments,
          flaggedComments: flaggedComments.length,
          moderatedComments,
        }}
      />
    </div>
  );
}
