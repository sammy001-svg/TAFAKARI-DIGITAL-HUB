import { NextRequest, NextResponse } from "next/server";
import prisma from "@/lib/prisma";

export const dynamic = "force-dynamic";

export async function POST(req: NextRequest) {
  const body = await req.json().catch(() => null);
  if (!body) return NextResponse.json({ error: "Invalid JSON" }, { status: 400 });

  const { fullName, email, country, interest, message } = body;

  if (!fullName?.trim() || !email?.trim() || !message?.trim()) {
    return NextResponse.json(
      { error: "fullName, email, and message are required" },
      { status: 400 }
    );
  }

  const record = await prisma.contactMessage.create({
    data: {
      fullName: fullName.trim().slice(0, 200),
      email: email.trim().slice(0, 200),
      country: (country ?? "").trim().slice(0, 100),
      interest: (interest ?? "").trim().slice(0, 100),
      message: message.trim().slice(0, 5000),
    },
  });

  return NextResponse.json({ data: record }, { status: 201 });
}
