"use client";

import Image from "next/image";
import { useState } from "react";
import { formatDate } from "@/lib/format";

interface GalleryPost {
  id: string;
  title: string;
  description: string | null;
  thumbnailUrl: string | null;
  country: string;
  region: string;
  createdAt: Date;
}

export default function GalleryClient({ posts }: { posts: GalleryPost[] }) {
  const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

  function prev() {
    setLightboxIndex((i) => (i === null ? null : (i - 1 + posts.length) % posts.length));
  }

  function next() {
    setLightboxIndex((i) => (i === null ? null : (i + 1) % posts.length));
  }

  function close() {
    setLightboxIndex(null);
  }

  return (
    <>
      {/* Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        {posts.map((post, i) => (
          <div
            key={post.id}
            className="group cursor-pointer"
            onClick={() => setLightboxIndex(i)}
          >
            <div className="aspect-4/5 rounded-[2.5rem] overflow-hidden relative mb-4 transition-all duration-500 group-hover:shadow-2xl group-hover:translate-y-[-8px] group-hover:-rotate-1 bg-slate-100">
              {post.thumbnailUrl ? (
                <Image
                  src={post.thumbnailUrl}
                  alt={post.title}
                  fill
                  className="object-cover transition-transform duration-700 group-hover:scale-110"
                />
              ) : (
                <div className="absolute inset-0 premium-gradient opacity-20" />
              )}
              <div className="absolute inset-0 bg-black/45" />
              <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <div className="bg-primary/95 backdrop-blur-md text-white text-xs font-bold uppercase tracking-widest px-5 py-2.5 rounded-full shadow-xl">
                  View &rarr;
                </div>
              </div>
              <div className="absolute bottom-6 left-6">
                <div className="glass px-4 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest w-fit text-white backdrop-blur-xl border-white/30 bg-primary/30">
                  {post.country}
                </div>
              </div>
            </div>
            <h3 className="font-outfit font-bold text-lg group-hover:text-secondary transition-colors line-clamp-2">
              {post.title}
            </h3>
            <p className="text-xs text-slate-400 font-medium italic mt-1">
              {post.region} • {formatDate(post.createdAt)}
            </p>
          </div>
        ))}
      </div>

      {/* Full-screen Lightbox */}
      {lightboxIndex !== null && (
        <div
          className="fixed inset-0 z-60 bg-black/95 backdrop-blur-xl flex items-center justify-center p-4"
          onClick={close}
        >
          {/* Close */}
          <button
            onClick={close}
            aria-label="Close lightbox"
            className="absolute top-6 right-6 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white font-bold text-xl transition-colors z-10"
          >
            ✕
          </button>

          {/* Prev */}
          <button
            onClick={(e) => { e.stopPropagation(); prev(); }}
            aria-label="Previous image"
            className="absolute left-6 w-12 h-12 rounded-full bg-white/10 hover:bg-primary flex items-center justify-center text-white font-bold text-xl transition-colors z-10"
          >
            ‹
          </button>

          {/* Image */}
          <div
            className="relative w-full max-w-4xl max-h-[80vh] aspect-video"
            onClick={(e) => e.stopPropagation()}
          >
            {posts[lightboxIndex].thumbnailUrl ? (
              <Image
                src={posts[lightboxIndex].thumbnailUrl!}
                alt={posts[lightboxIndex].title}
                fill
                className="object-contain"
              />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-white/40 text-sm">
                No image available
              </div>
            )}
          </div>

          {/* Next */}
          <button
            onClick={(e) => { e.stopPropagation(); next(); }}
            aria-label="Next image"
            className="absolute right-6 w-12 h-12 rounded-full bg-white/10 hover:bg-primary flex items-center justify-center text-white font-bold text-xl transition-colors z-10"
          >
            ›
          </button>

          {/* Caption & Counter */}
          <div className="absolute bottom-8 left-0 right-0 text-center text-white px-4">
            <p className="font-outfit font-bold text-base">{posts[lightboxIndex].title}</p>
            {posts[lightboxIndex].description && (
              <p className="text-sm text-white/70 mt-1 max-w-xl mx-auto line-clamp-2">
                {posts[lightboxIndex].description}
              </p>
            )}
            <p className="text-xs text-white/40 mt-2">
              {lightboxIndex + 1} / {posts.length} • {posts[lightboxIndex].country}, {posts[lightboxIndex].region}
            </p>
          </div>

          {/* Thumbnail strip */}
          {posts.length > 1 && (
            <div className="absolute bottom-28 left-0 right-0 flex justify-center gap-2 px-4 overflow-x-auto">
              {posts.map((p, i) => (
                <button
                  key={p.id}
                  aria-label={`View ${p.title}`}
                  onClick={(e) => { e.stopPropagation(); setLightboxIndex(i); }}
                  className={`relative w-12 h-9 rounded-lg overflow-hidden border-2 shrink-0 transition-all ${
                    i === lightboxIndex
                      ? "border-secondary scale-110 shadow-lg"
                      : "border-white/20 hover:border-white/50"
                  }`}
                >
                  {p.thumbnailUrl && (
                    <Image src={p.thumbnailUrl} alt={p.title} fill className="object-cover" />
                  )}
                </button>
              ))}
            </div>
          )}
        </div>
      )}
    </>
  );
}
