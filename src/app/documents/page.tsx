export const dynamic = "force-dynamic";

import prisma from "@/lib/prisma";
import { formatDate, formatNumber } from "@/lib/format";

export default async function DocumentsPage() {
  const docs = await prisma.post.findMany({
    where: { type: "DOCUMENT", status: "PUBLISHED" },
    orderBy: { createdAt: "desc" },
    select: {
      id: true,
      title: true,
      description: true,
      mediaUrl: true,
      country: true,
      region: true,
      issueCategory: true,
      downloadCount: true,
      createdAt: true,
    },
    take: 50,
  });

  function fileExtension(url: string | null): string {
    if (!url) return "DOC";
    const ext = url.split(".").pop()?.toUpperCase() ?? "DOC";
    return ext.length <= 5 ? ext : "DOC";
  }

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

      {docs.length === 0 ? (
        <div className="text-center py-32 text-slate-400">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-2xl">📄</div>
          <p className="text-lg font-semibold text-slate-500">No documents published yet.</p>
          <p className="text-sm mt-2">Reports, briefs, and datasets will appear here once approved.</p>
        </div>
      ) : (
        <div className="glass rounded-4xl overflow-hidden border-secondary/20 shadow-2xl">
          <table className="w-full text-left">
            <thead>
              <tr className="bg-primary/20 border-b border-secondary/20">
                <th className="px-8 py-6 text-xs font-bold uppercase tracking-widest text-white/50">Document Name</th>
                <th className="px-8 py-6 text-xs font-bold uppercase tracking-widest text-white/50">Country</th>
                <th className="px-8 py-6 text-xs font-bold uppercase tracking-widest text-white/50">Category</th>
                <th className="px-8 py-6 text-xs font-bold uppercase tracking-widest text-white/50">Published</th>
                <th className="px-8 py-6 text-xs font-bold uppercase tracking-widest text-white/50 text-right">Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/10">
              {docs.map((doc) => {
                const ext = fileExtension(doc.mediaUrl);
                return (
                  <tr key={doc.id} className="hover:bg-white/5 transition-colors group">
                    <td className="px-8 py-6">
                      <div className="flex items-center gap-4">
                        <div className="w-10 h-10 bg-secondary/15 rounded-xl flex items-center justify-center text-secondary font-bold text-xs border border-white/5 group-hover:bg-white group-hover:text-primary transition-all shrink-0">
                          {ext}
                        </div>
                        <div>
                          <span className="font-outfit font-bold text-white uppercase tracking-tight line-clamp-1">
                            {doc.title}
                          </span>
                          {doc.description && (
                            <p className="text-xs text-slate-400 mt-0.5 line-clamp-1">{doc.description}</p>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="px-8 py-6 text-sm text-slate-300 font-medium italic">{doc.country}</td>
                    <td className="px-8 py-6">
                      <span className="px-3 py-1 rounded-full bg-secondary/15 text-secondary text-[10px] font-bold uppercase tracking-widest border border-secondary/20">
                        {doc.issueCategory}
                      </span>
                    </td>
                    <td className="px-8 py-6 text-xs text-slate-400">{formatDate(doc.createdAt)}</td>
                    <td className="px-8 py-6 text-right">
                      {doc.mediaUrl ? (
                        <a
                          href={doc.mediaUrl}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-secondary font-bold text-sm hover:text-white transition-colors hover:underline"
                        >
                          Download
                        </a>
                      ) : (
                        <span className="text-slate-600 text-sm italic">No file</span>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      <div className="mt-12 text-center">
        <p className="text-xs text-slate-400 italic">
          Showing {docs.length} document{docs.length !== 1 ? "s" : ""} •{" "}
          Request custom data via{" "}
          <a href="/contact" className="text-secondary font-bold hover:underline">
            contact form
          </a>
        </p>
      </div>
    </div>
  );
}
