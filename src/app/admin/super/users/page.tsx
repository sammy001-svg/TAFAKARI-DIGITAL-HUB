export const dynamic = "force-dynamic";

import { requireSuperAdmin } from "@/lib/auth-helpers";
import prisma from "@/lib/prisma";
import UserManagementClient from "@/components/admin/UserManagementClient";

export default async function UserManagementPage() {
  const session = await requireSuperAdmin();

  const users = await prisma.user.findMany({
    select: {
      id: true,
      name: true,
      email: true,
      username: true,
      role: true,
      createdAt: true,
      _count: { select: { posts: true } },
    },
    orderBy: { createdAt: "desc" },
  });

  // Serialize dates for the client component
  const serializedUsers = users.map((u) => ({
    ...u,
    createdAt: u.createdAt.toISOString(),
  }));

  return (
    <div className="flex flex-col gap-8">
      <div>
        <h1 className="font-outfit text-3xl font-bold text-slate-900">User Management</h1>
        <p className="text-slate-500 mt-1 italic">Create, edit, and manage platform administrators</p>
      </div>

      <UserManagementClient
        initialUsers={serializedUsers}
        currentUserId={session.user.id}
      />
    </div>
  );
}
