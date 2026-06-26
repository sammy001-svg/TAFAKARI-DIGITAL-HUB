"use client";

import { useState } from "react";
import RegionalMap from "@/components/ui/Map";

interface CountryView {
  id: string;
  name: string;
  code: string;
  centerLat: number;
  centerLng: number;
  zoom: number;
}

interface Props {
  initialCountries?: CountryView[];
  initialCategories?: string[];
}

const FALLBACK_COUNTRIES: CountryView[] = [
  { id: "fb_ke", name: "Kenya",    code: "KE", centerLat:  0.0236,  centerLng: 37.9062, zoom: 6 },
  { id: "fb_et", name: "Ethiopia", code: "ET", centerLat:  9.145,   centerLng: 40.4897, zoom: 6 },
  { id: "fb_cd", name: "DR Congo", code: "CD", centerLat: -4.0383,  centerLng: 21.7587, zoom: 5 },
];

const FALLBACK_CATEGORIES = ["Health", "Education", "Security", "Climate", "Human Rights", "Policy"];

export default function HeatmapInteractive({ initialCountries = [], initialCategories = [] }: Props) {
  const countries = initialCountries.length > 0 ? initialCountries : FALLBACK_COUNTRIES;
  const allCategories = initialCategories.length > 0 ? initialCategories : FALLBACK_CATEGORIES;

  const [selectedCountry, setSelectedCountry] = useState<string>("ALL");
  const [selectedCategories, setSelectedCategories] = useState<string[]>(allCategories);

  const toggleCategory = (category: string) => {
    setSelectedCategories((prev) =>
      prev.includes(category) ? prev.filter((c) => c !== category) : [...prev, category]
    );
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
      <div className="lg:col-span-1 flex flex-col gap-6">
        {/* Country Filter */}
        <div className="glass p-6 rounded-3xl border-secondary/20 text-white shadow-xl">
          <h3 className="font-outfit font-bold mb-4">Country Filter</h3>
          <div className="flex flex-col gap-1 max-h-64 overflow-y-auto pr-1">
            <button
              type="button"
              onClick={() => setSelectedCountry("ALL")}
              className={`text-sm px-3 py-2.5 rounded-xl font-medium transition-all text-left ${
                selectedCountry === "ALL"
                  ? "bg-white/20 text-white font-bold"
                  : "text-white/60 hover:text-white hover:bg-white/10"
              }`}
            >
              All Countries
            </button>
            {countries.map((c) => (
              <button
                key={c.code}
                type="button"
                onClick={() => setSelectedCountry(c.code)}
                className={`text-sm px-3 py-2.5 rounded-xl font-medium transition-all text-left flex items-center gap-2.5 ${
                  selectedCountry === c.code
                    ? "bg-white/20 text-white font-bold"
                    : "text-white/60 hover:text-white hover:bg-white/10"
                }`}
              >
                <span className="inline-flex items-center justify-center w-8 h-5 bg-white/10 rounded text-[9px] font-black tracking-wider shrink-0">
                  {c.code}
                </span>
                {c.name}
              </button>
            ))}
          </div>
        </div>

        {/* Issue Categories */}
        <div className="glass p-6 rounded-3xl border-secondary/20 text-white shadow-xl">
          <h3 className="font-outfit font-bold mb-4">Issue Categories</h3>
          <div className="flex flex-col gap-3 max-h-72 overflow-y-auto pr-1">
            {allCategories.map((cat) => (
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

        {/* Export Data */}
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
        <RegionalMap
          country={selectedCountry}
          categories={selectedCategories}
          countryViews={countries}
        />
      </div>
    </div>
  );
}
