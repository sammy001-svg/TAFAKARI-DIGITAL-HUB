export default function NewsPage() {
  const articles = [
    { id: 1, title: "Advancing Digital Literacy in Rural Kenya", country: "Kenya", topic: "Education", date: "Mar 15, 2026", snippet: "A new initiative is bringing high-speed internet and digital training to three counties in western Kenya..." },
    { id: 2, title: "Evaluating the Impact of Ethiopia's New Policy", country: "Ethiopia", topic: "Governance", date: "Mar 10, 2026", snippet: "Regional leaders meet in Addis Ababa to discuss the implications of recent legislative changes on infrastructure..." },
    { id: 3, title: "Community-led Conflict Resolution in East DRC", country: "DRC", topic: "Security", date: "Mar 05, 2026", snippet: "Local mediation committees are showing promising results in reduction of cross-border tensions..." },
  ];

  return (
    <div className="max-w-7xl mx-auto px-6 py-12">
      <div className="flex flex-col gap-4 mb-16">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm font-semibold w-fit">
          Narratives & Reports
        </div>
        <h1 className="font-outfit text-4xl md:text-5xl font-bold">News & Articles</h1>
        <p className="text-slate-500 max-w-xl">
          In-depth analysis and timely updates on the issues that matter most across the region.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
        {articles.map((art) => (
          <div key={art.id} className="flex flex-col gap-6 group cursor-pointer">
            <div className="h-64 bg-slate-100 rounded-[2.5rem] overflow-hidden relative border border-slate-200">
               <div className="absolute inset-0 premium-gradient opacity-10 group-hover:opacity-20 transition-opacity"></div>
               <div className="absolute top-6 left-6 flex gap-2">
                  <span className="glass px-3 py-1 rounded-full text-[10px] font-bold text-slate-700 uppercase">{art.country}</span>
               </div>
            </div>
            <div>
              <span className="text-[10px] font-black uppercase tracking-widest text-indigo-600 mb-2 block">{art.topic}</span>
              <h3 className="font-outfit font-bold text-2xl group-hover:text-indigo-600 transition-colors leading-snug mb-3">{art.title}</h3>
              <p className="text-slate-500 text-sm leading-relaxed mb-6 line-clamp-3">{art.snippet}</p>
              <div className="flex justify-between items-center">
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{art.date}</span>
                <span className="text-indigo-600 font-black text-xs uppercase tracking-widest group-hover:translate-x-2 transition-transform">&rarr; Read Full</span>
              </div>
            </div>
          </div>
        ))}
      </div>
      
      <div className="mt-20 flex justify-center">
         <button className="glass px-12 py-4 rounded-full font-bold text-sm hover:bg-white transition-all shadow-sm">Load More Stories</button>
      </div>
    </div>
  );
}
