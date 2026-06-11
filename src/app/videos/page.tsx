export const dynamic = "force-dynamic";

import Image from "next/image";
import prisma from "@/lib/prisma";
import { formatDate, formatNumber } from "@/lib/format";

export default async function VideosPage() {
  const videos = await prisma.post.findMany({
    where: { type: "VIDEO", status: "PUBLISHED" },
    orderBy: { createdAt: "desc" },
    select: {
      id: true,
      title: true,
      description: true,
      thumbnailUrl: true,
      mediaUrl: true,
      country: true,
      issueCategory: true,
      viewCount: true,
      createdAt: true,
    },
    take: 12,
  });

  return (
    <div className="max-w-7xl mx-auto px-6 py-12">
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

      {videos.length === 0 ? (
        <div className="text-center py-32 text-slate-400">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-2xl">🎬</div>
          <p className="text-lg font-semibold text-slate-500">No videos published yet.</p>
          <p className="text-sm mt-2">Check back soon for documentaries and field reports.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
          {videos.map((vid) => {
            const card = (
              <div className="flex flex-col gap-5 group cursor-pointer">
                <div className="aspect-video rounded-[2.5rem] overflow-hidden relative shadow-lg group-hover:shadow-2xl transition-all duration-500 border-4 border-white group-hover:scale-[1.02] bg-slate-100">
                  {vid.thumbnailUrl ? (
                    <Image
                      src={vid.thumbnailUrl}
                      alt={vid.title}
                      fill
                      className="object-cover transition-transform duration-700 group-hover:scale-105"
                    />
                  ) : (
                    <div className="absolute inset-0 premium-gradient opacity-20" />
                  )}
                  <div className="absolute inset-0 bg-black/45 transition-opacity duration-500" />
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="w-18 h-18 bg-white/25 backdrop-blur-xl border-2 border-white/50 rounded-full flex items-center justify-center text-white text-3xl shadow-2xl group-hover:scale-110 group-hover:bg-white/40 transition-all duration-300 pl-1">
                      ▶
                    </div>
                  </div>
                  <div className="absolute bottom-5 left-5 flex gap-2 flex-wrap">
                    <span className="glass px-3 py-1 rounded-full text-[10px] font-bold text-white border-white/20 uppercase tracking-widest backdrop-blur-md">
                      {vid.country}
                    </span>
                    <span className="bg-primary px-3 py-1 rounded-full text-[10px] font-bold text-white uppercase tracking-widest border border-white/10">
                      {vid.issueCategory}
                    </span>
                  </div>
                  <div className="absolute top-5 right-5">
                    <span className="bg-black/50 backdrop-blur-md text-white/80 px-3 py-1 rounded-full text-[10px] font-semibold border border-white/10">
                      👁 {formatNumber(vid.viewCount)}
                    </span>
                  </div>
                </div>
                <div className="px-1">
                  <h3 className="font-outfit font-bold text-xl group-hover:text-secondary transition-colors uppercase tracking-tight leading-snug">
                    {vid.title}
                  </h3>
                  {vid.description && (
                    <p className="text-sm text-slate-500 mt-1.5 line-clamp-2">{vid.description}</p>
                  )}
                  <p className="text-sm text-slate-400 mt-1.5 font-medium italic">
                    {formatDate(vid.createdAt)}
                  </p>
                </div>
              </div>
            );

            return vid.mediaUrl ? (
              <a key={vid.id} href={vid.mediaUrl} target="_blank" rel="noopener noreferrer">
                {card}
              </a>
            ) : (
              <div key={vid.id}>{card}</div>
            );
          })}
        </div>
      )}
    </div>
  );
}
