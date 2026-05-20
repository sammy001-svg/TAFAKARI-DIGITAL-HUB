export default function DocumentsPage() {
  const docs = [
    { id: 1, title: "2025 Kenya Regional Health Survey", type: "PDF", size: "2.4 MB", country: "Kenya", category: "Health" },
    { id: 2, title: "Ethiopia Policy Reform Briefing", type: "DOCX", size: "1.1 MB", country: "Ethiopia", category: "Policy" },
    { id: 3, title: "DRC Conflict Mineral Data Matrix", type: "XLSX", size: "4.8 MB", country: "DRC", category: "Security" },
    { id: 4, title: "Tafakari Hub Annual Mission Report", type: "PDF", size: "6.2 MB", country: "All", category: "Other" },
  ];

  return (
    <div className="max-w-7xl mx-auto px-6 py-12">
      <div className="flex flex-col gap-4 mb-16">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/15 text-secondary text-sm font-semibold w-fit border border-secondary/20">
          <span className="flex h-2 w-2 rounded-full bg-secondary"></span>
          Research & Data
        </div>
        <h1 className="font-outfit text-4xl md:text-5xl font-bold">Document Library</h1>
        <p className="text-slate-500 max-w-xl">
          Searchable repository for research reports, policy briefs, datasets, and downloadable reference materials.
        </p>
      </div>

      <div className="flex flex-col gap-4 mb-12 lg:flex-row">
        <div className="grow glass px-6 py-4 rounded-2xl flex items-center gap-4 border-secondary/20 text-white">
          <span className="text-secondary">🔍</span>
          <input 
            type="text" 
            aria-label="Search documents"
            placeholder="Search by document title, keyword, or author..." 
            className="bg-transparent border-none focus:ring-0 text-sm w-full font-medium placeholder:text-white/30" 
          />
        </div>
        <div className="flex gap-4">
          <select 
            aria-label="Filter by Country"
            className="glass px-6 py-4 rounded-2xl text-sm font-semibold border-secondary/20 text-white bg-[#3B0708]/50 focus:ring-0 appearance-none"
          >
            <option>All Countries</option>
            <option>Kenya</option>
            <option>Ethiopia</option>
            <option>DRC</option>
          </select>
          <button className="bg-white text-primary py-4 px-8 rounded-2xl text-sm font-bold shadow-xl hover:bg-slate-100 transition-all">Apply Filters</button>
        </div>
      </div>

      <div className="glass rounded-4xl overflow-hidden border-secondary/20 shadow-2xl">
        <table className="w-full text-left">
          <thead>
            <tr className="bg-primary/20 border-b border-secondary/20">
              <th className="px-8 py-6 text-xs font-bold uppercase tracking-widest text-white/50">Document Name</th>
              <th className="px-8 py-6 text-xs font-bold uppercase tracking-widest text-white/50">Country</th>
              <th className="px-8 py-6 text-xs font-bold uppercase tracking-widest text-white/50">Type</th>
              <th className="px-8 py-6 text-xs font-bold uppercase tracking-widest text-white/50">Size</th>
              <th className="px-8 py-6 text-xs font-bold uppercase tracking-widest text-white/50 text-right">Action</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-white/10">
            {docs.map((doc) => (
              <tr key={doc.id} className="hover:bg-white/5 transition-colors group">
                <td className="px-8 py-6">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 bg-secondary/15 rounded-xl flex items-center justify-center text-secondary font-bold text-xs border border-white/5 group-hover:bg-white group-hover:text-primary transition-all">
                      {doc.type}
                    </div>
                    <span className="font-outfit font-bold text-white uppercase tracking-tight">{doc.title}</span>
                  </div>
                </td>
                <td className="px-8 py-6 text-sm text-slate-300 font-medium italic">{doc.country}</td>
                <td className="px-8 py-6">
                   <span className="px-3 py-1 rounded-full bg-secondary/15 text-secondary text-[10px] font-bold uppercase tracking-widest border border-secondary/20">{doc.category}</span>
                </td>
                <td className="px-8 py-6 text-sm text-secondary font-mono italic">{doc.size}</td>
                <td className="px-8 py-6 text-right">
                  <button className="text-secondary font-bold text-sm hover:text-white transition-colors hover:underline">Download</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      
      <div className="mt-12 text-center">
         <p className="text-xs text-slate-400 italic">Showing 4 of 42 documents • Request custom data via <a href="/contact" className="text-secondary font-bold hover:underline">contact form</a></p>
      </div>
    </div>
  );
}
