"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";

type Post = {
  id: string;
  title: string;
  type: string;
  country: string;
  region: string;
  issueCategory: string;
  createdAt: string;
  author: { id: string; name: string | null; username: string | null };
};

export default function ApprovalActions({ posts }: { posts: Post[] }) {
  const router = useRouter();
  const [loading, setLoading] = useState<Record<string, "approving" | "rejecting">>({});
  const [rejectingId, setRejectingId] = useState<string | null>(null);
  const [rejectionNotes, setRejectionNotes] = useState("");
  const [errors, setErrors] = useState<Record<string, string>>({});

  async function handleApprove(id: string) {
    setLoading((prev) => ({ ...prev, [id]: "approving" }));
    setErrors((prev) => ({ ...prev, [id]: "" }));
    try {
      const res = await fetch(`/api/posts/${id}/approve`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "APPROVE" }),
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

  async function handleReject(id: string) {
    if (!rejectionNotes.trim()) return;
    setLoading((prev) => ({ ...prev, [id]: "rejecting" }));
    setErrors((prev) => ({ ...prev, [id]: "" }));
    try {
      const res = await fetch(`/api/posts/${id}/approve`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "REJECT", rejectionNotes }),
      });
      if (!res.ok) {
        const data = await res.json();
        setErrors((prev) => ({ ...prev, [id]: data.error ?? "Failed" }));
      } else {
        setRejectingId(null);
        setRejectionNotes("");
        router.refresh();
      }
    } catch {
      setErrors((prev) => ({ ...prev, [id]: "Network error" }));
    } finally {
      setLoading((prev) => { const n = { ...prev }; delete n[id]; return n; });
    }
  }

  const typeIcon: Record<string, string> = {
    ARTICLE: "📄", GALLERY_IMAGE: "🖼️", PODCAST: "🎧", VIDEO: "🎬", DOCUMENT: "📁",
  };

  if (posts.length === 0) {
    return (
      <div className="glass p-16 rounded-4xl border-white/50 bg-white text-center">
        <div className="text-4xl mb-4">✅</div>
        <h3 className="font-outfit font-bold text-xl text-slate-900">Queue is clear</h3>
        <p className="text-slate-500 mt-2 text-sm">No pending submissions at this time.</p>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 gap-6">
      {posts.map((item) => (
        <div key={item.id} className="glass p-8 rounded-4xl border-white/50 bg-white flex flex-col gap-6">
          <div className="flex flex-col lg:flex-row items-start lg:items-center gap-6">
            <div className="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-2xl shadow-inner shrink-0">
              {typeIcon[item.type] ?? "📄"}
            </div>
            <div className="grow">
              <div className="flex items-center gap-2 mb-1">
                <span className="text-[10px] font-bold uppercase tracking-widest text-secondary">{item.type.replace("_", " ")}</span>
                <span className="w-1 h-1 rounded-full bg-slate-300"></span>
                <span className="text-[10px] font-bold uppercase tracking-widest text-slate-400">{item.country}</span>
                <span className="w-1 h-1 rounded-full bg-slate-300"></span>
                <span className="text-[10px] font-bold uppercase tracking-widest text-slate-400">{item.issueCategory}</span>
              </div>
              <h3 className="font-outfit font-bold text-xl text-slate-900 mb-1">{item.title}</h3>
              <p className="text-xs text-slate-400 font-medium italic">
                Submitted by {item.author.name ?? item.author.username} &bull; {item.region}
              </p>
            </div>
            <div className="flex items-center gap-3 bg-slate-50/50 p-3 rounded-3xl border border-slate-100 shrink-0">
              <Link
                href={`/admin/content/${item.id}/edit`}
                className="px-5 py-3 rounded-xl hover:bg-slate-100 transition-colors text-xs font-bold uppercase tracking-widest text-slate-600"
              >
                Review
              </Link>
              <button
                onClick={() => handleApprove(item.id)}
                disabled={!!loading[item.id]}
                className="px-5 py-3 rounded-xl bg-primary text-white shadow-lg shadow-black/10 hover:scale-105 transition-all text-xs font-bold uppercase tracking-widest disabled:opacity-60"
              >
                {loading[item.id] === "approving" ? "Approving..." : "Approve"}
              </button>
              <button
                onClick={() => setRejectingId(rejectingId === item.id ? null : item.id)}
                className="w-12 h-12 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center font-bold"
              >
                ✕
              </button>
            </div>
          </div>

          {errors[item.id] && (
            <p className="text-xs text-rose-500 font-bold px-2">{errors[item.id]}</p>
          )}

          {rejectingId === item.id && (
            <div className="bg-rose-50 rounded-2xl p-6 border border-rose-100 space-y-4 animate-in fade-in slide-in-from-top-2 duration-200">
              <label className="text-xs font-black uppercase tracking-widest text-rose-600">
                Rejection Notes (required)
              </label>
              <textarea
                rows={3}
                placeholder="Explain why this submission is being rejected..."
                value={rejectionNotes}
                onChange={(e) => setRejectionNotes(e.target.value)}
                className="w-full bg-white border border-rose-200 rounded-xl px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-rose-300 resize-none"
              />
              <div className="flex gap-3">
                <button
                  onClick={() => { setRejectingId(null); setRejectionNotes(""); }}
                  className="px-5 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  onClick={() => handleReject(item.id)}
                  disabled={!rejectionNotes.trim() || !!loading[item.id]}
                  className="px-5 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold uppercase tracking-widest shadow-lg disabled:opacity-50 hover:bg-rose-700 transition-colors"
                >
                  {loading[item.id] === "rejecting" ? "Rejecting..." : "Confirm Rejection"}
                </button>
              </div>
            </div>
          )}
        </div>
      ))}
    </div>
  );
}
