"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import StatusBadge from "@/components/admin/StatusBadge";

type Post = {
  id: string;
  title: string;
  content: string | null;
  description: string | null;
  type: string;
  status: string;
  country: string;
  region: string;
  issueCategory: string;
  thumbnailUrl: string | null;
  mediaUrl: string | null;
};

const COUNTRIES = ["Kenya", "Ethiopia", "DR Congo"];
const CATEGORIES = ["Health", "Education", "Security & Conflict", "Climate & Environment", "Economic Development", "Policy & Governance"];

export default function EditContentForm({ post, canSubmit }: { post: Post; canSubmit: boolean }) {
  const router = useRouter();
  const [step, setStep] = useState(1);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState({
    title: post.title,
    type: post.type,
    description: post.description ?? "",
    content: post.content ?? "",
    country: post.country,
    region: post.region,
    issueCategory: post.issueCategory,
    thumbnailUrl: post.thumbnailUrl ?? "",
    mediaUrl: post.mediaUrl ?? "",
  });

  function set(field: string, value: string) {
    setFormData((prev) => ({ ...prev, [field]: value }));
  }

  async function handleSave(andSubmit = false) {
    setSubmitting(true);
    setError(null);
    try {
      const res = await fetch(`/api/posts/${post.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });
      if (!res.ok) {
        const data = await res.json();
        setError(data.error ?? "Save failed");
        return;
      }
      if (andSubmit) {
        const submitRes = await fetch(`/api/posts/${post.id}/submit`, { method: "POST" });
        if (!submitRes.ok) {
          const data = await submitRes.json();
          setError(data.error ?? "Submit failed");
          return;
        }
      }
      router.push("/admin/content");
    } catch {
      setError("Network error. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  const inputClass =
    "w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-secondary/50 focus:outline-none transition-all font-medium";

  return (
    <div className="max-w-4xl mx-auto">
      <div className="mb-10 flex items-center justify-between">
        <div>
          <div className="flex items-center gap-3 mb-2">
            <h1 className="font-outfit text-3xl font-bold">Edit Content</h1>
            <StatusBadge status={post.status as "DRAFT" | "PENDING" | "PUBLISHED" | "REJECTED" | "ARCHIVED"} />
          </div>
          <p className="text-slate-500 italic">
            Step {step} of 3:{" "}
            {step === 1 ? "Basic Information" : step === 2 ? "Detailed Content" : "Regional Tagging & Review"}
          </p>
        </div>
        <div className="flex gap-2">
          {[1, 2, 3].map((s) => (
            <div
              key={s}
              className={`h-1.5 w-12 rounded-full transition-all ${s <= step ? "bg-primary" : "bg-slate-200"}`}
            />
          ))}
        </div>
      </div>

      {post.status === "REJECTED" && (
        <div className="mb-8 p-6 bg-rose-50 border border-rose-200 rounded-3xl">
          <p className="text-xs font-black uppercase tracking-widest text-rose-600 mb-1">Rejected</p>
          <p className="text-sm text-rose-700">Make your edits and re-submit for review.</p>
        </div>
      )}

      {error && (
        <div className="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl text-sm text-rose-700 font-medium">
          {error}
        </div>
      )}

      <div className="glass p-10 rounded-[2.5rem] border-white/50 bg-white">
        {step === 1 && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Content Title</label>
              <input type="text" required className={inputClass} value={formData.title} onChange={(e) => set("title", e.target.value)} />
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Content Type</label>
                <select className={inputClass} value={formData.type} onChange={(e) => set("type", e.target.value)}>
                  <option value="ARTICLE">Article / Post</option>
                  <option value="GALLERY_IMAGE">Gallery Album</option>
                  <option value="PODCAST">Podcast Episode</option>
                  <option value="VIDEO">Video Production</option>
                  <option value="DOCUMENT">Official Document</option>
                </select>
              </div>
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Thumbnail URL</label>
                <input type="url" className={inputClass} placeholder="https://..." value={formData.thumbnailUrl} onChange={(e) => set("thumbnailUrl", e.target.value)} />
              </div>
            </div>
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Media URL (audio / video / image)</label>
              <input type="url" className={inputClass} placeholder="https://..." value={formData.mediaUrl} onChange={(e) => set("mediaUrl", e.target.value)} />
            </div>
            <div className="pt-6">
              <button type="button" onClick={() => setStep(2)} className="btn-primary w-full py-4 text-sm">
                Continue to Content &rarr;
              </button>
            </div>
          </div>
        )}

        {step === 2 && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Short Description</label>
              <textarea rows={3} className={inputClass} placeholder="Brief summary for previews..." value={formData.description} onChange={(e) => set("description", e.target.value)} />
            </div>
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Main Content Body</label>
              <textarea rows={10} className={`${inputClass} font-inter`} placeholder="Write your full content here..." value={formData.content} onChange={(e) => set("content", e.target.value)} />
            </div>
            <div className="flex gap-4 pt-6">
              <button type="button" onClick={() => setStep(1)} className="glass py-4 px-8 rounded-full font-bold text-sm">Go Back</button>
              <button type="button" onClick={() => setStep(3)} className="btn-primary grow py-4 text-sm font-bold">Tag Regions & Finish &rarr;</button>
            </div>
          </div>
        )}

        {step === 3 && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Target Country</label>
                <select className={inputClass} value={formData.country} onChange={(e) => set("country", e.target.value)}>
                  {COUNTRIES.map((c) => <option key={c}>{c}</option>)}
                </select>
              </div>
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Issue Category</label>
                <select className={inputClass} value={formData.issueCategory} onChange={(e) => set("issueCategory", e.target.value)}>
                  {CATEGORIES.map((c) => <option key={c}>{c}</option>)}
                </select>
              </div>
            </div>
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Specific Region / County</label>
              <input type="text" required className={inputClass} placeholder="e.g., Nairobi, Addis Ababa, North Kivu..." value={formData.region} onChange={(e) => set("region", e.target.value)} />
            </div>

            <div className="p-6 bg-slate-50 rounded-3xl border border-slate-100 mt-4 space-y-4">
              <h4 className="text-xs font-black uppercase tracking-widest text-slate-400">Save Options</h4>
              <div className="flex flex-col sm:flex-row gap-3">
                <button type="button" onClick={() => setStep(2)} className="glass py-4 px-8 rounded-full font-bold text-sm shrink-0">
                  Go Back
                </button>
                <button
                  type="button"
                  onClick={() => handleSave(false)}
                  disabled={submitting}
                  className="grow py-4 rounded-full border-2 border-primary text-primary font-bold text-sm hover:bg-primary hover:text-white transition-colors disabled:opacity-50"
                >
                  {submitting ? "Saving..." : "Save as Draft"}
                </button>
                {canSubmit && (
                  <button
                    type="button"
                    onClick={() => handleSave(true)}
                    disabled={submitting}
                    className="grow btn-primary py-4 text-sm font-bold shadow-black/10 disabled:opacity-50"
                  >
                    {submitting ? "Submitting..." : "Save & Submit for Approval"}
                  </button>
                )}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
