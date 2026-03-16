export const dynamic = "force-dynamic";

export default function CommentModerationPage() {
  const flaggedComments = [
    { id: 1, author: "Anonymous", content: "This report is biased and doesn't reflect the truth on the ground!", reason: "Hate Speech Alert (Low)", date: "10m ago", item: "Kenya Health Trends" },
    { id: 2, author: "Bot_92", content: "Get cheap bitcoin here: http://scam.link", reason: "Spam Filter Match", date: "1h ago", item: "Election Policy 2026" },
  ];

  return (
    <div className="flex flex-col gap-10">
      <div className="flex justify-between items-end">
        <div>
          <h1 className="font-outfit text-3xl font-bold">Comment Moderation</h1>
          <p className="text-slate-500 mt-1 italic">Managing community engagement and safety filters</p>
        </div>
        <div className="flex gap-4">
           <div className="flex flex-col items-end">
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-2">Auto-Moderation</span>
              <div className="flex items-center gap-2 bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-full text-[10px] font-black uppercase ring-4 ring-emerald-50">Active</div>
           </div>
        </div>
      </div>

      <div className="glass p-10 rounded-[2.5rem] border-white/50 bg-white">
        <h3 className="font-outfit font-bold text-xl mb-8 flex items-center gap-3 text-rose-600">
           <span className="w-2 h-2 rounded-full bg-rose-600 animate-pulse"></span>
           Flagged for Review
        </h3>
        
        <div className="space-y-8">
           {flaggedComments.map((comment) => (
             <div key={comment.id} className="p-8 rounded-3xl bg-slate-50 border border-slate-100 flex flex-col md:flex-row gap-8 items-start relative overflow-hidden group">
               <div className="absolute top-0 left-0 w-1.5 h-full bg-rose-500"></div>
               
               <div className="flex-grow">
                 <div className="flex items-center gap-3 mb-4">
                    <div className="w-8 h-8 rounded-lg bg-white flex items-center justify-center font-bold text-xs text-slate-400 shadow-sm">
                      {comment.author[0]}
                    </div>
                    <div>
                      <span className="text-sm font-bold text-slate-900">{comment.author}</span>
                      <span className="text-[10px] text-slate-400 ml-2 italic">on {comment.item}</span>
                    </div>
                 </div>
                 <p className="text-slate-700 text-sm leading-relaxed mb-4 italic">&quot;{comment.content}&quot;</p>
                 <div className="inline-flex items-center gap-2 px-3 py-1.5 bg-rose-100 text-rose-600 rounded-lg text-[10px] font-bold uppercase tracking-widest border border-rose-200/50">
                    Flag: {comment.reason}
                 </div>
               </div>

               <div className="flex gap-2 w-full md:w-fit mt-4 md:mt-0">
                  <button className="flex-grow md:flex-none px-6 py-3 bg-white text-emerald-600 border border-emerald-200 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-emerald-600 hover:text-white transition-all shadow-sm">Accept</button>
                  <button className="flex-grow md:flex-none px-6 py-3 bg-rose-600 text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-lg shadow-rose-500/20 hover:scale-105 transition-all">Delete</button>
               </div>
             </div>
           ))}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
         <div className="glass p-8 rounded-[2rem] border-white/20 bg-slate-50">
            <h4 className="font-outfit font-bold text-lg mb-4">Moderation Stats</h4>
            <div className="space-y-4">
               <div className="flex justify-between items-center text-sm">
                  <span className="text-slate-500 font-medium">Auto-Blocked Spam</span>
                  <span className="font-black text-rose-600">1,242</span>
               </div>
               <div className="flex justify-between items-center text-sm border-y border-slate-200 py-4">
                  <span className="text-slate-500 font-medium">Manually Reviewed</span>
                  <span className="font-black text-indigo-600">84</span>
               </div>
               <div className="flex justify-between items-center text-sm">
                  <span className="text-slate-500 font-medium">Reported by Users</span>
                  <span className="font-black text-amber-600">12</span>
               </div>
            </div>
         </div>
         
         <div className="glass p-8 rounded-[2rem] border-white/20 bg-emerald-950 text-white">
            <h4 className="font-outfit font-bold text-lg mb-4 text-emerald-400">Moderation AI</h4>
            <p className="text-emerald-100/70 text-xs leading-relaxed mb-6"> Our multilingual AI filter currently supports English, Swahili, Amharic, and French with an accuracy rate of 94.2%.</p>
            <button className="w-full bg-emerald-500 text-white py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-emerald-400 transition-colors shadow-lg shadow-emerald-900/40">Open API Settings</button>
         </div>
      </div>
    </div>
  );
}
