"use client";

import { useState } from "react";

export default function ContactPage() {
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitted(true);
  };

  return (
    <div className="max-w-7xl mx-auto px-6 py-20">
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
        <div className="flex flex-col gap-8">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100/10 text-emerald-400 text-sm font-semibold w-fit border border-emerald-500/20">
            <span className="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Get in Touch
          </div>
          <h1 className="font-outfit text-5xl md:text-6xl font-bold leading-tight">
            Connect with the <br />
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-emerald-400">Tafakari Team</span>
          </h1>
          <p className="text-lg text-slate-600 leading-relaxed max-w-lg">
            Whether you are a researcher, community leader, or concerned citizen, we want to hear from you. 
            Your insights help shape our regional knowledge repository.
          </p>
          
          <div className="flex flex-col gap-6 mt-4">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 glass border-emerald-400/30 rounded-2xl flex items-center justify-center text-xl shadow-sm">📍</div>
              <div>
                <h4 className="font-bold text-slate-900 dark:text-white uppercase tracking-tight">Regional HQ</h4>
                <p className="text-sm text-slate-500 italic">Nairobi, Kenya</p>
              </div>
            </div>
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 glass border-emerald-400/30 rounded-2xl flex items-center justify-center text-xl shadow-sm">📧</div>
              <div>
                <h4 className="font-bold text-slate-900 dark:text-white uppercase tracking-tight">Email Analysis</h4>
                <p className="text-sm text-slate-500 italic">contact@tafakarihub.org</p>
              </div>
            </div>
          </div>
        </div>

        <div>
          {submitted ? (
            <div className="glass p-12 rounded-[3rem] border-emerald-400/30 text-center animate-in zoom-in duration-500 text-white">
               <div className="w-20 h-20 bg-white text-emerald-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-6 shadow-xl border-4 border-emerald-500/20">✓</div>
               <h2 className="font-outfit text-3xl font-bold mb-4 text-white uppercase tracking-tight">Information Received</h2>
               <p className="text-emerald-50/70 mb-8 italic leading-relaxed">Thank you for reaching out. A member of our editorial team will review your message and respond within 48 hours.</p>
               <button onClick={() => setSubmitted(false)} className="bg-white text-emerald-900 px-8 py-3 rounded-xl font-bold hover:bg-emerald-50 transition-all shadow-xl">Send another message</button>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="glass p-10 rounded-[3rem] border-emerald-400/30 bg-emerald-900/50 shadow-2xl space-y-6 text-white">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <label className="text-xs font-bold uppercase tracking-widest text-emerald-200/50 ml-1">Full Name</label>
                  <input type="text" required className="w-full bg-white/10 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-white transition-all font-medium placeholder:text-white/20" placeholder="Jane Doe" />
                </div>
                <div className="space-y-2">
                  <label className="text-xs font-bold uppercase tracking-widest text-emerald-200/50 ml-1">Email Address</label>
                  <input type="email" required className="w-full bg-white/10 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-white transition-all font-medium placeholder:text-white/20" placeholder="jane@example.com" />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <label className="text-xs font-bold uppercase tracking-widest text-emerald-200/50 ml-1">Country</label>
                  <select className="w-full bg-white/10 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-white transition-all font-medium appearance-none">
                    <option className="bg-emerald-900">Kenya</option>
                    <option className="bg-emerald-900">Ethiopia</option>
                    <option className="bg-emerald-900">DR Congo</option>
                    <option className="bg-emerald-900">Other</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <label className="text-xs font-bold uppercase tracking-widest text-emerald-200/50 ml-1">Area of Interest</label>
                  <select className="w-full bg-white/10 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-white transition-all font-medium appearance-none">
                    <option className="bg-emerald-900">Health</option>
                    <option className="bg-emerald-900">Security</option>
                    <option className="bg-emerald-900">Policy</option>
                    <option className="bg-emerald-900">Media/Press</option>
                    <option className="bg-emerald-900">Other</option>
                  </select>
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-emerald-200/50 ml-1">Message</label>
                <textarea rows={4} className="w-full bg-white/10 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-white transition-all font-medium placeholder:text-white/20" placeholder="How can we help or collaborate?"></textarea>
              </div>

              <div className="pt-4">
                 <p className="text-[10px] text-emerald-100/50 mb-6 italic">By submitting, you agree to our GDPR-aligned data protection policy. We value your privacy and never share information with third parties.</p>
                 <button type="submit" className="w-full bg-white text-emerald-900 py-4 rounded-2xl text-sm font-bold shadow-xl hover:bg-emerald-50 transition-all uppercase tracking-widest">Submit Information</button>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
