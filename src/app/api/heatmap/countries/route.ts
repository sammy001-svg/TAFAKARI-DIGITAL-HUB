import { NextRequest, NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import prisma from "@/lib/prisma";

export const dynamic = "force-dynamic";

export async function GET() {
  try {
    const countries = await prisma.heatmapCountry.findMany({
      where: { isActive: true },
      orderBy: { name: "asc" },
      select: { id: true, name: true, code: true, centerLat: true, centerLng: true, zoom: true },
    });
    return NextResponse.json(countries);
  } catch {
    return NextResponse.json([]);
  }
}

export async function POST(req: NextRequest) {
  const session = await getServerSession(authOptions);
  if (!session || session.user.role !== "SUPER_ADMIN") {
    return NextResponse.json({ error: "Super Admin access required" }, { status: 403 });
  }

  const body = await req.json();
  const { name, code, centerLat, centerLng, zoom } = body;

  if (!name?.trim() || !code?.trim()) {
    return NextResponse.json({ error: "Name and code are required" }, { status: 400 });
  }
  if (typeof centerLat !== "number" || typeof centerLng !== "number") {
    return NextResponse.json({ error: "Valid center coordinates required" }, { status: 400 });
  }
  if (centerLat < -90 || centerLat > 90 || centerLng < -180 || centerLng > 180) {
    return NextResponse.json({ error: "Coordinates out of range" }, { status: 400 });
  }

  try {
    const country = await prisma.heatmapCountry.create({
      data: {
        name: name.trim(),
        code: code.trim().toUpperCase(),
        centerLat,
        centerLng,
        zoom: typeof zoom === "number" ? Math.min(Math.max(zoom, 3), 12) : 6,
      },
    });
    return NextResponse.json(country, { status: 201 });
  } catch (err: unknown) {
    if ((err as { code?: string })?.code === "P2002") {
      return NextResponse.json({ error: "A country with this code already exists" }, { status: 409 });
    }
    return NextResponse.json({ error: "Failed to create country" }, { status: 500 });
  }
}
