export const dynamic = "force-dynamic";

import { notFound } from "next/navigation";
import { requireAuth } from "@/lib/auth-helpers";
import prisma from "@/lib/prisma";
import ProfileForm from "@/components/admin/ProfileForm";

export default async function ProfilePage() {
  const session = await requireAuth();

  const user = await prisma.user.findUnique({
    where: { id: session.user.id },
    select: { id: true, name: true, email: true, username: true, role: true, createdAt: true },
  });

  if (!user) notFound();

  return (
    <div className="flex flex-col gap-8">
      <div>
        <h1 className="font-outfit text-3xl font-bold text-slate-900">My Profile</h1>
        <p className="text-slate-500 mt-1 italic">Manage your account information and password</p>
      </div>
      <ProfileForm
        user={{
          id: user.id,
          name: user.name,
          email: user.email,
          username: user.username,
          role: user.role,
          createdAt: user.createdAt.toISOString(),
        }}
      />
    </div>
  );
}
