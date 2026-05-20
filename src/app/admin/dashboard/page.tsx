export const dynamic = "force-dynamic";

import Link from "next/link";
import { requireAuth, isSuperAdmin } from "@/lib/auth-helpers";
import prisma from "@/lib/prisma";
import StatusBadge from "@/components/admin/StatusBadge";
import TypeBadge from "@/components/admin/TypeBadge";
import { formatRelativeTime, formatNumber } from "@/lib/format";

export default async function AdminDashboard() {
  const session = await requireAuth();
  const isSuper = isSuperAdmin(session);
  const postWhere = isSuper ? {} : { authorId: session.user.id };

  const [
    totalPosts,
    publishedPosts,
    pendingPosts,
    draftPosts,
    viewsAgg,
    recentPosts,
    flaggedComments,
  ] = await prisma.$transaction([
    prisma.post.count({ where: postWhere }),
    prisma.post.count({ where: { ...postWhere, status: "PUBLISHED" } }),
    prisma.post.count({ where: { ...postWhere, status: "PENDING" } }),
    prisma.post.count({ where: { ...postWhere, status: "DRAFT" } }),
    prisma.post.aggregate({ where: postWhere, _sum: { viewCount: true, downloadCount: true } }),
    prisma.post.findMany({
      where: postWhere,
      take: 5,
      orderBy: { updatedAt: "desc" },
      include: { author: { select: { name: true, username: true } } },
    }),
    prisma.comment.count({ where: { isFlagged: true } }),
  ]);

  const totalViews = viewsAgg._sum.viewCount ?? 0;
  const totalDownloads = viewsAgg._sum.downloadCount ?? 0;

  const stats = [
    { label: "Published", value: formatNumber(publishedPosts), sub: `of ${totalPosts} total`, color: "indigo" },
    { label: "Pending Approval", value: pendingPosts.toString(), sub: pendingPosts > 0 ? "Action Needed" : "All clear", color: "rose" },
    { label: "Total Views", value: formatNumber(totalViews), sub: `${formatNumber(totalDownloads)} downloads`, color: "sky" },
    { label: "Flagged Comments", value: flaggedComments.toString(), sub: flaggedComments > 0 ? "Needs review" : "All clear", color: "amber" },
  ];

  const typeIcon: Record<string, string> = {
    ARTICLE: "📄", GALLERY_IMAGE: "🖼️", PODCAST: "🎧", VIDEO: "🎬", DOCUMENT: "📁",
  };

  return (
    <div className="flex flex-col gap-10">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="font-outfit text-3xl font-bold text-slate-900">Dashboard Overview</h1>
          <p className="text-slate-500 mt-1">
            Welcome back, <span className="text-primary font-bold">{session?.user?.name ?? session?.user?.username}</span>
            {isSuper && <span className="ml-2 text-[10px] font-black uppercase tracking-widest text-rose-500 bg-rose-50 px-2 py-0.5 rounded-full">Super Admin</span>}
          </p>
        </div>
        <Link href="/admin/content/new" className="btn-primary">
          Create New Content
        </Link>
      </div>

      {/* Stat Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat) => (
          <div key={stat.label} className="glass p-6 rounded-3xl border-white/50 bg-white shadow-sm overflow-hidden relative">
            <div className={`absolute top-0 right-0 w-2 h-full bg-${stat.color}-500 opacity-20`}></div>
            <span className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 block">{stat.label}</span>
            <div className="flex justify-between items-end">
              <span className="text-4xl font-outfit font-black text-slate-900">{stat.value}</span>
              <span className={`text-[10px] font-bold px-2 py-1 rounded-full bg-${stat.color}-50 text-${stat.color}-600`}>
                {stat.sub}
              </span>
            </div>
          </div>
        ))}
      </div>

      {/* Status summary (ADMIN) or content breakdown (SUPER_ADMIN) */}
      {isSuper && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {[
            { label: "Draft", value: draftPosts, color: "slate" },
            { label: "Pending", value: pendingPosts, color: "amber" },
            { label: "Published", value: publishedPosts, color: "emerald" },
            { label: "Total", value: totalPosts, color: "indigo" },
          ].map((item) => (
            <Link
              key={item.label}
              href={`/admin/content${item.label !== "Total" ? `?status=${item.label.toUpperCase()}` : ""}`}
              className={`p-5 rounded-2xl bg-${item.color}-50 border border-${item.color}-100 text-center hover:scale-105 transition-transform`}
            >
              <div className={`text-2xl font-black text-${item.color}-700`}>{item.value}</div>
              <div className={`text-[10px] font-bold uppercase tracking-widest text-${item.color}-500 mt-1`}>{item.label}</div>
            </Link>
          ))}
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Recent Activity */}
        <div className="lg:col-span-2 glass p-8 rounded-4xl border-white/50 bg-white">
          <div className="flex justify-between items-center mb-8">
            <h3 className="font-outfit font-bold text-xl">Recent Activity</h3>
            <Link href="/admin/content" className="text-xs font-bold text-primary hover:underline">
              View All
            </Link>
          </div>
          {recentPosts.length === 0 ? (
            <div className="text-center py-8 text-slate-400">
              <p className="text-sm">No content yet. <Link href="/admin/content/new" className="text-primary font-bold hover:underline">Create your first post.</Link></p>
            </div>
          ) : (
            <div className="space-y-6">
              {recentPosts.map((post) => (
                <div key={post.id} className="flex items-center gap-4 group">
                  <div className="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-lg group-hover:bg-[#1F0404]/5 transition-colors shrink-0">
                    {typeIcon[post.type] ?? "📄"}
                  </div>
                  <div className="grow min-w-0">
                    <h4 className="text-sm font-bold text-slate-800 truncate">{post.title}</h4>
                    <p className="text-[10px] text-slate-400 mt-0.5 uppercase font-bold tracking-tighter">
                      {post.country} &bull; {post.issueCategory} &bull; {formatRelativeTime(post.updatedAt)}
                      {isSuper && ` · ${post.author.name ?? post.author.username}`}
                    </p>
                  </div>
                  <StatusBadge status={post.status as "DRAFT" | "PENDING" | "PUBLISHED" | "REJECTED" | "ARCHIVED"} />
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Quick Info */}
        <div className="glass p-8 rounded-4xl border-white/50 bg-slate-950 text-white flex flex-col justify-between">
          <div>
            <h3 className="font-outfit font-bold text-xl mb-4">Quick Tip</h3>
            <p className="text-slate-400 text-sm leading-relaxed">
              Always ensure your content is tagged with a valid <strong>Region</strong> and{" "}
              <strong>Issue Category</strong> to ensure it appears correctly on the regional heatmap.
            </p>
            {isSuper && pendingPosts > 0 && (
              <div className="mt-6 p-4 bg-amber-500/10 rounded-2xl border border-amber-500/20">
                <p className="text-amber-400 text-xs font-bold">
                  {pendingPosts} submission{pendingPosts !== 1 ? "s" : ""} awaiting your approval
                </p>
                <Link href="/admin/super/approvals" className="text-xs text-amber-300 hover:text-white underline mt-1 block">
                  Go to Approval Queue &rarr;
                </Link>
              </div>
            )}
          </div>
          <div className="mt-12 p-4 bg-white/5 rounded-2xl border border-white/10">
            <h4 className="text-xs font-bold uppercase tracking-widest mb-2 opacity-60">System Health</h4>
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 rounded-full bg-secondary animate-pulse"></div>
              <span className="text-sm font-medium">All systems operational</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
