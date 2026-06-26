"use client";

import { useState } from "react";

type Country = {
  id: string;
  name: string;
  code: string;
  centerLat: number;
  centerLng: number;
  zoom: number;
  isActive: boolean;
};

type Category = {
  id: string;
  name: string;
  isActive: boolean;
};

interface Props {
  initialCountries: Country[];
  initialCategories: Category[];
  tablesReady: boolean;
}

export default function HeatmapConfigClient({ initialCountries, initialCategories, tablesReady }: Props) {
  const [countries, setCountries] = useState(initialCountries);
  const [categories, setCategories] = useState(initialCategories);
  const [activeTab, setActiveTab] = useState<"countries" | "categories">("countries");
  const [isReady, setIsReady] = useState(tablesReady);
  const [isInitializing, setIsInitializing] = useState(false);
  const [initError, setInitError] = useState("");

  const [countryForm, setCountryForm] = useState({ name: "", code: "", centerLat: "", centerLng: "", zoom: "6" });
  const [countryAdding, setCountryAdding] = useState(false);
  const [countryError, setCountryError] = useState("");

  const [categoryName, setCategoryName] = useState("");
  const [categoryAdding, setCategoryAdding] = useState(false);
  const [categoryError, setCategoryError] = useState("");

  const handleInitialize = async () => {
    setIsInitializing(true);
    setInitError("");
    try {
      const res = await fetch("/api/heatmap/setup", { method: "POST" });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Initialization failed");
      setIsReady(true);
      window.location.reload();
    } catch (e) {
      setInitError(e instanceof Error ? e.message : "Initialization failed");
    } finally {
      setIsInitializing(false);
    }
  };

  const handleAddCountry = async (e: React.FormEvent) => {
    e.preventDefault();
    setCountryAdding(true);
    setCountryError("");
    try {
      const res = await fetch("/api/heatmap/countries", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: countryForm.name.trim(),
          code: countryForm.code.trim().toUpperCase(),
          centerLat: parseFloat(countryForm.centerLat),
          centerLng: parseFloat(countryForm.centerLng),
          zoom: parseInt(countryForm.zoom),
        }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Failed to add country");
      setCountries((prev) => [...prev, data].sort((a, b) => a.name.localeCompare(b.name)));
      setCountryForm({ name: "", code: "", centerLat: "", centerLng: "", zoom: "6" });
    } catch (e) {
      setCountryError(e instanceof Error ? e.message : "Failed to add country");
    } finally {
      setCountryAdding(false);
    }
  };

  const handleDeleteCountry = async (id: string, name: string) => {
    if (!confirm(`Remove "${name}" from the heatmap? This cannot be undone.`)) return;
    try {
      const res = await fetch(`/api/heatmap/countries/${id}`, { method: "DELETE" });
      if (!res.ok) {
        const data = await res.json();
        throw new Error(data.error || "Failed to delete");
      }
      setCountries((prev) => prev.filter((c) => c.id !== id));
    } catch (e) {
      alert(e instanceof Error ? e.message : "Failed to remove country");
    }
  };

  const handleAddCategory = async (e: React.FormEvent) => {
    e.preventDefault();
    setCategoryAdding(true);
    setCategoryError("");
    try {
      const res = await fetch("/api/heatmap/categories", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: categoryName.trim() }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Failed to add category");
      setCategories((prev) => [...prev, data].sort((a, b) => a.name.localeCompare(b.name)));
      setCategoryName("");
    } catch (e) {
      setCategoryError(e instanceof Error ? e.message : "Failed to add category");
    } finally {
      setCategoryAdding(false);
    }
  };

  const handleDeleteCategory = async (id: string, name: string) => {
    if (!confirm(`Remove "${name}" from the heatmap? This cannot be undone.`)) return;
    try {
      const res = await fetch(`/api/heatmap/categories/${id}`, { method: "DELETE" });
      if (!res.ok) {
        const data = await res.json();
        throw new Error(data.error || "Failed to delete");
      }
      setCategories((prev) => prev.filter((c) => c.id !== id));
    } catch (e) {
      alert(e instanceof Error ? e.message : "Failed to remove category");
    }
  };

  if (!isReady) {
    return (
      <div className="bg-amber-50 border border-amber-200 rounded-2xl p-8 max-w-2xl">
        <div className="flex items-start gap-4">
          <div className="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-lg shrink-0">⚠️</div>
          <div className="flex-1">
            <h3 className="font-bold text-amber-900 text-lg mb-2">Heatmap tables not initialized</h3>
            <p className="text-amber-800 text-sm leading-relaxed mb-4">
              The database tables for heatmap countries and categories need to be created first.
              Click below to create them and seed 18 African countries with 12 default issue categories.
            </p>
            {initError && <p className="text-red-600 text-sm mb-3 font-medium">{initError}</p>}
            <button
              onClick={handleInitialize}
              disabled={isInitializing}
              className="bg-primary text-white px-6 py-3 rounded-xl font-bold text-sm hover:opacity-90 disabled:opacity-50 transition-all"
            >
              {isInitializing ? "Initializing…" : "Initialize Heatmap Config"}
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="flex gap-1 p-1 bg-slate-100 rounded-xl w-fit">
        <button
          onClick={() => setActiveTab("countries")}
          className={`px-5 py-2 rounded-lg text-sm font-bold transition-all ${
            activeTab === "countries" ? "bg-white text-slate-900 shadow-sm" : "text-slate-500 hover:text-slate-700"
          }`}
        >
          Countries ({countries.length})
        </button>
        <button
          onClick={() => setActiveTab("categories")}
          className={`px-5 py-2 rounded-lg text-sm font-bold transition-all ${
            activeTab === "categories" ? "bg-white text-slate-900 shadow-sm" : "text-slate-500 hover:text-slate-700"
          }`}
        >
          Categories ({categories.length})
        </button>
      </div>

      {activeTab === "countries" && (
        <div className="flex flex-col gap-6">
          <div className="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 className="font-bold text-slate-900 mb-1">Add Country</h3>
            <p className="text-xs text-slate-400 mb-4">
              Enter the country name, ISO code, and the map center coordinates.
              Use{" "}
              <a href="https://www.latlong.net" target="_blank" rel="noopener noreferrer" className="text-primary underline">
                latlong.net
              </a>{" "}
              to look up coordinates.
            </p>
            <form onSubmit={handleAddCountry} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <input
                required
                placeholder="Country name (e.g. Uganda)"
                value={countryForm.name}
                onChange={(e) => setCountryForm((f) => ({ ...f, name: e.target.value }))}
                className="border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
              />
              <input
                required
                placeholder="Code (e.g. UG)"
                maxLength={10}
                value={countryForm.code}
                onChange={(e) => setCountryForm((f) => ({ ...f, code: e.target.value.toUpperCase() }))}
                className="border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
              />
              <input
                required
                type="number"
                step="any"
                placeholder="Center latitude (e.g. 1.3733)"
                value={countryForm.centerLat}
                onChange={(e) => setCountryForm((f) => ({ ...f, centerLat: e.target.value }))}
                className="border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
              />
              <input
                required
                type="number"
                step="any"
                placeholder="Center longitude (e.g. 32.2903)"
                value={countryForm.centerLng}
                onChange={(e) => setCountryForm((f) => ({ ...f, centerLng: e.target.value }))}
                className="border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
              />
              <input
                required
                type="number"
                min="3"
                max="12"
                placeholder="Zoom level (4–10)"
                value={countryForm.zoom}
                onChange={(e) => setCountryForm((f) => ({ ...f, zoom: e.target.value }))}
                className="border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
              />
              <div className="flex items-center gap-3">
                <button
                  type="submit"
                  disabled={countryAdding}
                  className="bg-primary text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:opacity-90 disabled:opacity-50 transition-all"
                >
                  {countryAdding ? "Adding…" : "Add Country"}
                </button>
                {countryError && <p className="text-red-500 text-xs">{countryError}</p>}
              </div>
            </form>
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
              <h3 className="font-bold text-slate-900">Active Countries</h3>
              <span className="text-xs text-slate-400 font-medium">{countries.length} countries on heatmap</span>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-slate-50">
                  <tr>
                    <th className="text-left px-6 py-3 font-bold text-slate-500 text-xs uppercase tracking-wider">Country</th>
                    <th className="text-left px-6 py-3 font-bold text-slate-500 text-xs uppercase tracking-wider">Code</th>
                    <th className="text-left px-6 py-3 font-bold text-slate-500 text-xs uppercase tracking-wider">Center (Lat, Lng)</th>
                    <th className="text-left px-6 py-3 font-bold text-slate-500 text-xs uppercase tracking-wider">Zoom</th>
                    <th className="px-6 py-3"></th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {countries.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="text-center py-10 text-slate-400 text-sm">
                        No countries added yet. Use the form above to add one.
                      </td>
                    </tr>
                  ) : (
                    countries.map((c) => (
                      <tr key={c.id} className="hover:bg-slate-50 transition-colors">
                        <td className="px-6 py-4 font-medium text-slate-900">{c.name}</td>
                        <td className="px-6 py-4">
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full bg-secondary/15 text-primary text-xs font-black">
                            {c.code}
                          </span>
                        </td>
                        <td className="px-6 py-4 text-slate-500 font-mono text-xs">
                          {c.centerLat.toFixed(4)}, {c.centerLng.toFixed(4)}
                        </td>
                        <td className="px-6 py-4 text-slate-500">{c.zoom}</td>
                        <td className="px-6 py-4 text-right">
                          <button
                            onClick={() => handleDeleteCountry(c.id, c.name)}
                            className="text-red-400 hover:text-red-600 font-bold text-xs uppercase tracking-widest transition-colors"
                          >
                            Remove
                          </button>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeTab === "categories" && (
        <div className="flex flex-col gap-6">
          <div className="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 className="font-bold text-slate-900 mb-1">Add Category</h3>
            <p className="text-xs text-slate-400 mb-4">
              Issue categories appear as filter checkboxes on the public heatmap page.
            </p>
            <form onSubmit={handleAddCategory} className="flex gap-3 max-w-xl">
              <input
                required
                placeholder="Category name (e.g. Water & Sanitation)"
                value={categoryName}
                onChange={(e) => setCategoryName(e.target.value)}
                className="flex-1 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
              />
              <button
                type="submit"
                disabled={categoryAdding}
                className="bg-primary text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:opacity-90 disabled:opacity-50 transition-all shrink-0"
              >
                {categoryAdding ? "Adding…" : "Add"}
              </button>
            </form>
            {categoryError && <p className="text-red-500 text-xs mt-2">{categoryError}</p>}
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
              <h3 className="font-bold text-slate-900">Active Categories</h3>
              <span className="text-xs text-slate-400 font-medium">{categories.length} categories</span>
            </div>
            <div className="divide-y divide-slate-100">
              {categories.length === 0 ? (
                <div className="text-center py-10 text-slate-400 text-sm">
                  No categories added yet. Use the form above to add one.
                </div>
              ) : (
                categories.map((c) => (
                  <div key={c.id} className="flex items-center justify-between px-6 py-4 hover:bg-slate-50 transition-colors">
                    <span className="font-medium text-slate-900 text-sm">{c.name}</span>
                    <button
                      onClick={() => handleDeleteCategory(c.id, c.name)}
                      className="text-red-400 hover:text-red-600 font-bold text-xs uppercase tracking-widest transition-colors"
                    >
                      Remove
                    </button>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
