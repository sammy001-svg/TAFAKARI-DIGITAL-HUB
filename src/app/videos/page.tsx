import Image from "next/image";

const videos = [
  {
    id: 1,
    title: "Tafakari Mission Overview",
    country: "All",
    category: "Governance",
    duration: "5:30",
    views: "1.2k",
    thumbnail: "/gallery_featured_1.png",
    overlay: "bg-black/45",
    accent: "text-secondary",
    badge: "bg-primary",
  },
  {
    id: 2,
    title: "Kenya County Insight: Mombasa",
    country: "Kenya",
    category: "Economic",
    duration: "12:15",
    views: "850",
    thumbnail: "/gallery_mombasa.png",
    overlay: "bg-black/45",
    accent: "text-secondary",
    badge: "bg-secondary",
  },
  {
    id: 3,
    title: "Ethiopian Agricultural Reform",
    country: "Ethiopia",
    category: "Climate",
    duration: "8:45",
    views: "2.1k",
    thumbnail: "/gallery_addis.png",
    overlay: "bg-black/45",
    accent: "text-secondary",
    badge: "bg-primary",
  },
  {
    id: 4,
    title: "DRC Security & Conflict Report",
    country: "DRC",
    category: "Security",
    duration: "15:20",
    views: "3.4k",
    thumbnail: "/gallery_goma.png",
    overlay: "bg-black/45",
    accent: "text-secondary",
    badge: "bg-primary",
  },
];

export default function VideosPage() {
  return (
    <div className="max-w-7xl mx-auto px-6 py-12">
      {/* Header */}
      <div className="flex flex-col gap-4 mb-16">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/10 text-secondary text-sm font-semibold w-fit border border-secondary/20">
          <span className="flex h-2 w-2 rounded-full bg-secondary"></span>
          Visual Reports
        </div>
        <h1 className="font-outfit text-4xl md:text-5xl font-bold">Video Library</h1>
        <p className="text-slate-500 max-w-xl">
          Documentaries, event recordings, and field reports from across the region.
        </p>
      </div>

      {/* Video Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
        {videos.map((vid) => (
          <div key={vid.id} className="flex flex-col gap-5 group cursor-pointer">
            {/* Thumbnail */}
            <div className="aspect-video rounded-[2.5rem] overflow-hidden relative shadow-lg group-hover:shadow-2xl transition-all duration-500 border-4 border-white group-hover:scale-[1.02]">
              {/* Real background image */}
              <Image
                src={vid.thumbnail}
                alt={vid.title}
                fill
                className="object-cover transition-transform duration-700 group-hover:scale-105"
              />

              {/* Category color-grade overlay (No Gradients) */}
              <div className={`absolute inset-0 ${vid.overlay} transition-opacity duration-500`}></div>

              {/* Play button */}
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="w-18 h-18 bg-white/25 backdrop-blur-xl border-2 border-white/50 rounded-full flex items-center justify-center text-white text-3xl shadow-2xl group-hover:scale-110 group-hover:bg-white/40 transition-all duration-300 pl-1">
                  ▶
                </div>
              </div>

              {/* Bottom left: country + category badges */}
              <div className="absolute bottom-5 left-5 flex gap-2 flex-wrap">
                <span className="glass px-3 py-1 rounded-full text-[10px] font-bold text-white border-white/20 uppercase tracking-widest backdrop-blur-md">
                  {vid.country}
                </span>
                <span className={`${vid.badge} px-3 py-1 rounded-full text-[10px] font-bold text-white uppercase tracking-widest border border-white/10`}>
                  {vid.category}
                </span>
              </div>

              {/* Bottom right: duration badge */}
              <div className="absolute bottom-5 right-5">
                <span className="bg-black/70 backdrop-blur-md text-white px-3 py-1 rounded-lg text-xs font-mono font-bold border border-white/10">
                  {vid.duration}
                </span>
              </div>

              {/* Top right: views indicator */}
              <div className="absolute top-5 right-5">
                <span className="bg-black/50 backdrop-blur-md text-white/80 px-3 py-1 rounded-full text-[10px] font-semibold border border-white/10">
                  👁 {vid.views} views
                </span>
              </div>
            </div>

            {/* Info row */}
            <div className="flex justify-between items-start px-1">
              <div>
                <h3 className="font-outfit font-bold text-xl group-hover:text-secondary transition-colors uppercase tracking-tight leading-snug">
                  {vid.title}
                </h3>
                <p className="text-sm text-slate-400 mt-1.5 font-medium italic">
                  {vid.views} views • 2 weeks ago
                </p>
              </div>
              <button className="text-slate-400 hover:text-secondary transition-colors shrink-0 ml-4 mt-1">
                <span className="text-xl">⋮</span>
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
