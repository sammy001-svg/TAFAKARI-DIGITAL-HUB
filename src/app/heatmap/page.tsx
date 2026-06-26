export const dynamic = "force-dynamic";

import prisma from "@/lib/prisma";
import HeatmapInteractive from "./HeatmapInteractive";
import { formatNumber } from "@/lib/format";

export default async function HeatmapPage() {
  const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);

  const [totalPublished, recentPublished] = await prisma.$transaction([
    prisma.post.count({ where: { status: "PUBLISHED" } }),
    prisma.post.count({
      where: { status: "PUBLISHED", createdAt: { gte: thirtyDaysAgo } },
    }),
  ]);

  let heatmapCountries: { id: string; name: string; code: string; centerLat: number; centerLng: number; zoom: number }[] = [];
  let heatmapCategories: string[] = [];

  try {
    const [countries, categories] = await prisma.$transaction([
      prisma.heatmapCountry.findMany({
        where: { isActive: true },
        orderBy: { name: "asc" },
        select: { id: true, name: true, code: true, centerLat: true, centerLng: true, zoom: true },
      }),
      prisma.heatmapCategory.findMany({
        where: { isActive: true },
        orderBy: { name: "asc" },
        select: { name: true },
      }),
    ]);
    heatmapCountries = countries;
    heatmapCategories = categories.map((c) => c.name);
  } catch {
    // Tables not yet initialized — HeatmapInteractive will use its built-in fallbacks
  }

  return (
    <div className="max-w-7xl mx-auto px-6 py-12">
      <div className="flex flex-col md:flex-row justify-between items-end gap-8 mb-12">
        <div className="flex flex-col gap-4">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/15 text-primary text-sm font-semibold w-fit border border-secondary/20">
            <span className="flex h-2 w-2 rounded-full bg-primary animate-pulse"></span>
            Live Heatmap Data
          </div>
          <h1 className="font-outfit text-4xl md:text-5xl font-bold">Regional Issue Tracker</h1>
          <p className="text-slate-500 max-w-xl">
            Visualize the frequency and severity of reported issues across key categories in Kenya, Ethiopia, and the DRC.
          </p>
        </div>
      </div>

      <HeatmapInteractive
        initialCountries={heatmapCountries}
        initialCategories={heatmapCategories}
      />

      <div className="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div className="flex flex-col gap-2 border-l-4 border-primary pl-6">
          <span className="text-4xl font-outfit font-black text-slate-900 leading-none">
            {formatNumber(totalPublished)}
          </span>
          <span className="font-bold text-slate-700">Total Validated Reports</span>
          <p className="text-xs text-slate-400">
            Published content across all regional centres and categories since launch.
          </p>
        </div>
        <div className="flex flex-col gap-2 border-l-4 border-slate-900 pl-6">
          <span className="text-4xl font-outfit font-black text-slate-900 leading-none">
            {formatNumber(recentPublished)}
          </span>
          <span className="font-bold text-slate-700">New Reports This Month</span>
          <p className="text-xs text-slate-400">
            Reports published in the last 30 days across all content types.
          </p>
        </div>
        <div className="flex flex-col gap-2 border-l-4 border-primary pl-6">
          <span className="text-4xl font-outfit font-black text-primary leading-none">24h</span>
          <span className="font-bold text-slate-700">Data Refresh Cycle</span>
          <p className="text-xs text-slate-400">
            Heatmap intensity is updated within hours of new editorial validation.
          </p>
        </div>
      </div>
    </div>
  );
}
