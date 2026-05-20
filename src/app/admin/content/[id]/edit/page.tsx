export const dynamic = "force-dynamic";

import { notFound, redirect } from "next/navigation";
import { requireAuth, isSuperAdmin } from "@/lib/auth-helpers";
import prisma from "@/lib/prisma";
import EditContentForm from "@/components/admin/EditContentForm";

export default async function EditContentPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const session = await requireAuth();
  const { id } = await params;

  const post = await prisma.post.findUnique({ where: { id } });
  if (!post) notFound();

  const isSuper = isSuperAdmin(session);

  // ADMIN can only edit their own posts
  if (!isSuper && post.authorId !== session.user.id) redirect("/admin/content");

  // ADMIN cannot edit published/archived posts
  if (!isSuper && (post.status === "PUBLISHED" || post.status === "ARCHIVED")) {
    redirect("/admin/content");
  }

  const canSubmit =
    isSuper || (post.status === "DRAFT" || post.status === "REJECTED");

  return (
    <EditContentForm
      post={{
        id: post.id,
        title: post.title,
        content: post.content,
        description: post.description,
        type: post.type,
        status: post.status,
        country: post.country,
        region: post.region,
        issueCategory: post.issueCategory,
        thumbnailUrl: post.thumbnailUrl,
        mediaUrl: post.mediaUrl,
      }}
      canSubmit={canSubmit}
    />
  );
}
