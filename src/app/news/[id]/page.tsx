export const dynamic = "force-dynamic";

import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import Image from "next/image";
import prisma from "@/lib/prisma";
import { formatDate, formatNumber } from "@/lib/format";
import { markdownToHtml } from "@/lib/markdown";
import CommentForm from "./CommentForm";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ id: string }>;
}): Promise<Metadata> {
  const { id } = await params;
  const post = await prisma.post.findFirst({
    where: { id, status: "PUBLISHED", type: "ARTICLE" },
    select: { title: true, description: true, thumbnailUrl: true, country: true, issueCategory: true },
  });

  if (!post) return { title: "Article Not Found | Tafakari Hub" };

  return {
    title: `${post.title} | Tafakari Hub`,
    description: post.description ?? `${post.issueCategory} report from ${post.country} — Tafakari Digital Hub.`,
    openGraph: {
      title: post.title,
      description: post.description ?? undefined,
      images: post.thumbnailUrl ? [{ url: post.thumbnailUrl }] : [],
      type: "article",
    },
    twitter: {
      card: "summary_large_image",
      title: post.title,
      description: post.description ?? undefined,
      images: post.thumbnailUrl ? [post.thumbnailUrl] : [],
    },
  };
}

export default async function ArticlePage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const post = await prisma.post.findFirst({
    where: { id, status: "PUBLISHED", type: "ARTICLE" },
    include: {
      author: { select: { name: true, username: true } },
      comments: {
        where: { isModerated: true, isFlagged: false },
        orderBy: { createdAt: "desc" },
        select: { id: true, name: true, content: true, createdAt: true },
      },
    },
  });

  if (!post) notFound();

  // Increment view count — fire and forget so it never blocks the render
  prisma.post
    .update({ where: { id }, data: { viewCount: { increment: 1 } } })
    .catch(() => {});

  const authorName = post.author.name ?? post.author.username ?? "Tafakari Team";

  return (
    <div className="max-w-4xl mx-auto px-6 py-12">
      {/* Breadcrumb */}
      <nav className="flex items-center gap-2 text-[10px] text-slate-400 mb-8 uppercase tracking-widest font-bold">
        <Link href="/news" className="hover:text-primary transition-colors">
          News
        </Link>
        <span>›</span>
        <span className="text-slate-600">{post.issueCategory}</span>
      </nav>

      {/* Badges */}
      <div className="flex gap-3 mb-5">
        <span className="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-widest">
          {post.country}
        </span>
        <span className="px-3 py-1 rounded-full bg-secondary/15 text-secondary text-[10px] font-bold uppercase tracking-widest">
          {post.issueCategory}
        </span>
      </div>

      {/* Title */}
      <h1 className="font-outfit text-4xl md:text-5xl font-bold leading-tight mb-5">
        {post.title}
      </h1>

      {/* Description */}
      {post.description && (
        <p className="text-xl text-slate-500 leading-relaxed mb-6">{post.description}</p>
      )}

      {/* Meta row */}
      <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-400 pb-8 border-b border-slate-100">
        <span>
          By <strong className="text-slate-700">{authorName}</strong>
        </span>
        <span>•</span>
        <span>{formatDate(post.createdAt)}</span>
        <span>•</span>
        <span>{post.region}</span>
        <span>•</span>
        <span>{formatNumber(post.viewCount)} views</span>
      </div>

      {/* Hero image */}
      {post.thumbnailUrl && (
        <div className="relative h-72 md:h-112 rounded-[2.5rem] overflow-hidden my-10 border border-slate-200">
          <Image
            src={post.thumbnailUrl}
            alt={post.title}
            fill
            className="object-cover"
            priority
          />
        </div>
      )}

      {/* Content body */}
      {post.content ? (
        <div
          className="text-slate-700 text-base mb-16"
          dangerouslySetInnerHTML={{ __html: markdownToHtml(post.content) }}
        />
      ) : (
        <div className="py-8 text-slate-400 italic text-sm mb-16">
          Full article content will be available soon.
        </div>
      )}

      {/* Media embed (video or audio link) */}
      {post.mediaUrl && (
        <div className="mb-16 p-6 bg-slate-50 rounded-3xl border border-slate-100 flex items-center gap-5">
          <div className="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center text-white font-bold text-lg shrink-0">
            ▶
          </div>
          <div>
            <p className="font-semibold text-slate-800 text-sm">Related Media</p>
            <a
              href={post.mediaUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="text-secondary text-sm font-bold hover:underline"
            >
              Open &rarr;
            </a>
          </div>
        </div>
      )}

      {/* Comments section */}
      <div className="border-t border-slate-100 pt-12 mt-4">
        <h2 className="font-outfit font-bold text-2xl mb-8">
          {post.comments.length > 0
            ? `${post.comments.length} Comment${post.comments.length !== 1 ? "s" : ""}`
            : "Leave a Comment"}
        </h2>

        <CommentForm postId={post.id} />

        {post.comments.length > 0 && (
          <div className="mt-12 flex flex-col gap-5">
            {post.comments.map((c) => (
              <div
                key={c.id}
                className="p-6 bg-slate-50 rounded-3xl border border-slate-100"
              >
                <div className="flex items-center gap-3 mb-3">
                  <div className="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm shrink-0">
                    {(c.name ?? "A")[0].toUpperCase()}
                  </div>
                  <div>
                    <p className="font-semibold text-slate-800 text-sm">
                      {c.name ?? "Anonymous"}
                    </p>
                    <p className="text-[10px] text-slate-400 uppercase tracking-wide">
                      {formatDate(c.createdAt)}
                    </p>
                  </div>
                </div>
                <p className="text-slate-600 text-sm leading-relaxed">{c.content}</p>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Back link */}
      <div className="mt-16 pt-8 border-t border-slate-100">
        <Link
          href="/news"
          className="text-sm font-bold text-slate-400 hover:text-primary transition-colors uppercase tracking-widest"
        >
          &larr; Back to News
        </Link>
      </div>
    </div>
  );
}
