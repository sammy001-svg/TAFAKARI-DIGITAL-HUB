export const dynamic = "force-dynamic";

export default function ApprovalsPage() {
  const pendingItems = [
    { id: 1, title: "Report on Kenya Health Trends", type: "ARTICLE", author: "Jane Doe", date: "2h ago", country: "Kenya" },
    { id: 2, title: "DRC Conflict Mineral Data", type: "DOCUMENT", author: "John Smith", date: "5h ago", country: "DRC" },
    { id: 3, title: "Agricultural Resilience Podcast", type: "PODCAST", author: "Jane Doe", date: "1d ago", country: "Ethiopia" },
  ];

  return (
    <div className="flex flex-col gap-10">
      <div className="flex justify-between items-end">
        <div>
          <h1 className="font-outfit text-3xl font-bold">Approval Queue</h1>
          <p className="text-slate-500 mt-1 italic">Vetting editorial submissions for regional publication</p>
        </div>
        <div className="flex gap-2 p-1 glass rounded-xl bg-white/50">
           <button className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold shadow-lg">Pending (3)</button>
           <button className="px-4 py-2 hover:bg-white transition-colors rounded-lg text-xs font-bold text-slate-500">History</button>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-6">
        {pendingItems.map((item) => (
          <div key={item.id} className="glass p-8 rounded-[2rem] border-white/50 bg-white flex flex-col lg:flex-row items-center gap-8 group hover:shadow-xl transition-all">
            <div className="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-2xl group-hover:bg-indigo-50 transition-colors shadow-inner">
               {item.type === "ARTICLE" ? "📄" : item.type === "DOCUMENT" ? "📁" : "🎧"}
            </div>
            
            <div className="flex-grow text-center lg:text-left">
              <div className="flex items-center gap-2 justify-center lg:justify-start mb-1">
                <span className="text-[10px] font-bold uppercase tracking-widest text-indigo-600">{item.type}</span>
                <span className="w-1 h-1 rounded-full bg-slate-300"></span>
                <span className="text-[10px] font-bold uppercase tracking-widest text-slate-400">{item.country}</span>
              </div>
              <h3 className="font-outfit font-bold text-xl text-slate-900 mb-1">{item.title}</h3>
              <p className="text-xs text-slate-400 font-medium italic">Submitted by {item.author} • {item.date}</p>
            </div>

            <div className="flex items-center gap-4 bg-slate-50/50 p-3 rounded-[1.5rem] border border-slate-100">
               <button className="px-6 py-3 rounded-xl hover:bg-slate-100 transition-colors text-xs font-bold uppercase tracking-widest text-slate-600">Review</button>
               <button className="px-6 py-3 rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/20 hover:scale-105 transition-all text-xs font-bold uppercase tracking-widest">Approve</button>
               <button className="w-12 h-12 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center">✕</button>
            </div>
          </div>
        ))}
      </div>
      
      {/* Editorial Rule Alert */}
      <div className="mt-12 p-8 bg-slate-950 rounded-[2.5rem] text-white relative overflow-hidden">
        <div className="absolute top-0 right-1/4 w-64 h-64 bg-indigo-500/20 rounded-full blur-[80px]"></div>
        <div className="relative z-10 flex gap-8 items-start">
           <div className="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center text-2xl">⚠️</div>
           <div>
              <h4 className="font-outfit font-bold text-xl mb-2">Social Media Auto-Publishing</h4>
              <p className="text-slate-400 text-sm leading-relaxed max-w-2xl">
                Approving an item will automatically trigger the <strong>Asynchronous Social Queue</strong>. 
                Content will be distributed to Facebook, X, and Instagram within minutes of your sign-off.
              </p>
           </div>
        </div>
      </div>
    </div>
  );
}
