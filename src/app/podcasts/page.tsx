export const dynamic = "force-dynamic";

import prisma from "@/lib/prisma";
import { formatDate } from "@/lib/format";

export default async function PodcastsPage() {
  const episodes = await prisma.post.findMany({
    where: { type: "PODCAST", status: "PUBLISHED" },
    orderBy: { createdAt: "desc" },
    select: {
      id: true,
      title: true,
      description: true,
      mediaUrl: true,
      country: true,
      issueCategory: true,
      createdAt: true,
    },
    take: 20,
  });

  return (
    <div className="max-w-7xl mx-auto px-6 py-12">
      <div className="flex flex-col gap-4 mb-16">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/10 text-secondary text-sm font-semibold w-fit border border-secondary/20">
          <span className="flex h-2 w-2 rounded-full bg-secondary animate-pulse"></span>
          Listen & Learn
        </div>
        <h1 className="font-outfit text-4xl md:text-5xl font-bold">Podcast Library</h1>
        <p className="text-slate-500 max-w-xl">
          A dedicated audio library for interviews, field discussions, and regional oral reports.
        </p>
      </div>

      {episodes.length === 0 ? (
        <div className="text-center py-32 text-slate-400">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-2xl">🎙</div>
          <p className="text-lg font-semibold text-slate-500">No podcast episodes published yet.</p>
          <p className="text-sm mt-2">Check back soon for audio reports and interviews.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-6">
          {episodes.map((ep) => (
            <div
              key={ep.id}
              className="glass p-8 rounded-4xl border-secondary/20 flex flex-col md:flex-row items-center gap-8 transition-all hover:border-white/40 hover:shadow-2xl text-white"
            >
              {ep.mediaUrl ? (
                <a
                  href={ep.mediaUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label={`Play ${ep.title}`}
                  className="w-16 h-16 bg-primary rounded-full flex items-center justify-center text-white text-2xl shadow-lg shrink-0 hover:scale-110 transition-transform border border-white/20 pl-1"
                >
                  ▶
                </a>
              ) : (
                <div className="w-16 h-16 bg-primary/50 rounded-full flex items-center justify-center text-white text-2xl shadow-lg shrink-0 border border-white/20 pl-1">
                  ▶
                </div>
              )}
              <div className="grow text-center md:text-left">
                <span className="text-[10px] font-bold uppercase tracking-[0.2em] text-secondary mb-2 block">
                  {ep.issueCategory} • {ep.country}
                </span>
                <h3 className="font-outfit font-bold text-xl mb-1 text-white">{ep.title}</h3>
                {ep.description && (
                  <p className="text-xs text-slate-300 line-clamp-2 mt-1">{ep.description}</p>
                )}
                <p className="text-xs text-slate-400 mt-1.5">{formatDate(ep.createdAt)}</p>
              </div>
              {ep.mediaUrl && (
                <div className="flex gap-4 shrink-0">
                  <a
                    href={ep.mediaUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="px-4 py-2 bg-white text-primary rounded-full text-xs font-semibold hover:bg-slate-100 transition-colors"
                  >
                    Listen
                  </a>
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      <div className="mt-20 p-12 bg-[#3B0708] rounded-[3rem] text-white relative overflow-hidden border border-secondary/20">
        <div className="absolute top-0 right-0 w-64 h-64 bg-secondary/10 rounded-full blur-[80px]"></div>
        <div className="relative z-10 flex flex-col md:flex-row justify-between items-center gap-12">
          <div className="space-y-4">
            <h2 className="font-outfit text-3xl font-bold">Subscribe to our feed</h2>
            <p className="text-slate-300 max-w-md">
              Never miss an episode. Get our latest field discussions delivered directly to your device.
            </p>
          </div>
          <div className="flex gap-4 grayscale opacity-60">
            <div className="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center font-bold border border-white/5 uppercase tracking-tighter text-[10px]">
              Spotify
            </div>
            <div className="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center font-bold border border-white/5 uppercase tracking-tighter text-[10px]">
              Apple
            </div>
            <div className="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center font-bold border border-white/5 uppercase tracking-tighter text-[10px]">
              Google
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
