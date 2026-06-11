"use client";

import { useState } from "react";
import RegionalMap from "@/components/ui/Map";

const CATEGORIES = ["Health", "Education", "Security", "Climate", "Human Rights", "Policy"];

export default function HeatmapInteractive() {
  const [selectedCountry, setSelectedCountry] = useState<"KE" | "ET" | "CD" | "ALL">("ALL");
  const [selectedCategories, setSelectedCategories] = useState<string[]>(CATEGORIES);

  const toggleCategory = (category: string) => {
    setSelectedCategories((prev) =>
      prev.includes(category) ? prev.filter((c) => c !== category) : [...prev, category]
    );
  };

  return (
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
                <span
                  className={`text-sm font-medium transition-colors ${
                    selectedCategories.includes(cat) ? "text-white" : "text-white/50"
                  }`}
                >
                  {cat}
                </span>
              </label>
            ))}
          </div>
        </div>

        <div className="glass p-6 rounded-3xl border-secondary/20 bg-primary/50 text-white shadow-xl">
          <h3 className="font-outfit font-bold mb-2">Export Data</h3>
          <p className="text-xs text-white/70 mb-6 leading-relaxed">
            Download the current view as a high-resolution PNG or localized CSV dataset.
          </p>
          <div className="flex flex-col gap-2">
            <button
              type="button"
              className="w-full bg-white text-primary py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-100 transition-colors shadow-lg"
            >
              Export HTML/PNG
            </button>
            <button
              type="button"
              className="w-full bg-primary text-white py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:opacity-90 transition-colors border border-white/5"
            >
              Export CSV
            </button>
          </div>
        </div>
      </div>

      <div className="lg:col-span-3">
        <RegionalMap country={selectedCountry} categories={selectedCategories} />
      </div>
    </div>
  );
}
