"use client";

import { useEffect, useState } from "react";
import dynamic from "next/dynamic";
import type { Map } from "leaflet";
import "leaflet/dist/leaflet.css";

// Dynamic import for Leaflet components to avoid SSR issues
const MapContainer = dynamic(
  () => import("react-leaflet").then((mod) => mod.MapContainer),
  { ssr: false }
);
const TileLayer = dynamic(
  () => import("react-leaflet").then((mod) => mod.TileLayer),
  { ssr: false }
);
const GeoJSON = dynamic(
  () => import("react-leaflet").then((mod) => mod.GeoJSON),
  { ssr: false }
);

import { type GeoData, type GeoFeature, MOCK_GEO_JSON } from "@/data/mapData";

interface RegionalMapProps {
  country: "KE" | "ET" | "CD" | "ALL";
  categories?: string[];
}



const COUNTRY_VIEWS: Record<string, { center: [number, number], zoom: number, title: string }> = {
  ALL: { center: [1.2921, 36.8219], zoom: 4, title: "Regional Overview" },
  KE: { center: [0.0236, 37.9062], zoom: 6, title: "Kenya Hub" },
  ET: { center: [9.145, 40.4897], zoom: 6, title: "Ethiopia Hub" },
  CD: { center: [-4.0383, 21.7587], zoom: 5, title: "DRC Hub" }
};

