"use client";

import { useState } from "react";

export default function CommentForm({ postId }: { postId: string }) {
  const [form, setForm] = useState({ name: "", email: "", content: "" });
  const [status, setStatus] = useState<"idle" | "submitting" | "success" | "error">("idle");
  const [error, setError] = useState<string | null>(null);

  function set(field: string, value: string) {
    setForm((prev) => ({ ...prev, [field]: value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!form.content.trim()) return;
    setStatus("submitting");
    setError(null);
    try {
      const res = await fetch("/api/comments", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...form, postId }),
      });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        setError(data.error ?? "Submission failed. Please try again.");
        setStatus("error");
        return;
      }
      setStatus("success");
      setForm({ name: "", email: "", content: "" });
    } catch {
      setError("Network error. Please try again.");
      setStatus("error");
    }
  }

  const inputClass =
    "w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 text-sm font-medium focus:ring-2 focus:ring-secondary/30 focus:outline-none transition-all";

  if (status === "success") {
    return (
      <div className="p-6 bg-green-50 border border-green-200 rounded-3xl text-green-700 font-medium text-sm">
        Thank you! Your comment has been submitted and will appear after moderation.
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input
          type="text"
          placeholder="Your name"
          value={form.name}
          onChange={(e) => set("name", e.target.value)}
          className={inputClass}
        />
        <input
          type="email"
          placeholder="Email address (optional)"
          value={form.email}
          onChange={(e) => set("email", e.target.value)}
          className={inputClass}
        />
      </div>
      <textarea
        required
        rows={4}
        placeholder="Share your thoughts on this article..."
        value={form.content}
        onChange={(e) => set("content", e.target.value)}
        className={`${inputClass} resize-none`}
      />
      {error && <p className="text-sm text-rose-600 font-medium">{error}</p>}
      <div className="flex justify-end">
        <button
          type="submit"
          disabled={status === "submitting"}
          className="btn-primary px-8 py-3 text-sm font-bold disabled:opacity-60"
        >
          {status === "submitting" ? "Submitting..." : "Post Comment"}
        </button>
      </div>
    </form>
  );
}
