export const dynamic = "force-dynamic";

import { requireSuperAdmin } from "@/lib/auth-helpers";
import prisma from "@/lib/prisma";
import HeatmapConfigClient from "./HeatmapConfigClient";

export default async function HeatmapConfigPage() {
  await requireSuperAdmin();

  type CountryRow = {
    id: string; name: string; code: string;
    centerLat: number; centerLng: number; zoom: number; isActive: boolean;
  };
  type CategoryRow = { id: string; name: string; isActive: boolean };

  let countries: CountryRow[] = [];
  let categories: CategoryRow[] = [];
  let tablesReady = false;

  try {
    [countries, categories] = await prisma.$transaction([
      prisma.heatmapCountry.findMany({ orderBy: { name: "asc" } }),
      prisma.heatmapCategory.findMany({ orderBy: { name: "asc" } }),
    ]);
    tablesReady = true;
  } catch {
    tablesReady = false;
  }

  return (
    <div className="flex flex-col gap-8">
      <div>
        <h1 className="font-outfit text-3xl font-bold text-slate-900">Heatmap Configuration</h1>
        <p className="text-slate-500 mt-1 italic">
          Manage countries and issue categories displayed on the regional heatmap
        </p>
      </div>

      <HeatmapConfigClient
        initialCountries={countries}
        initialCategories={categories}
        tablesReady={tablesReady}
      />
    </div>
  );
}
