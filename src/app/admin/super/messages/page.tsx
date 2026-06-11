export const dynamic = "force-dynamic";

import { requireSuperAdmin } from "@/lib/auth-helpers";
import prisma from "@/lib/prisma";
import MessagesClient from "./MessagesClient";

export default async function MessagesPage() {
  await requireSuperAdmin();

  const messages = await prisma.contactMessage.findMany({
    orderBy: [{ isRead: "asc" }, { createdAt: "desc" }],
  });

  const unreadCount = messages.filter((m) => !m.isRead).length;

  return (
    <div className="max-w-4xl mx-auto">
      <div className="mb-10 flex items-center justify-between">
        <div>
          <h1 className="font-outfit text-3xl font-bold">Contact Inbox</h1>
          <p className="text-slate-500 mt-1 italic">
            Messages submitted via the public contact form.
          </p>
        </div>
        <div className="flex items-center gap-3">
          <span className="px-4 py-2 rounded-2xl bg-slate-100 text-slate-600 text-sm font-bold">
            {messages.length} total
          </span>
          {unreadCount > 0 && (
            <span className="px-4 py-2 rounded-2xl bg-amber-100 text-amber-700 text-sm font-bold">
              {unreadCount} unread
            </span>
          )}
        </div>
      </div>

      <MessagesClient messages={messages} />
    </div>
  );
}
