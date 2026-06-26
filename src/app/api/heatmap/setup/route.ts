/**
 * POST /api/heatmap/setup
 * Super Admin only. Creates HeatmapCountry and HeatmapCategory tables,
 * then seeds them with 18 default African countries and 12 issue categories.
 */
import { NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import prisma from "@/lib/prisma";

export const dynamic = "force-dynamic";

const CREATE_COUNTRY_TABLE = `
  CREATE TABLE IF NOT EXISTS \`HeatmapCountry\` (
    \`id\`        VARCHAR(191) NOT NULL,
    \`name\`      VARCHAR(191) NOT NULL,
    \`code\`      VARCHAR(10)  NOT NULL,
    \`centerLat\` DOUBLE       NOT NULL,
    \`centerLng\` DOUBLE       NOT NULL,
    \`zoom\`      INT          NOT NULL DEFAULT 6,
    \`isActive\`  TINYINT(1)   NOT NULL DEFAULT 1,
    \`createdAt\` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    \`updatedAt\` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (\`id\`),
    UNIQUE KEY \`HeatmapCountry_code_key\` (\`code\`),
    INDEX \`HeatmapCountry_isActive_idx\` (\`isActive\`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
`;

const CREATE_CATEGORY_TABLE = `
  CREATE TABLE IF NOT EXISTS \`HeatmapCategory\` (
    \`id\`        VARCHAR(191) NOT NULL,
    \`name\`      VARCHAR(191) NOT NULL,
    \`isActive\`  TINYINT(1)   NOT NULL DEFAULT 1,
    \`createdAt\` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    \`updatedAt\` DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (\`id\`),
    UNIQUE KEY \`HeatmapCategory_name_key\` (\`name\`),
    INDEX \`HeatmapCategory_isActive_idx\` (\`isActive\`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
`;

const SEED_COUNTRIES = [
  { id: "hc_ke", name: "Kenya",        code: "KE", centerLat:  0.0236,  centerLng: 37.9062, zoom: 6 },
  { id: "hc_et", name: "Ethiopia",     code: "ET", centerLat:  9.1450,  centerLng: 40.4897, zoom: 6 },
  { id: "hc_cd", name: "DR Congo",     code: "CD", centerLat: -4.0383,  centerLng: 21.7587, zoom: 5 },
  { id: "hc_ug", name: "Uganda",       code: "UG", centerLat:  1.3733,  centerLng: 32.2903, zoom: 7 },
  { id: "hc_tz", name: "Tanzania",     code: "TZ", centerLat: -6.3690,  centerLng: 34.8888, zoom: 6 },
  { id: "hc_rw", name: "Rwanda",       code: "RW", centerLat: -1.9403,  centerLng: 29.8739, zoom: 8 },
  { id: "hc_bi", name: "Burundi",      code: "BI", centerLat: -3.3731,  centerLng: 29.9189, zoom: 8 },
  { id: "hc_ss", name: "South Sudan",  code: "SS", centerLat:  6.8770,  centerLng: 31.3070, zoom: 6 },
  { id: "hc_so", name: "Somalia",      code: "SO", centerLat:  5.1521,  centerLng: 46.1996, zoom: 6 },
  { id: "hc_ng", name: "Nigeria",      code: "NG", centerLat:  9.0820,  centerLng:  8.6753, zoom: 6 },
  { id: "hc_gh", name: "Ghana",        code: "GH", centerLat:  7.9465,  centerLng: -1.0232, zoom: 7 },
  { id: "hc_za", name: "South Africa", code: "ZA", centerLat: -30.5595, centerLng: 22.9375, zoom: 5 },
  { id: "hc_eg", name: "Egypt",        code: "EG", centerLat: 26.8206,  centerLng: 30.8025, zoom: 6 },
  { id: "hc_cm", name: "Cameroon",     code: "CM", centerLat:  3.8480,  centerLng: 11.5021, zoom: 6 },
  { id: "hc_sd", name: "Sudan",        code: "SD", centerLat: 12.8628,  centerLng: 30.2176, zoom: 5 },
  { id: "hc_zm", name: "Zambia",       code: "ZM", centerLat: -13.1339, centerLng: 27.8493, zoom: 6 },
  { id: "hc_mz", name: "Mozambique",   code: "MZ", centerLat: -18.6657, centerLng: 35.5296, zoom: 6 },
  { id: "hc_zw", name: "Zimbabwe",     code: "ZW", centerLat: -19.0154, centerLng: 29.1549, zoom: 6 },
];

const SEED_CATEGORIES = [
  { id: "cat_health",      name: "Health" },
  { id: "cat_education",   name: "Education" },
  { id: "cat_security",    name: "Security & Conflict" },
  { id: "cat_climate",     name: "Climate & Environment" },
  { id: "cat_economic",    name: "Economic Development" },
  { id: "cat_policy",      name: "Policy & Governance" },
  { id: "cat_humanrights", name: "Human Rights" },
  { id: "cat_migration",   name: "Migration & Refugees" },
  { id: "cat_agriculture", name: "Agriculture & Food Security" },
  { id: "cat_infra",       name: "Infrastructure & Energy" },
  { id: "cat_tech",        name: "Technology & Innovation" },
  { id: "cat_gender",      name: "Gender & Social Affairs" },
];

export async function POST() {
  const session = await getServerSession(authOptions);
  if (!session || session.user.role !== "SUPER_ADMIN") {
    return NextResponse.json({ error: "Super Admin access required" }, { status: 403 });
  }

  try {
    await prisma.$executeRawUnsafe(CREATE_COUNTRY_TABLE);
    await prisma.$executeRawUnsafe(CREATE_CATEGORY_TABLE);

    const [{ count: countriesSeeded }, { count: categoriesSeeded }] = await Promise.all([
      prisma.heatmapCountry.createMany({ data: SEED_COUNTRIES, skipDuplicates: true }),
      prisma.heatmapCategory.createMany({ data: SEED_CATEGORIES, skipDuplicates: true }),
    ]);

    return NextResponse.json({ success: true, countriesSeeded, categoriesSeeded });
  } catch (err) {
    console.error("[heatmap/setup]", err);
    return NextResponse.json(
      { error: err instanceof Error ? err.message : "Setup failed" },
      { status: 500 }
    );
  }
}
