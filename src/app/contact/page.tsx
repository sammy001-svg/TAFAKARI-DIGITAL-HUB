"use client";

import { useState } from "react";

type Status = "idle" | "submitting" | "success" | "error";

export default function ContactPage() {
  const [status, setStatus] = useState<Status>("idle");
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const el = (name: string) => form.elements.namedItem(name) as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

    const payload = {
      fullName: el("fullName").value.trim(),
      email: el("emailAddr").value.trim(),
      country: el("countrySelect").value,
      interest: el("interestSelect").value,
      message: el("messageText").value.trim(),
    };

    if (!payload.fullName || !payload.email || !payload.message) return;

    setStatus("submitting");
    setError(null);

    try {
      const res = await fetch("/api/contact", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        setError(data.error ?? "Submission failed. Please try again.");
        setStatus("error");
        return;
      }
      setStatus("success");
    } catch {
      setError("Network error. Please try again.");
      setStatus("error");
    }
  }

  return (
    <div className="max-w-7xl mx-auto px-6 py-20">
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
        <div className="flex flex-col gap-8">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/10 text-secondary text-sm font-semibold w-fit border border-secondary/20">
            <span className="flex h-2 w-2 rounded-full bg-secondary animate-pulse"></span>
            Get in Touch
          </div>
          <h1 className="font-outfit text-5xl md:text-6xl font-bold leading-tight">
            Connect with the <br />
            <span className="text-primary">Tafakari Team</span>
          </h1>
          <p className="text-lg text-slate-600 leading-relaxed max-w-lg">
            Whether you are a researcher, community leader, or concerned citizen, we want to hear from you.
            Your insights help shape our regional knowledge repository.
          </p>

          <div className="flex flex-col gap-6 mt-4">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 glass border-secondary/20 rounded-2xl flex items-center justify-center text-xl shadow-sm">📍</div>
              <div>
                <h4 className="font-bold text-slate-900 dark:text-white uppercase tracking-tight">Regional HQ</h4>
                <p className="text-sm text-slate-500 italic">Nairobi, Kenya</p>
              </div>
            </div>
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 glass border-secondary/20 rounded-2xl flex items-center justify-center text-xl shadow-sm">📧</div>
              <div>
                <h4 className="font-bold text-slate-900 dark:text-white uppercase tracking-tight">Email Analysis</h4>
                <p className="text-sm text-slate-500 italic">contact@tafakarihub.org</p>
              </div>
            </div>
          </div>
        </div>

        <div>
          {status === "success" ? (
            <div className="glass p-12 rounded-[3rem] border-secondary/20 text-center animate-in zoom-in duration-500 text-white">
              <div className="w-20 h-20 bg-white text-primary rounded-full flex items-center justify-center text-4xl mx-auto mb-6 shadow-xl border-4 border-secondary/20">✓</div>
              <h2 className="font-outfit text-3xl font-bold mb-4 text-white uppercase tracking-tight">Information Received</h2>
              <p className="text-white/70 mb-8 italic leading-relaxed">
                Thank you for reaching out. A member of our editorial team will review your message and respond within 48 hours.
              </p>
              <button
                type="button"
                onClick={() => setStatus("idle")}
                className="bg-white text-primary px-8 py-3 rounded-xl font-bold hover:bg-slate-50 transition-all shadow-xl"
              >
                Send another message
              </button>
            </div>
          ) : (
            <form
              onSubmit={handleSubmit}
              className="glass p-10 rounded-[3rem] border-secondary/20 bg-primary/50 shadow-2xl space-y-6 text-white"
            >
              {error && (
                <div className="p-4 bg-rose-500/20 border border-rose-400/30 rounded-2xl text-rose-300 text-sm font-medium">
                  {error}
                </div>
              )}

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <label htmlFor="fullName" className="text-xs font-bold uppercase tracking-widest text-secondary/80 ml-1">
                    Full Name
                  </label>
                  <input
                    id="fullName"
                    name="fullName"
                    type="text"
                    required
                    className="w-full bg-white/10 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-white transition-all font-medium placeholder:text-white/20 focus:outline-none"
                    placeholder="Jane Doe"
                  />
                </div>
                <div className="space-y-2">
                  <label htmlFor="emailAddr" className="text-xs font-bold uppercase tracking-widest text-secondary/80 ml-1">
                    Email Address
                  </label>
                  <input
                    id="emailAddr"
                    name="emailAddr"
                    type="email"
                    required
                    className="w-full bg-white/10 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-white transition-all font-medium placeholder:text-white/20 focus:outline-none"
                    placeholder="jane@example.com"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <label htmlFor="countrySelect" className="text-xs font-bold uppercase tracking-widest text-secondary/80 ml-1">
                    Country
                  </label>
                  <select
                    id="countrySelect"
                    name="countrySelect"
                    className="w-full bg-white/10 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-white transition-all font-medium appearance-none focus:outline-none"
                  >
                    <option className="bg-[#4A0809]">Kenya</option>
                    <option className="bg-[#4A0809]">Ethiopia</option>
                    <option className="bg-[#4A0809]">DR Congo</option>
                    <option className="bg-[#4A0809]">Other</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <label htmlFor="interestSelect" className="text-xs font-bold uppercase tracking-widest text-secondary/80 ml-1">
                    Area of Interest
                  </label>
                  <select
                    id="interestSelect"
                    name="interestSelect"
                    className="w-full bg-white/10 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-white transition-all font-medium appearance-none focus:outline-none"
                  >
                    <option className="bg-[#4A0809]">Health</option>
                    <option className="bg-[#4A0809]">Security</option>
                    <option className="bg-[#4A0809]">Policy</option>
                    <option className="bg-[#4A0809]">Media / Press</option>
                    <option className="bg-[#4A0809]">Other</option>
                  </select>
                </div>
              </div>

              <div className="space-y-2">
                <label htmlFor="messageText" className="text-xs font-bold uppercase tracking-widest text-secondary/80 ml-1">
                  Message
                </label>
                <textarea
                  id="messageText"
                  name="messageText"
                  required
                  rows={4}
                  className="w-full bg-white/10 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-white transition-all font-medium placeholder:text-white/20 focus:outline-none resize-none"
                  placeholder="How can we help or collaborate?"
                />
              </div>

              <div className="pt-4">
                <p className="text-[10px] text-white/50 mb-6 italic">
                  By submitting, you agree to our GDPR-aligned data protection policy. We never share your information with third parties.
                </p>
                <button
                  type="submit"
                  disabled={status === "submitting"}
                  className="w-full bg-white text-primary py-4 rounded-2xl text-sm font-bold shadow-xl hover:bg-slate-50 transition-all uppercase tracking-widest disabled:opacity-60"
                >
                  {status === "submitting" ? "Sending..." : "Submit Information"}
                </button>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
