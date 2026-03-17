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

interface GeoFeature {
  type: string;
  properties: {
    name: string;
    intensity: number;
    country: string;
    category: string;
    description: string;
  };
  geometry: {
    type: string;
    coordinates: [number, number];
  };
}

interface GeoData {
  type: string;
  features: GeoFeature[];
}

interface RegionalMapProps {
  country: "KE" | "ET" | "CD" | "ALL";
  categories?: string[];
}

const MOCK_GEO_JSON: GeoData = {
  type: "FeatureCollection",
  features: [
    // Kenya (16 Points)
    {
      type: "Feature",
      properties: { name: "Nairobi", intensity: 8, country: "KE", category: "Security", description: "Increased monitoring in central business district." },
      geometry: { type: "Point", coordinates: [36.8219, -1.2921] }
    },
    {
      type: "Feature",
      properties: { name: "Mombasa", intensity: 5, country: "KE", category: "Climate", description: "Coastal erosion reports near historical sites." },
      geometry: { type: "Point", coordinates: [39.6673, -4.0435] }
    },
    {
      type: "Feature",
      properties: { name: "Kisumu", intensity: 6, country: "KE", category: "Social", description: "Community engagement for lake conservation." },
      geometry: { type: "Point", coordinates: [34.7679, -0.0917] }
    },
    {
      type: "Feature",
      properties: { name: "Eldoret", intensity: 4, country: "KE", category: "Education", description: "Regional educational hub developments and expansion." },
      geometry: { type: "Point", coordinates: [35.2697, 0.5143] }
    },
    {
      type: "Feature",
      properties: { name: "Nakuru", intensity: 3, country: "KE", category: "Agriculture", description: "Large-scale irrigation improvement projects in Rift Valley." },
      geometry: { type: "Point", coordinates: [36.0662, -0.3031] }
    },
    {
      type: "Feature",
      properties: { name: "Machakos", intensity: 5, country: "KE", category: "Infrastructure", description: "Urban expansion and road connectivity phase 2." },
      geometry: { type: "Point", coordinates: [37.2634, -1.5177] }
    },
    {
      type: "Feature",
      properties: { name: "Malindi", intensity: 6, country: "KE", category: "Climate", description: "Marine ecosystem protection and coral restoration." },
      geometry: { type: "Point", coordinates: [40.1169, -3.2175] }
    },
    {
      type: "Feature",
      properties: { name: "Garissa", intensity: 7, country: "KE", category: "Security", description: "Resource allocation for border security enhancement." },
      geometry: { type: "Point", coordinates: [39.6461, -0.4532] }
    },
    {
      type: "Feature",
      properties: { name: "Lodwar", intensity: 4, country: "KE", category: "Security", description: "Northern border security and resource management reports." },
      geometry: { type: "Point", coordinates: [35.5947, 3.1192] }
    },
    {
      type: "Feature",
      properties: { name: "Kakamega", intensity: 5, country: "KE", category: "Health", description: "Community health initiatives and tropical forest conservation." },
      geometry: { type: "Point", coordinates: [34.757, 0.2827] }
    },
    {
      type: "Feature",
      properties: { name: "Meru", intensity: 4, country: "KE", category: "Agriculture", description: "New sustainable farming certifications for local exporters." },
      geometry: { type: "Point", coordinates: [37.6482, 0.0463] }
    },
    {
      type: "Feature",
      properties: { name: "Isiolo", intensity: 5, country: "KE", category: "Policy", description: "Administrative hub planning for regional connectivity." },
      geometry: { type: "Point", coordinates: [37.5833, 0.35] }
    },
    {
      type: "Feature",
      properties: { name: "Narok", intensity: 3, country: "KE", category: "Tourism", description: "Eco-tourism sustainability guidelines for the Mara region." },
      geometry: { type: "Point", coordinates: [35.8722, -1.0789] }
    },
    {
      type: "Feature",
      properties: { name: "Voi", intensity: 5, country: "KE", category: "Transport", description: "Major rail and road transit hub upgrades in progress." },
      geometry: { type: "Point", coordinates: [38.563, -3.393] }
    },
    {
      type: "Feature",
      properties: { name: "Lamu", intensity: 6, country: "KE", category: "History", description: "Historical site preservation and marine port security." },
      geometry: { type: "Point", coordinates: [40.9022, -2.2717] }
    },
    {
      type: "Feature",
      properties: { name: "Marsabit", intensity: 7, country: "KE", category: "Climate", description: "Critical drought monitoring and food security interventions." },
      geometry: { type: "Point", coordinates: [37.9833, 2.3333] }
    },

    // Ethiopia (16 Points)
    {
      type: "Feature",
      properties: { name: "Addis Ababa", intensity: 6, country: "ET", category: "Health", description: "New regional health center inauguration." },
      geometry: { type: "Point", coordinates: [38.7578, 8.9806] }
    },
    {
      type: "Feature",
      properties: { name: "Gonder", intensity: 4, country: "ET", category: "Education", description: "University expansion project updates." },
      geometry: { type: "Point", coordinates: [37.4667, 12.6] }
    },
    {
      type: "Feature",
      properties: { name: "Bahir Dar", intensity: 3, country: "ET", category: "Tourism", description: "Sustainability initiative for Blue Nile Falls." },
      geometry: { type: "Point", coordinates: [37.3908, 11.5936] }
    },
    {
      type: "Feature",
      properties: { name: "Mekele", intensity: 8, country: "ET", category: "Reconstruction", description: "Post-conflict infrastructure rehabilitation efforts." },
      geometry: { type: "Point", coordinates: [39.4753, 13.4927] }
    },
    {
      type: "Feature",
      properties: { name: "Hawassa", intensity: 5, country: "ET", category: "Industry", description: "Expansion of industrial park and local job creation." },
      geometry: { type: "Point", coordinates: [38.4735, 7.062] }
    },
    {
      type: "Feature",
      properties: { name: "Dire Dawa", intensity: 5, country: "ET", category: "Trade", description: "Logistics hub modernization and trade corridor safety." },
      geometry: { type: "Point", coordinates: [41.8661, 9.5933] }
    },
    {
      type: "Feature",
      properties: { name: "Jimma", intensity: 4, country: "ET", category: "Agriculture", description: "Specialty coffee certification and sustainable farming." },
      geometry: { type: "Point", coordinates: [36.8333, 7.6667] }
    },
    {
      type: "Feature",
      properties: { name: "Adama", intensity: 6, country: "ET", category: "Transport", description: "High-speed rail maintenance and urban transit expansion." },
      geometry: { type: "Point", coordinates: [39.2667, 8.55] }
    },
    {
      type: "Feature",
      properties: { name: "Dessie", intensity: 5, country: "ET", category: "Trade", description: "Strategic trade route security and market stabilization." },
      geometry: { type: "Point", coordinates: [39.6333, 11.1333] }
    },
    {
      type: "Feature",
      properties: { name: "Jijiga", intensity: 8, country: "ET", category: "Social", description: "Community resilience programs and cross-border trade." },
      geometry: { type: "Point", coordinates: [42.8, 9.35] }
    },
    {
      type: "Feature",
      properties: { name: "Shashemene", intensity: 5, country: "ET", category: "Transport", description: "Southern transit network improvements and road safety." },
      geometry: { type: "Point", coordinates: [38.6, 7.2] }
    },
    {
      type: "Feature",
      properties: { name: "Arba Minch", intensity: 4, country: "ET", category: "Tourism", description: "Eco-tourism development and local community training." },
      geometry: { type: "Point", coordinates: [37.55, 6.0333] }
    },
    {
      type: "Feature",
      properties: { name: "Gambela", intensity: 6, country: "ET", category: "Water", description: "River management and regional water security project." },
      geometry: { type: "Point", coordinates: [34.5833, 8.25] }
    },
    {
      type: "Feature",
      properties: { name: "Asosa", intensity: 5, country: "ET", category: "Mining", description: "Mining regulation audits and environmental safety." },
      geometry: { type: "Point", coordinates: [34.5333, 10.0667] }
    },
    {
      type: "Feature",
      properties: { name: "Semera", intensity: 5, country: "ET", category: "Climate", description: "Monitoring high-temperature trends and energy resilience." },
      geometry: { type: "Point", coordinates: [41.0167, 11.7833] }
    },
    {
      type: "Feature",
      properties: { name: "Debre Markos", intensity: 4, country: "ET", category: "Education", description: "New educational facilities and local research hub." },
      geometry: { type: "Point", coordinates: [37.7333, 10.3333] }
    },

    // DRC (17 Points)
    {
      type: "Feature",
      properties: { name: "Kinshasa", intensity: 9, country: "CD", category: "Human Rights", description: "Civil society reports on labor conditions." },
      geometry: { type: "Point", coordinates: [15.307, -4.322] }
    },
    {
      type: "Feature",
      properties: { name: "Goma", intensity: 7, country: "CD", category: "Security", description: "Border stability assessment in progress." },
      geometry: { type: "Point", coordinates: [29.22, -1.67] }
    },
    {
      type: "Feature",
      properties: { name: "Lubumbashi", intensity: 5, country: "CD", category: "Mining", description: "Environmental impact audit of local copper mines." },
      geometry: { type: "Point", coordinates: [27.4794, -11.6607] }
    },
    {
      type: "Feature",
      properties: { name: "Kisangani", intensity: 4, country: "CD", category: "Energy", description: "Hydroelectric plant optimization for local industry." },
      geometry: { type: "Point", coordinates: [25.19, 0.515] }
    },
    {
      type: "Feature",
      properties: { name: "Kananga", intensity: 6, country: "CD", category: "Health", description: "Regional vaccine distribution network rollout." },
      geometry: { type: "Point", coordinates: [22.4167, -5.8962] }
    },
    {
      type: "Feature",
      properties: { name: "Bukavu", intensity: 8, country: "CD", category: "Human Rights", description: "Mineral supply chain transparency initiative." },
      geometry: { type: "Point", coordinates: [28.85, -2.5] }
    },
    {
      type: "Feature",
      properties: { name: "Mbuji-Mayi", intensity: 5, country: "CD", category: "Mining", description: "Diamond artisan guild formation and safety training." },
      geometry: { type: "Point", coordinates: [23.5967, -6.1522] }
    },
    {
      type: "Feature",
      properties: { name: "Matadi", intensity: 3, country: "CD", category: "Logistics", description: "Port modernization and shipping efficiency audit." },
      geometry: { type: "Point", coordinates: [13.4508, -5.8194] }
    },
    {
      type: "Feature",
      properties: { name: "Beni", intensity: 7, country: "CD", category: "Security", description: "Peacebuilding workshops and civilian protection measures." },
      geometry: { type: "Point", coordinates: [29.47, 0.49] }
    },
    {
      type: "Feature",
      properties: { name: "Boma", intensity: 4, country: "CD", category: "Trade", description: "Maritime logistics and shipping lane security audits." },
      geometry: { type: "Point", coordinates: [13.05, -5.85] }
    },
    {
      type: "Feature",
      properties: { name: "Mbandaka", intensity: 6, country: "CD", category: "Climate", description: "Riverine ecosystem monitoring and flora protection." },
      geometry: { type: "Point", coordinates: [18.26, 0.048] }
    },
    {
      type: "Feature",
      properties: { name: "Bandundu", intensity: 5, country: "CD", category: "Agriculture", description: "Inland agricultural development and food transit." },
      geometry: { type: "Point", coordinates: [17.38, -3.31] }
    },
    {
      type: "Feature",
      properties: { name: "Isiro", intensity: 4, country: "CD", category: "Mining", description: "Small-scale mining safety and regional infrastructure." },
      geometry: { type: "Point", coordinates: [27.6167, 2.7833] }
    },
    {
      type: "Feature",
      properties: { name: "Kindu", intensity: 5, country: "CD", category: "Social", description: "Public transport reliability and community services." },
      geometry: { type: "Point", coordinates: [25.95, -2.95] }
    },
    {
      type: "Feature",
      properties: { name: "Kalemie", intensity: 8, country: "CD", category: "Security", description: "Lake stability monitoring and water resource access." },
      geometry: { type: "Point", coordinates: [29.17, -5.92] }
    },
    {
      type: "Feature",
      properties: { name: "Kamina", intensity: 5, country: "CD", category: "Security", description: "Central transit hub security and regional logistics." },
      geometry: { type: "Point", coordinates: [24.98, -9.41] }
    },
    {
      type: "Feature",
      properties: { name: "Lisala", intensity: 4, country: "CD", category: "Health", description: "Health facility upgrades and medical supply chains." },
      geometry: { type: "Point", coordinates: [21.5167, 2.15] }
    }
  ]
};

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
