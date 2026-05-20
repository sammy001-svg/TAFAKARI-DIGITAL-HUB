"use client";

import { useState } from "react";
import RegionalMap from "@/components/ui/Map";

const CATEGORIES = ["Health", "Education", "Security", "Climate", "Human Rights", "Policy"];

export default function HeatmapPage() {
  const [selectedCountry, setSelectedCountry] = useState<"KE" | "ET" | "CD" | "ALL">("ALL");
  const [selectedCategories, setSelectedCategories] = useState<string[]>(CATEGORIES);

  const toggleCategory = (category: string) => {
    setSelectedCategories(prev => 
      prev.includes(category) 
        ? prev.filter(c => c !== category) 
        : [...prev, category]
    );
  };

  return (
    <div className="max-w-7xl mx-auto px-6 py-12">
      <div className="flex flex-col md:flex-row justify-between items-end gap-8 mb-12">
        <div className="flex flex-col gap-4">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/15 text-primary text-sm font-semibold w-fit border border-secondary/20">
            <span className="flex h-2 w-2 rounded-full bg-primary animate-pulse"></span>
            Live Heatmap Data
          </div>
          <h1 className="font-outfit text-4xl md:text-5xl font-bold">Regional Issue Tracker</h1>
          <p className="text-slate-500 max-w-xl">
            Visualize the frequency and severity of reported issues across key categories in Kenya, Ethiopia, and the DRC.
          </p>
        </div>
        
        <div className="flex gap-4 p-2 glass rounded-2xl">
          {[
            { id: "ALL", label: "All Countries" },
            { id: "KE", label: "Kenya" },
            { id: "ET", label: "Ethiopia" },
            { id: "CD", label: "DRC" }
          ].map((country) => (
            <button 
              key={country.id}
              onClick={() => setSelectedCountry(country.id as "KE" | "ET" | "CD" | "ALL")}
              className={`px-6 py-2 rounded-xl transition-all font-semibold text-sm ${
                selectedCountry === country.id 
                  ? "bg-white shadow-sm text-primary scale-105" 
                  : "hover:bg-white/50 text-slate-650"
              }`}
            >
              {country.label}
            </button>
          ))}
        </div>
      </div>
      
      <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <div className="lg:col-span-1 flex flex-col gap-6">
          <div className="glass p-6 rounded-3xl border-secondary/20 text-white shadow-xl">
            <h3 className="font-outfit font-bold mb-4">Issue Categories</h3>
            <div className="flex flex-col gap-3">
              {CATEGORIES.map((cat) => (
                <label key={cat} className="flex items-center gap-3 cursor-pointer group">
                  <input 
                    type="checkbox" 
                    checked={selectedCategories.includes(cat)}
                    onChange={() => toggleCategory(cat)}
                    className="w-5 h-5 rounded-lg border-white/20 bg-white/10 text-white focus:ring-white shadow-sm transition-all" 
                  />
                  <span className={`text-sm font-medium transition-colors ${
                    selectedCategories.includes(cat) ? "text-white" : "text-white/50"
                  }`}>
                    {cat}
                  </span>
                </label>
              ))}
            </div>
          </div>
          
          <div className="glass p-6 rounded-3xl border-secondary/20 bg-primary/50 text-white shadow-xl">
            <h3 className="font-outfit font-bold mb-2">Export Data</h3>
            <p className="text-xs text-white/70 mb-6 leading-relaxed">Download the current view as a high-resolution PNG or localized CSV dataset.</p>
            <div className="flex flex-col gap-2">
              <button className="w-full bg-white text-primary py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-100 transition-colors shadow-lg">Export HTML/PNG</button>
              <button className="w-full bg-primary text-white py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:opacity-90 transition-colors border border-white/5">Export CSV</button>
            </div>
          </div>
        </div>
        
        <div className="lg:col-span-3">
          <RegionalMap country={selectedCountry} categories={selectedCategories} />
        </div>
      </div>
      
      <div className="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div className="flex flex-col gap-2 border-l-4 border-primary pl-6">
          <span className="text-4xl font-outfit font-black text-slate-900 leading-none">1,248</span>
          <span className="font-bold text-slate-700">Total Validated Reports</span>
          <p className="text-xs text-slate-400">Aggregated across all regional centers and provinces since launch.</p>
        </div>
        <div className="flex flex-col gap-2 border-l-4 border-slate-900 pl-6">
          <span className="text-4xl font-outfit font-black text-slate-900 leading-none">12.4%</span>
          <span className="font-bold text-slate-700">Growth in Security Insights</span>
          <p className="text-xs text-slate-400">Monthly increase in community-led conflict reporting in eastern DRC.</p>
        </div>
        <div className="flex flex-col gap-2 border-l-4 border-primary pl-6">
          <span className="text-4xl font-outfit font-black text-primary leading-none">24h</span>
          <span className="font-bold text-slate-700">Data Refresh Cycle</span>
          <p className="text-xs text-slate-400">Heatmap intensity is updated within hours of new editorial validation.</p>
        </div>
      </div>
    </div>
  );
}
