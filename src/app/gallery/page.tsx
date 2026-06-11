export const dynamic = "force-dynamic";

import prisma from "@/lib/prisma";
import GalleryClient from "./GalleryClient";

export default async function GalleryPage() {
  const posts = await prisma.post.findMany({
    where: { type: "GALLERY_IMAGE", status: "PUBLISHED" },
    orderBy: { createdAt: "desc" },
    select: {
      id: true,
      title: true,
      description: true,
      thumbnailUrl: true,
      country: true,
      region: true,
      createdAt: true,
    },
    take: 48,
  });

  return (
    <div className="max-w-7xl mx-auto px-6 py-12">
      <div className="flex flex-col gap-4 mb-16">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/15 text-secondary text-sm font-semibold w-fit border border-secondary/20">
          <span className="flex h-2 w-2 rounded-full bg-secondary animate-pulse"></span>
          Visual Stories
        </div>
        <h1 className="font-outfit text-4xl md:text-5xl font-bold">Photo Gallery</h1>
        <p className="text-slate-500 max-w-xl">
          Click any image to view it full screen. Documenting field activities, community life, and regional events.
        </p>
      </div>

      {posts.length === 0 ? (
        <div className="text-center py-32 text-slate-400">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-2xl">🖼</div>
          <p className="text-lg font-semibold text-slate-500">No gallery images published yet.</p>
          <p className="text-sm mt-2">Field photography will appear here once approved.</p>
        </div>
      ) : (
        <GalleryClient posts={posts} />
      )}
    </div>
  );
}
