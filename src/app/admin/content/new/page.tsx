"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import MarkdownEditor from "@/components/admin/MarkdownEditor";
import { AFRICAN_COUNTRIES, ISSUE_CATEGORIES } from "@/lib/constants";

export default function CreateContentPage() {
  const [step, setStep] = useState(1);
  const router = useRouter();
  const [formData, setFormData] = useState({
    title: "",
    type: "ARTICLE",
    description: "",
    content: "",
    country: "Kenya",
    region: "",
    issueCategory: "Health",
    thumbnailUrl: "",
    mediaUrl: "",
  });
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  function set(field: string, value: string) {
    setFormData((prev) => ({ ...prev, [field]: value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!formData.region.trim()) {
      setError("Region / County is required.");
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      const res = await fetch("/api/posts", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          title: formData.title,
          type: formData.type,
          description: formData.description || null,
          content: formData.content || null,
          country: formData.country,
          region: formData.region,
          issueCategory: formData.issueCategory,
          thumbnailUrl: formData.thumbnailUrl || null,
          mediaUrl: formData.mediaUrl || null,
        }),
      });
      if (!res.ok) {
        const data = await res.json();
        setError(data.error ?? "Submission failed. Please try again.");
        return;
      }
      router.push("/admin/content");
    } catch {
      setError("Network error. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  const inputClass =
    "w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-secondary/50 focus:border-secondary/50 focus:outline-none transition-all font-medium";

  return (
    <div className="max-w-4xl mx-auto">
      <div className="mb-10 flex items-center justify-between">
        <div>
          <h1 className="font-outfit text-3xl font-bold">Create New Content</h1>
          <p className="text-slate-500 mt-1 italic">
            Step {step} of 3:{" "}
            {step === 1 ? "Basic Information" : step === 2 ? "Detailed Content" : "Regional Tagging & Review"}
          </p>
        </div>
        <div className="flex gap-2">
          {[1, 2, 3].map((s) => (
            <div key={s} className={`h-1.5 w-12 rounded-full transition-all ${s <= step ? "bg-primary" : "bg-slate-200"}`} />
          ))}
        </div>
      </div>

      {error && (
        <div className="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl text-sm text-rose-700 font-medium">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="glass p-10 rounded-[2.5rem] border-white/50 bg-white">
        {/* ── Step 1: Basic Information ─────────────────────────── */}
        {step === 1 && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Content Title</label>
              <input
                type="text"
                required
                className={inputClass}
                placeholder="Enter a descriptive title..."
                value={formData.title}
                onChange={(e) => set("title", e.target.value)}
              />
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Content Type</label>
                <select title="Content Type" className={inputClass} value={formData.type} onChange={(e) => set("type", e.target.value)}>
                  <option value="ARTICLE">Article / Post</option>
                  <option value="GALLERY_IMAGE">Gallery Album</option>
                  <option value="PODCAST">Podcast Episode</option>
                  <option value="VIDEO">Video Production</option>
                  <option value="DOCUMENT">Official Document</option>
                </select>
              </div>
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Thumbnail URL</label>
                <input
                  type="url"
                  className={inputClass}
                  placeholder="https://..."
                  value={formData.thumbnailUrl}
                  onChange={(e) => set("thumbnailUrl", e.target.value)}
                />
              </div>
            </div>
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Media URL (audio / video / document)</label>
              <input
                type="url"
                className={inputClass}
                placeholder="https://..."
                value={formData.mediaUrl}
                onChange={(e) => set("mediaUrl", e.target.value)}
              />
            </div>
            <div className="pt-6">
              <button type="button" onClick={() => setStep(2)} className="btn-primary w-full py-4 text-sm">
                Continue to Content &rarr;
              </button>
            </div>
          </div>
        )}

        {/* ── Step 2: Content Body ──────────────────────────────── */}
        {step === 2 && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Short Description</label>
              <textarea
                rows={3}
                className={inputClass}
                placeholder="Give a brief summary for previews..."
                value={formData.description}
                onChange={(e) => set("description", e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">
                Main Content Body
                <span className="ml-2 text-[10px] font-normal normal-case text-slate-400">Markdown supported</span>
              </label>
              <MarkdownEditor
                value={formData.content}
                onChange={(v) => set("content", v)}
                placeholder="Write your article in Markdown. Use **bold**, *italic*, ## headings, - lists, etc."
                minRows={14}
              />
            </div>
            <div className="flex gap-4 pt-6">
              <button type="button" onClick={() => setStep(1)} className="glass py-4 px-8 rounded-full font-bold text-sm">
                Go Back
              </button>
              <button type="button" onClick={() => setStep(3)} className="btn-primary grow py-4 text-sm font-bold">
                Tag Regions & Finish &rarr;
              </button>
            </div>
          </div>
        )}

        {/* ── Step 3: Regional Tagging ──────────────────────────── */}
        {step === 3 && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Target Country</label>
                <select title="Target Country" className={inputClass} value={formData.country} onChange={(e) => set("country", e.target.value)}>
                  {AFRICAN_COUNTRIES.map((c) => <option key={c}>{c}</option>)}
                </select>
              </div>
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Issue Category</label>
                <select title="Issue Category" className={inputClass} value={formData.issueCategory} onChange={(e) => set("issueCategory", e.target.value)}>
                  {ISSUE_CATEGORIES.map((c) => <option key={c}>{c}</option>)}
                </select>
              </div>
            </div>
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Specific Region / County *</label>
              <input
                type="text"
                required
                className={inputClass}
                placeholder="e.g., Nairobi, Addis Ababa, North Kivu..."
                value={formData.region}
                onChange={(e) => set("region", e.target.value)}
              />
            </div>
            <div className="p-6 bg-slate-50 rounded-3xl border border-slate-100 mt-10">
              <h4 className="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Final Submission</h4>
              <p className="text-xs text-slate-500 leading-relaxed mb-6">
                Your content will be saved as a <strong>Draft</strong>. You can then submit it for Super Admin review from your content list.
              </p>
              <div className="flex gap-4">
                <button type="button" onClick={() => setStep(2)} className="glass py-4 px-8 rounded-full font-bold text-sm">
                  Go Back
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  className="grow btn-primary py-4 text-sm font-bold shadow-black/10 disabled:opacity-60"
                >
                  {submitting ? "Saving Draft..." : "Save as Draft"}
                </button>
              </div>
            </div>
          </div>
        )}
      </form>
    </div>
  );
}