export default function RegionalMap({ country, categories = [] }: RegionalMapProps) {
  const [geoData, setGeoData] = useState<GeoData>(MOCK_GEO_JSON);
  const [isLoading, setIsLoading] = useState(true);
  const [L, setL] = useState<typeof import("leaflet") | null>(null); // Leaflet instance
  const [mapInstance, setMapInstance] = useState<Map | null>(null); // Map instance

  useEffect(() => {
    import("leaflet").then((leaflet) => {
      setL(leaflet.default);
      setIsLoading(false);
    });
  }, []);

  useEffect(() => {
    const filteredFeatures = MOCK_GEO_JSON.features.filter(f => {
      const countryMatch = country === "ALL" || f.properties.country === country;
      const categoryMatch = categories.length === 0 || categories.includes(f.properties.category);
      return countryMatch && categoryMatch;
    });
    
    setGeoData({ ...MOCK_GEO_JSON, features: filteredFeatures });
  }, [country, categories]);

  useEffect(() => {
    if (mapInstance) {
      const view = COUNTRY_VIEWS[country] || COUNTRY_VIEWS.ALL;
      mapInstance.flyTo(view.center, view.zoom, { animate: true, duration: 1.5 });
    }
  }, [country, mapInstance]);

  const getStyle = (feature: import("geojson").Feature<import("geojson").Geometry> | undefined) => {
    const f = feature as GeoFeature;
    const intensity = f?.properties?.intensity || 0;
    return {
      fillColor: intensity > 7 ? "#065f46" : intensity > 4 ? "#10b981" : "#a7f3d0",
      weight: 1,
      opacity: 1,
      color: "white",
      fillOpacity: 0.7
    };
  };

  const getCountryStats = () => {
    const stats = {
      total: geoData.features.length,
      avgIntensity: geoData.features.reduce((acc, f) => acc + f.properties.intensity, 0) / (geoData.features.length || 1),
      topCategory: ""
    };
    
    const categoryCounts: Record<string, number> = {};
    geoData.features.forEach(f => {
      categoryCounts[f.properties.category] = (categoryCounts[f.properties.category] || 0) + 1;
    });
    
    stats.topCategory = Object.entries(categoryCounts).sort((a, b) => b[1] - a[1])[0]?.[0] || "N/A";
    
    return stats;
  };

  if (isLoading) {
    return (
      <div className="w-full h-[600px] flex items-center justify-center bg-slate-100 rounded-3xl animate-pulse">
        <p className="text-slate-400 font-medium font-outfit uppercase tracking-tighter">Initializing Map Engine...</p>
      </div>
    );
  }

  const stats = getCountryStats();
  const currentView = COUNTRY_VIEWS[country] || COUNTRY_VIEWS.ALL;

  return (
    <div className="w-full flex flex-col gap-6">
      {/* Map Canvas */}
      <div className="w-full h-[600px] rounded-3xl overflow-hidden shadow-2xl relative border-4 border-white group">
        <MapContainer
          center={COUNTRY_VIEWS.ALL.center}
          zoom={COUNTRY_VIEWS.ALL.zoom}
          style={{ height: "100%", width: "100%" }}
          scrollWheelZoom={false}
          ref={setMapInstance}
        >
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          {geoData && L && (
            <GeoJSON 
              data={geoData as unknown as import("geojson").GeoJsonObject} 
              style={getStyle} 
              // eslint-disable-next-line @typescript-eslint/no-explicit-any
              pointToLayer={(feature: any, latlng: import("leaflet").LatLng) => {
                const f = feature as GeoFeature;
                const intensity = f?.properties?.intensity || 0;
                return L.circleMarker(latlng, {
                  radius: intensity * 1.5 + 4,
                  fillColor: intensity > 7 ? "#065f46" : intensity > 4 ? "#10b981" : "#34d399",
                  weight: 2,
                  opacity: 1,
                  color: "white",
                  fillOpacity: 0.8
                }).bindPopup(`
                  <div class="p-4 font-inter min-w-[240px]">
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-[10px] uppercase tracking-widest bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">${f.properties.category}</span>
                      <span class="text-[10px] text-slate-400 font-bold">${f.properties.country}</span>
                    </div>
                    <h4 class="font-outfit font-bold text-slate-900 text-lg">${f.properties.name}</h4>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed border-l-2 border-emerald-500 pl-3">${f.properties.description}</p>
                    <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-2">
                      <div class="bg-slate-50 p-2 rounded-lg text-center">
                         <span class="block text-[8px] text-slate-400 uppercase font-black">Intensity</span>
                         <span class="text-sm font-black text-emerald-600">${f.properties.intensity}/10</span>
                      </div>
                      <a href="/news" class="bg-emerald-600 text-white rounded-lg flex items-center justify-center text-[10px] font-bold hover:bg-emerald-700 transition-colors uppercase tracking-widest">Details</a>
                    </div>
                  </div>
                `, { closeButton: false });
              }}
            />
          )}

          {/* Action Controls */}
          <div className="absolute bottom-6 right-6 z-1000 flex flex-col gap-2">
             <button className="glass w-10 h-10 rounded-full flex items-center justify-center text-emerald-700 hover:bg-emerald-500 hover:text-white transition-all shadow-lg font-black">+</button>
             <button className="glass w-10 h-10 rounded-full flex items-center justify-center text-emerald-700 hover:bg-emerald-500 hover:text-white transition-all shadow-lg font-black">-</button>
          </div>
        </MapContainer>
      </div>

      {/* Dashboard & Legend — below the map */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {/* Regional Scorecard */}
        <div className="md:col-span-2 glass p-5 rounded-2xl shadow-lg">
          <h3 className="font-outfit font-black text-emerald-800 text-sm uppercase tracking-tighter mb-4 flex items-center gap-2">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            {currentView.title}
          </h3>
          <div className="grid grid-cols-3 gap-3">
            <div className="bg-white/60 backdrop-blur-sm p-4 rounded-xl border border-white/50">
              <span className="block text-[9px] text-emerald-600 uppercase font-bold tracking-widest mb-1">Reports</span>
              <span className="text-2xl font-outfit font-black text-emerald-950">{stats.total}</span>
            </div>
            <div className="bg-white/60 backdrop-blur-sm p-4 rounded-xl border border-white/50">
              <span className="block text-[9px] text-emerald-600 uppercase font-bold tracking-widest mb-1">Avg Stress</span>
              <span className="text-2xl font-outfit font-black text-emerald-950">{stats.avgIntensity.toFixed(1)}</span>
            </div>
            <div className="bg-emerald-900 p-4 rounded-xl text-white">
              <span className="block text-[9px] text-emerald-300 uppercase font-bold tracking-widest mb-1">Top Focus Area</span>
              <span className="text-sm font-bold font-inter truncate block">{stats.topCategory}</span>
            </div>
          </div>
        </div>

        {/* Intensity Legend */}
        <div className="glass p-5 rounded-2xl shadow-lg flex flex-col justify-center">
          <h4 className="font-outfit font-bold text-xs uppercase tracking-widest text-emerald-800 mb-4 opacity-60">Issue Intensity</h4>
          <div className="flex flex-col gap-3 text-[11px] font-black">
            <div className="flex items-center gap-3 text-emerald-900/80">
              <span className="w-3 h-3 rounded-full bg-emerald-800 shrink-0 shadow-[0_0_8px_rgba(6,95,70,0.5)]"></span>
              <span className="uppercase">Critical Zone (8–10)</span>
            </div>
            <div className="flex items-center gap-3 text-emerald-700/80">
              <span className="w-3 h-3 rounded-full bg-emerald-500 shrink-0 shadow-[0_0_8px_rgba(16,185,129,0.4)]"></span>
              <span className="uppercase">Moderate Focus (5–7)</span>
            </div>
            <div className="flex items-center gap-3 text-emerald-600">
              <span className="w-3 h-3 rounded-full bg-emerald-300 shrink-0"></span>
              <span className="uppercase">Localized Incident (1–4)</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
