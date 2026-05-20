"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

type Props = {
  postId: string;
  status: string;
  canDelete: boolean; // true if SUPER_ADMIN or (own DRAFT/REJECTED)
};

export default function ContentActions({ postId, status, canDelete }: Props) {
  const router = useRouter();
  const [loading, setLoading] = useState<"submit" | "delete" | null>(null);
  const [error, setError] = useState<string | null>(null);

  const canSubmit = status === "DRAFT" || status === "REJECTED";

  async function handleSubmit() {
    setLoading("submit");
    setError(null);
    try {
      const res = await fetch(`/api/posts/${postId}/submit`, { method: "POST" });
      if (!res.ok) {
        const data = await res.json();
        setError(data.error ?? "Failed to submit");
      } else {
        router.refresh();
      }
    } catch {
      setError("Network error");
    } finally {
      setLoading(null);
    }
  }

  async function handleDelete() {
    if (!confirm("Are you sure you want to delete this post? This cannot be undone.")) return;
    setLoading("delete");
    setError(null);
    try {
      const res = await fetch(`/api/posts/${postId}`, { method: "DELETE" });
      if (!res.ok) {
        const data = await res.json();
        setError(data.error ?? "Failed to delete");
      } else {
        router.refresh();
      }
    } catch {
      setError("Network error");
    } finally {
      setLoading(null);
    }
  }

  return (
    <div className="flex items-center gap-2">
      {error && (
        <span className="text-[10px] text-rose-500 font-bold">{error}</span>
      )}
      {canSubmit && (
        <button
          onClick={handleSubmit}
          disabled={loading === "submit"}
          className="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-700 rounded-lg hover:bg-amber-100 transition-colors disabled:opacity-50"
        >
          {loading === "submit" ? "Submitting..." : "Submit for Review"}
        </button>
      )}
      {canDelete && (
        <button
          onClick={handleDelete}
          disabled={loading === "delete"}
          className="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-100 transition-colors disabled:opacity-50"
        >
          {loading === "delete" ? "Deleting..." : "Delete"}
        </button>
      )}
    </div>
  );
}
