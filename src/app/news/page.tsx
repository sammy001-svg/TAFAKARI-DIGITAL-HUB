export const dynamic = "force-dynamic";

import Link from "next/link";
import Image from "next/image";
import prisma from "@/lib/prisma";
import { formatDate } from "@/lib/format";

export default async function NewsPage() {
  const articles = await prisma.post.findMany({
    where: { type: "ARTICLE", status: "PUBLISHED" },
    orderBy: { createdAt: "desc" },
    select: {
      id: true,
      title: true,
      description: true,
      thumbnailUrl: true,
      country: true,
      issueCategory: true,
      createdAt: true,
      viewCount: true,
      author: { select: { name: true, username: true } },
    },
    take: 12,
  });

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

      {articles.length === 0 ? (
        <div className="text-center py-32 text-slate-400">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-2xl">📰</div>
          <p className="text-lg font-semibold text-slate-500">No articles published yet.</p>
          <p className="text-sm mt-2">Check back soon for the latest updates from across the region.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
          {articles.map((art) => (
            <Link key={art.id} href={`/news/${art.id}`} className="flex flex-col gap-6 group cursor-pointer">
              <div className="h-64 bg-slate-100 rounded-[2.5rem] overflow-hidden relative border border-slate-200">
                {art.thumbnailUrl ? (
                  <Image
                    src={art.thumbnailUrl}
                    alt={art.title}
                    fill
                    className="object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                ) : (
                  <div className="absolute inset-0 premium-gradient opacity-10 group-hover:opacity-20 transition-opacity" />
                )}
                <div className="absolute inset-0 bg-linear-to-t from-black/30 to-transparent" />
                <div className="absolute top-6 left-6 flex gap-2">
                  <span className="glass px-3 py-1 rounded-full text-[10px] font-bold text-white uppercase backdrop-blur-md">
                    {art.country}
                  </span>
                </div>
              </div>
              <div>
                <span className="text-[10px] font-black uppercase tracking-widest text-secondary mb-2 block">
                  {art.issueCategory}
                </span>
                <h3 className="font-outfit font-bold text-2xl group-hover:text-primary transition-colors leading-snug mb-3">
                  {art.title}
                </h3>
                {art.description && (
                  <p className="text-slate-500 text-sm leading-relaxed mb-6 line-clamp-3">{art.description}</p>
                )}
                <div className="flex justify-between items-center">
                  <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    {formatDate(art.createdAt)}
                  </span>
                  <span className="text-primary font-black text-xs uppercase tracking-widest group-hover:translate-x-2 transition-transform">
                    &rarr; Read Full
                  </span>
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
