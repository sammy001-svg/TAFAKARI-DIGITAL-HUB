"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

type Comment = {
  id: string;
  content: string;
  name: string | null;
  email: string | null;
  isFlagged: boolean;
  isModerated: boolean;
  createdAt: string;
  post: { id: string; title: string };
};

type Stats = {
  totalComments: number;
  flaggedComments: number;
  moderatedComments: number;
};

export default function ModerationActions({
  comments,
  stats,
}: {
  comments: Comment[];
  stats: Stats;
}) {
  const router = useRouter();
  const [loading, setLoading] = useState<Record<string, "accepting" | "deleting">>({});
  const [errors, setErrors] = useState<Record<string, string>>({});

  async function handleAccept(id: string) {
    setLoading((prev) => ({ ...prev, [id]: "accepting" }));
    setErrors((prev) => ({ ...prev, [id]: "" }));
    try {
      const res = await fetch(`/api/comments/${id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ isModerated: true, isFlagged: false }),
      });
      if (!res.ok) {
        const data = await res.json();
        setErrors((prev) => ({ ...prev, [id]: data.error ?? "Failed" }));
      } else {
        router.refresh();
      }
    } catch {
      setErrors((prev) => ({ ...prev, [id]: "Network error" }));
    } finally {
      setLoading((prev) => { const n = { ...prev }; delete n[id]; return n; });
    }
  }

  async function handleDelete(id: string) {
    if (!confirm("Permanently delete this comment?")) return;
    setLoading((prev) => ({ ...prev, [id]: "deleting" }));
    setErrors((prev) => ({ ...prev, [id]: "" }));
    try {
      const res = await fetch(`/api/comments/${id}`, { method: "DELETE" });
      if (!res.ok) {
        const data = await res.json();
        setErrors((prev) => ({ ...prev, [id]: data.error ?? "Failed" }));
      } else {
        router.refresh();
      }
    } catch {
      setErrors((prev) => ({ ...prev, [id]: "Network error" }));
    } finally {
      setLoading((prev) => { const n = { ...prev }; delete n[id]; return n; });
    }
  }

  return (
    <>
      <div className="glass p-10 rounded-[2.5rem] border-white/50 bg-white">
        <h3 className="font-outfit font-bold text-xl mb-8 flex items-center gap-3 text-rose-600">
          <span className="w-2 h-2 rounded-full bg-rose-600 animate-pulse"></span>
          Flagged for Review ({comments.length})
        </h3>

        {comments.length === 0 ? (
          <div className="text-center py-12">
            <div className="text-4xl mb-3">✅</div>
            <p className="text-slate-500 font-medium">No flagged comments at this time.</p>
          </div>
        ) : (
          <div className="space-y-6">
            {comments.map((comment) => (
              <div
                key={comment.id}
                className="p-8 rounded-3xl bg-slate-50 border border-slate-100 flex flex-col md:flex-row gap-8 items-start relative overflow-hidden"
              >
                <div className="absolute top-0 left-0 w-1.5 h-full bg-rose-500"></div>
                <div className="grow">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-8 h-8 rounded-lg bg-white flex items-center justify-center font-bold text-xs text-slate-400 shadow-sm">
                      {(comment.name ?? "?")[0].toUpperCase()}
                    </div>
                    <div>
                      <span className="text-sm font-bold text-slate-900">{comment.name ?? "Anonymous"}</span>
                      <span className="text-[10px] text-slate-400 ml-2 italic">on &quot;{comment.post.title}&quot;</span>
                    </div>
                  </div>
                  <p className="text-slate-700 text-sm leading-relaxed mb-4 italic">&quot;{comment.content}&quot;</p>
                  {errors[comment.id] && (
                    <p className="text-xs text-rose-500 font-bold">{errors[comment.id]}</p>
                  )}
                </div>
                <div className="flex gap-2 w-full md:w-fit mt-4 md:mt-0 shrink-0">
                  <button
                    onClick={() => handleAccept(comment.id)}
                    disabled={!!loading[comment.id]}
                    className="grow md:flex-none px-6 py-3 bg-white text-primary border border-primary/20 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-primary hover:text-white transition-all shadow-sm disabled:opacity-50"
                  >
                    {loading[comment.id] === "accepting" ? "..." : "Accept"}
                  </button>
                  <button
                    onClick={() => handleDelete(comment.id)}
                    disabled={!!loading[comment.id]}
                    className="grow md:flex-none px-6 py-3 bg-rose-600 text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-lg shadow-rose-500/20 hover:scale-105 transition-all disabled:opacity-50"
                  >
                    {loading[comment.id] === "deleting" ? "..." : "Delete"}
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div className="glass p-8 rounded-4xl border-white/20 bg-slate-50">
          <h4 className="font-outfit font-bold text-lg mb-4">Moderation Stats</h4>
          <div className="space-y-4">
            <div className="flex justify-between items-center text-sm">
              <span className="text-slate-500 font-medium">Total Comments</span>
              <span className="font-black text-slate-900">{stats.totalComments}</span>
            </div>
            <div className="flex justify-between items-center text-sm border-y border-slate-200 py-4">
              <span className="text-slate-500 font-medium">Manually Reviewed</span>
              <span className="font-black text-primary">{stats.moderatedComments}</span>
            </div>
            <div className="flex justify-between items-center text-sm">
              <span className="text-slate-500 font-medium">Currently Flagged</span>
              <span className="font-black text-rose-600">{stats.flaggedComments}</span>
            </div>
          </div>
        </div>

        <div className="glass p-8 rounded-4xl border-white/20 bg-[#1F0404] text-white">
          <h4 className="font-outfit font-bold text-lg mb-4 text-secondary">Moderation AI</h4>
          <p className="text-white/70 text-xs leading-relaxed mb-6">
            Our multilingual AI filter currently supports English, Swahili, Amharic, and French with an accuracy rate of 94.2%.
          </p>
          <button className="w-full bg-primary text-white py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-primary/80 transition-colors shadow-lg shadow-black/20">
            Open API Settings
          </button>
        </div>
      </div>
    </>
  );
}
