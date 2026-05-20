import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import { redirect } from "next/navigation";
import type { Session } from "next-auth";

export async function requireAuth(): Promise<Session> {
  const session = await getServerSession(authOptions);
  if (!session) redirect("/admin/login");
  return session;
}

export async function requireSuperAdmin(): Promise<Session> {
  const session = await requireAuth();
  if (session.user.role !== "SUPER_ADMIN") redirect("/admin/dashboard");
  return session;
}

export function isSuperAdmin(session: Session): boolean {
  return session?.user?.role === "SUPER_ADMIN";
}
