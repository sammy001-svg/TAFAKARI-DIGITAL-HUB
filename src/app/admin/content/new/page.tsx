"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

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
    thumbnail: null,
  });

  const nextStep = () => setStep(step + 1);
  const prevStep = () => setStep(step - 1);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Simulate submission
    alert("Content submitted for approval!");
    router.push("/admin/dashboard");
  };

  return (
    <div className="max-w-4xl mx-auto">
      <div className="mb-10 flex items-center justify-between">
        <div>
          <h1 className="font-outfit text-3xl font-bold">Create New Content</h1>
          <p className="text-slate-500 mt-1 italic">Step {step} of 3: {step === 1 ? "Basic Information" : step === 2 ? "Detailed Content" : "Regional Tagging & Review"}</p>
        </div>
        <div className="flex gap-2">
          {[1, 2, 3].map((s) => (
            <div key={s} className={`h-1.5 w-12 rounded-full transition-all ${s <= step ? "bg-indigo-600" : "bg-slate-200"}`}></div>
          ))}
        </div>
      </div>

      <form onSubmit={handleSubmit} className="glass p-10 rounded-[2.5rem] border-white/50 bg-white">
        {step === 1 && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Content Title</label>
              <input 
                type="text" 
                required
                className="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-indigo-500 transition-all font-medium"
                placeholder="Enter a descriptive title..."
                value={formData.title}
                onChange={(e) => setFormData({...formData, title: e.target.value})}
              />
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Content Type</label>
                <select 
                  className="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-indigo-500 transition-all font-medium"
                  value={formData.type}
                  onChange={(e) => setFormData({...formData, type: e.target.value})}
                >
                  <option value="ARTICLE">Article / Post</option>
                  <option value="GALLERY_IMAGE">Gallery Album</option>
                  <option value="PODCAST">Podcast Episode</option>
                  <option value="VIDEO">Video Production</option>
                  <option value="DOCUMENT">Official Document</option>
                </select>
              </div>
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Thumbnail Preview</label>
                <div className="w-full h-14 bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl flex items-center justify-center text-xs text-slate-400 font-bold uppercase tracking-widest cursor-pointer hover:bg-slate-100 transition-colors">
                  Choose File
                </div>
              </div>
            </div>

            <div className="pt-6">
              <button type="button" onClick={nextStep} className="btn-primary w-full py-4 text-sm">Continue to Content &rarr;</button>
            </div>
          </div>
        )}

        {step === 2 && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
             <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Short Description</label>
              <textarea 
                rows={3}
                className="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-indigo-500 transition-all font-medium"
                placeholder="Give a brief summary for previews..."
                value={formData.description}
                onChange={(e) => setFormData({...formData, description: e.target.value})}
              ></textarea>
            </div>

            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Main Content Body</label>
              <textarea 
                rows={8}
                className="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-indigo-500 transition-all font-medium font-inter"
                placeholder="Write your article or add media transcript here..."
                value={formData.content}
                onChange={(e) => setFormData({...formData, content: e.target.value})}
              ></textarea>
            </div>

            <div className="flex gap-4 pt-6">
              <button type="button" onClick={prevStep} className="glass py-4 px-8 rounded-full font-bold text-sm">Go Back</button>
              <button type="button" onClick={nextStep} className="btn-primary flex-grow py-4 text-sm font-bold">Tag Regions & Finish &rarr;</button>
            </div>
          </div>
        )}

        {step === 3 && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
               <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Target Country</label>
                <select 
                  className="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-indigo-500 transition-all font-medium"
                  value={formData.country}
                  onChange={(e) => setFormData({...formData, country: e.target.value})}
                >
                  <option>Kenya</option>
                  <option>Ethiopia</option>
                  <option>DR Congo</option>
                </select>
              </div>
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Issue Category</label>
                <select 
                  className="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-indigo-500 transition-all font-medium"
                  value={formData.issueCategory}
                  onChange={(e) => setFormData({...formData, issueCategory: e.target.value})}
                >
                  <option>Health</option>
                  <option>Education</option>
                  <option>Security & Conflict</option>
                  <option>Climate & Environment</option>
                  <option>Economic Development</option>
                  <option>Policy & Governance</option>
                </select>
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Specific Region / County</label>
              <input 
                type="text" 
                className="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-indigo-500 transition-all font-medium"
                placeholder="e.g., Nairobi, Addis Ababa, North Kivu..."
                value={formData.region}
                onChange={(e) => setFormData({...formData, region: e.target.value})}
              />
            </div>

            <div className="p-6 bg-slate-50 rounded-3xl border border-slate-100 mt-10">
               <h4 className="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Final Submission Polish</h4>
               <p className="text-xs text-slate-500 leading-relaxed mb-6">
                Your content will enter the <strong>Super Admin Queue</strong>. You will receive an dashboard alert once your submission is either published or returned for revision. 
               </p>
               <div className="flex gap-4">
                  <button type="button" onClick={prevStep} className="glass py-4 px-8 rounded-full font-bold text-sm">Go Back</button>
                  <button type="submit" className="flex-grow btn-primary py-4 text-sm font-bold shadow-indigo-500/40">Submit for Approval</button>
               </div>
            </div>
          </div>
        )}
      </form>
    </div>
  );
}
