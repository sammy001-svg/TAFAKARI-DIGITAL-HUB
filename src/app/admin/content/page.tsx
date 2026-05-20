export const dynamic = "force-dynamic";

import Link from "next/link";
import { requireAuth, isSuperAdmin } from "@/lib/auth-helpers";
import prisma from "@/lib/prisma";
import StatusBadge from "@/components/admin/StatusBadge";
import TypeBadge from "@/components/admin/TypeBadge";
import ContentActions from "@/components/admin/ContentActions";
import { formatRelativeTime } from "@/lib/format";
import { ContentStatus } from "@prisma/client";

const STATUS_TABS: { label: string; value: ContentStatus | "ALL" }[] = [
  { label: "All", value: "ALL" },
  { label: "Draft", value: "DRAFT" },
  { label: "Pending", value: "PENDING" },
  { label: "Published", value: "PUBLISHED" },
  { label: "Rejected", value: "REJECTED" },
  { label: "Archived", value: "ARCHIVED" },
];

export default async function ContentListPage({
  searchParams,
}: {
  searchParams: Promise<{ status?: string }>;
}) {
  const session = await requireAuth();
  const isSuper = isSuperAdmin(session);
  const { status: statusParam } = await searchParams;
  const statusFilter = statusParam && statusParam !== "ALL" ? (statusParam as ContentStatus) : undefined;

  const where = {
    ...(isSuper ? {} : { authorId: session.user.id }),
    ...(statusFilter && { status: statusFilter }),
  };

  const posts = await prisma.post.findMany({
    where,
    include: { author: { select: { id: true, name: true, username: true } } },
    orderBy: { createdAt: "desc" },
  });

  return (
    <div className="flex flex-col gap-8">
      <div className="flex justify-between items-start">
        <div>
          <h1 className="font-outfit text-3xl font-bold text-slate-900">
            {isSuper ? "All Content" : "My Content"}
          </h1>
          <p className="text-slate-500 mt-1 italic">
            {posts.length} item{posts.length !== 1 ? "s" : ""} {statusFilter ? `with status: ${statusFilter}` : "total"}
          </p>
        </div>
        <Link href="/admin/content/new" className="btn-primary px-6 py-3 text-sm">
          + Create New
        </Link>
      </div>

      {/* Status tabs */}
      <div className="flex gap-1 p-1 glass rounded-xl bg-white/50 w-fit flex-wrap">
        {STATUS_TABS.map((tab) => (
          <Link
            key={tab.value}
            href={tab.value === "ALL" ? "/admin/content" : `/admin/content?status=${tab.value}`}
            className={`px-4 py-2 rounded-lg text-xs font-bold transition-colors ${
              (statusParam ?? "ALL") === tab.value
                ? "bg-primary text-white shadow-lg"
                : "text-slate-500 hover:text-slate-900 hover:bg-white"
            }`}
          >
            {tab.label}
          </Link>
        ))}
      </div>

      {posts.length === 0 ? (
        <div className="glass p-16 rounded-[2.5rem] border-white/50 bg-white text-center">
          <div className="text-4xl mb-4">📭</div>
          <h3 className="font-outfit font-bold text-xl text-slate-900">No content found</h3>
          <p className="text-slate-500 mt-2 text-sm">
            {statusFilter ? `No ${statusFilter.toLowerCase()} posts.` : "Start by creating new content."}
          </p>
          <Link href="/admin/content/new" className="btn-primary inline-block mt-6 px-6 py-3 text-sm">
            Create Content
          </Link>
        </div>
      ) : (
        <div className="glass rounded-[2.5rem] border-white/50 bg-white overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-slate-100">
                <th className="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Content</th>
                <th className="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden md:table-cell">Status</th>
                <th className="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden lg:table-cell">Region</th>
                <th className="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden lg:table-cell">Updated</th>
                <th className="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50">
              {posts.map((post) => {
                const isOwner = post.authorId === session.user.id;
                const canDelete =
                  isSuper ||
                  (isOwner && (post.status === "DRAFT" || post.status === "REJECTED"));

                return (
                  <tr key={post.id} className="hover:bg-slate-50/50 transition-colors group">
                    <td className="px-8 py-5">
                      <div className="flex flex-col gap-1">
                        <TypeBadge type={post.type as "ARTICLE" | "GALLERY_IMAGE" | "PODCAST" | "VIDEO" | "DOCUMENT"} />
                        <p className="text-sm font-bold text-slate-900 line-clamp-1">{post.title}</p>
                        {isSuper && (
                          <p className="text-[10px] text-slate-400">
                            by {post.author.name ?? post.author.username}
                          </p>
                        )}
                      </div>
                    </td>
                    <td className="px-8 py-5 hidden md:table-cell">
                      <StatusBadge status={post.status as "DRAFT" | "PENDING" | "PUBLISHED" | "REJECTED" | "ARCHIVED"} />
                    </td>
                    <td className="px-8 py-5 hidden lg:table-cell">
                      <div className="text-xs text-slate-500">
                        <span className="font-bold text-slate-700">{post.country}</span>
                        <span className="mx-1">·</span>
                        {post.region}
                      </div>
                    </td>
                    <td className="px-8 py-5 text-xs text-slate-400 hidden lg:table-cell">
                      {formatRelativeTime(post.updatedAt)}
                    </td>
                    <td className="px-8 py-5">
                      <div className="flex items-center justify-end gap-2">
                        <Link
                          href={`/admin/content/${post.id}/edit`}
                          className="px-4 py-2 text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-colors"
                        >
                          Edit
                        </Link>
                        <ContentActions
                          postId={post.id}
                          status={post.status}
                          canDelete={canDelete}
                        />
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
