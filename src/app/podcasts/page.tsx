export default function PodcastsPage() {
  const episodes = [
    { id: 1, title: "Youth Engagement in Nairobi", series: "Urban Future", duration: "24:12", date: "Jan 12, 2026" },
    { id: 2, title: "Agricultural Resilience in Oromia", series: "Rural Voices", duration: "18:45", date: "Feb 05, 2026" },
    { id: 3, title: "Cross-border Trade Insights", series: "Economic Policy", duration: "32:10", date: "Mar 01, 2026" },
  ];

  return (
    <div className="max-w-7xl mx-auto px-6 py-12">
      <div className="flex flex-col gap-4 mb-16">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100/10 text-emerald-400 text-sm font-semibold w-fit border border-emerald-500/20">
          <span className="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
          Listen & Learn
        </div>
        <h1 className="font-outfit text-4xl md:text-5xl font-bold">Podcast Library</h1>
        <p className="text-slate-500 max-w-xl">
          A dedicated audio library for interviews, field discussions, and regional oral reports.
        </p>
      </div>

      <div className="grid grid-cols-1 gap-6">
        {episodes.map((ep) => (
          <div key={ep.id} className="glass p-8 rounded-[2rem] border-emerald-400/30 flex flex-col md:flex-row items-center gap-8 transition-all hover:border-white/40 hover:shadow-2xl text-white">
            <div className="w-16 h-16 premium-gradient rounded-full flex items-center justify-center text-white text-2xl shadow-lg flex-shrink-0 cursor-pointer hover:scale-110 transition-transform border border-white/20">
              ▶
            </div>
            <div className="flex-grow text-center md:text-left">
              <span className="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-300 mb-2 block">{ep.series}</span>
              <h3 className="font-outfit font-bold text-xl mb-1 text-white">{ep.title}</h3>
              <p className="text-xs text-emerald-100/60">{ep.date} • {ep.duration}</p>
            </div>
            <div className="flex gap-4">
               <button className="px-4 py-2 border border-white/10 rounded-full text-xs font-semibold hover:bg-white/10 transition-colors">Transcript</button>
               <button className="px-4 py-2 bg-white text-emerald-900 rounded-full text-xs font-semibold hover:bg-emerald-50 transition-colors">Download</button>
            </div>
          </div>
        ))}
      </div>
      
      <div className="mt-20 p-12 bg-emerald-950 rounded-[3rem] text-white relative overflow-hidden border border-emerald-800/30">
        <div className="absolute top-0 right-0 w-64 h-64 bg-emerald-500/10 rounded-full blur-[80px]"></div>
        <div className="relative z-10 flex flex-col md:flex-row justify-between items-center gap-12">
          <div className="space-y-4">
            <h2 className="font-outfit text-3xl font-bold">Subscribe to our feed</h2>
            <p className="text-emerald-100/70 max-w-md">Never miss an episode. Get our latest field discussions delivered directly to your device.</p>
          </div>
          <div className="flex gap-4 grayscale opacity-60">
             <div className="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center font-bold border border-white/5 uppercase tracking-tighter">Spotify</div>
             <div className="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center font-bold border border-white/5 uppercase tracking-tighter">Apple</div>
             <div className="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center font-bold border border-white/5 uppercase tracking-tighter">Google</div>
          </div>
        </div>
      </div>
    </div>
  );
}
