export const dynamic = "force-dynamic";

import { requireSuperAdmin } from "@/lib/auth-helpers";
import prisma from "@/lib/prisma";
import ApprovalActions from "@/components/admin/ApprovalActions";

export default async function ApprovalsPage() {
  await requireSuperAdmin();

  const pendingPosts = await prisma.post.findMany({
    where: { status: "PENDING" },
    include: { author: { select: { id: true, name: true, username: true } } },
    orderBy: { createdAt: "asc" }, // oldest first — fairness
  });

  const serialized = pendingPosts.map((p) => ({
    id: p.id,
    title: p.title,
    type: p.type,
    country: p.country,
    region: p.region,
    issueCategory: p.issueCategory,
    createdAt: p.createdAt.toISOString(),
    author: p.author,
  }));

  return (
    <div className="flex flex-col gap-10">
      <div className="flex justify-between items-end">
        <div>
          <h1 className="font-outfit text-3xl font-bold">Approval Queue</h1>
          <p className="text-slate-500 mt-1 italic">Vetting editorial submissions for regional publication</p>
        </div>
        <div className="flex items-center gap-3 p-1 glass rounded-xl bg-white/50">
          <span className="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold shadow-lg">
            Pending ({pendingPosts.length})
          </span>
        </div>
      </div>

      <ApprovalActions posts={serialized} />

      <div className="mt-4 p-8 bg-[#1F0404] rounded-[2.5rem] text-white relative overflow-hidden">
        <div className="absolute top-0 right-1/4 w-64 h-64 bg-secondary/10 rounded-full blur-[80px]"></div>
        <div className="relative z-10 flex gap-8 items-start">
          <div className="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center text-2xl shrink-0">⚠️</div>
          <div>
            <h4 className="font-outfit font-bold text-xl mb-2">Social Media Auto-Publishing</h4>
            <p className="text-slate-400 text-sm leading-relaxed max-w-2xl">
              Approving an item will automatically trigger the <strong>Asynchronous Social Queue</strong>.
              Content will be distributed to Facebook, X, and Instagram within minutes of your sign-off.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
