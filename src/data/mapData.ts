export interface GeoFeature {
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

export interface GeoData {
  type: string;
  features: GeoFeature[];
}

export const MOCK_GEO_JSON: GeoData = {
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
