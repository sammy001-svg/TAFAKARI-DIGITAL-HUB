"use client";

import Image from "next/image";
import { useState } from "react";

// Each album has its own array of images. We use the 8 available images cleverly.
const albums = [
  {
    id: 1,
    title: "Nairobi Field Work",
    country: "Kenya",
    region: "Nairobi",
    date: "Jan 2026",
    image: "/gallery_nairobi.png",
    images: [
      { src: "/gallery_nairobi.png", caption: "Community survey in Nairobi CBD" },
      { src: "/gallery_mombasa.png", caption: "Youth engagement session, Coast" },
      { src: "/gallery_featured_1.png", caption: "Aerial view of Rift Valley settlements" },
      { src: "/gallery_featured_4.png", caption: "Children at community learning center" },
    ],
  },
  {
    id: 2,
    title: "Addis Educational Series",
    country: "Ethiopia",
    region: "Addis Ababa",
    date: "Feb 2026",
    image: "/gallery_addis.png",
    images: [
      { src: "/gallery_addis.png", caption: "Classroom session, Addis primary school" },
      { src: "/gallery_featured_2.png", caption: "Community leader portrait, Addis" },
      { src: "/gallery_featured_1.png", caption: "Rural education outreach, Oromia Region" },
    ],
  },
  {
    id: 3,
    title: "Goma Community Outreach",
    country: "DRC",
    region: "Goma",
    date: "Mar 2026",
    image: "/gallery_goma.png",
    images: [
      { src: "/gallery_goma.png", caption: "Community assembly, Goma outskirts" },
      { src: "/gallery_featured_3.png", caption: "Congo River fishing communities, dusk" },
      { src: "/gallery_featured_2.png", caption: "Women's cooperative leader, Bukavu" },
      { src: "/gallery_nairobi.png", caption: "Cross-border humanitarian meetup" },
    ],
  },
  {
    id: 4,
    title: "Mombasa Youth Summit",
    country: "Kenya",
    region: "Mombasa",
    date: "Dec 2025",
    image: "/gallery_mombasa.png",
    images: [
      { src: "/gallery_mombasa.png", caption: "Opening ceremony, Mombasa Youth Summit" },
      { src: "/gallery_featured_4.png", caption: "Panel discussion, youth policy forum" },
      { src: "/gallery_nairobi.png", caption: "Networking session, coastal delegates" },
      { src: "/gallery_featured_1.png", caption: "Sunrise yoga & wellness, beach front" },
    ],
  },
];

const featuredImages = [
  { src: "/gallery_featured_1.png", alt: "Rural African village at sunrise, Kenya" },
  { src: "/gallery_featured_2.png", alt: "Ethiopian woman in traditional dress" },
  { src: "/gallery_featured_3.png", alt: "Congo River at dusk with fishing boats" },
  { src: "/gallery_featured_4.png", alt: "Children at a community center, Kenya" },
];

type Album = typeof albums[0];

export default function GalleryPage() {
  const [selectedAlbum, setSelectedAlbum] = useState<Album | null>(null);
  const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

  const openAlbum = (album: Album) => {
    setSelectedAlbum(album);
    setLightboxIndex(null);
  };

  const closeAlbum = () => {
    setSelectedAlbum(null);
    setLightboxIndex(null);
  };

  const openLightbox = (index: number) => setLightboxIndex(index);
  const closeLightbox = () => setLightboxIndex(null);

  const prevImage = () => {
    if (lightboxIndex === null || !selectedAlbum) return;
    setLightboxIndex((lightboxIndex - 1 + selectedAlbum.images.length) % selectedAlbum.images.length);
  };

  const nextImage = () => {
    if (lightboxIndex === null || !selectedAlbum) return;
    setLightboxIndex((lightboxIndex + 1) % selectedAlbum.images.length);
  };

  return (
    <div className="max-w-7xl mx-auto px-6 py-12">
      {/* Header */}
      <div className="flex flex-col gap-4 mb-16">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/15 text-secondary text-sm font-semibold w-fit border border-secondary/20">
          <span className="flex h-2 w-2 rounded-full bg-secondary animate-pulse"></span>
          Visual Stories
        </div>
        <h1 className="font-outfit text-4xl md:text-5xl font-bold">Photo Gallery</h1>
        <p className="text-slate-500 max-w-xl">
          Click any album to browse all images from that region. Documenting field activities, community life, and regional events.
        </p>
      </div>

      {/* Album Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        {albums.map((album) => (
          <div key={album.id} className="group cursor-pointer" onClick={() => openAlbum(album)}>
            <div className="aspect-4/5 rounded-[2.5rem] overflow-hidden relative mb-4 transition-all duration-500 group-hover:shadow-2xl group-hover:translate-y-[-8px] group-hover:-rotate-1">
              <Image src={album.image} alt={album.title} fill className="object-cover transition-transform duration-700 group-hover:scale-110" />
              <div className="absolute inset-0 bg-black/45"></div>
              {/* Photos count badge */}
              <div className="absolute top-4 right-4 bg-white/20 backdrop-blur-md text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full border border-white/20">
                {album.images.length} Photos
              </div>
              {/* Click hint */}
              <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <div className="bg-primary/95 backdrop-blur-md text-white text-xs font-bold uppercase tracking-widest px-5 py-2.5 rounded-full shadow-xl">
                  View Album →
                </div>
              </div>
              {/* Country badge */}
              <div className="absolute bottom-6 left-6">
                <div className="glass px-4 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest w-fit text-white backdrop-blur-xl border-white/30 bg-primary/30">
                  {album.country}
                </div>
              </div>
            </div>
            <h3 className="font-outfit font-bold text-lg group-hover:text-secondary transition-colors">{album.title}</h3>
            <p className="text-xs text-slate-400 font-medium italic mt-1">{album.region} • {album.date}</p>
          </div>
        ))}
      </div>

      {/* Featured Large View */}
      <div className="mt-24 p-12 glass rounded-[3rem] border-secondary/20 flex flex-col lg:flex-row gap-12 items-center text-white">
        <div className="flex-1 space-y-6">
          <h2 className="font-outfit text-3xl font-bold">Latest field documentation</h2>
          <p className="text-white/75 leading-relaxed">
            Our teams routinely document sub-national developments to provide visual context to our data-driven reports.
            All items are vetted for accuracy and local sensitivity before publication.
          </p>
          <button className="bg-white text-primary px-8 py-3 rounded-xl font-bold hover:bg-slate-100 transition-all shadow-xl">
            View Featured Album
          </button>
        </div>
        <div className="flex-1 grid grid-cols-2 gap-4 w-full">
          {featuredImages.map((img, i) => (
            <div key={i} className="h-44 rounded-2xl overflow-hidden relative group">
              <Image src={img.src} alt={img.alt} fill className="object-cover transition-transform duration-700 group-hover:scale-110" />
              <div className="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
            </div>
          ))}
        </div>
      </div>

      {/* ── Album Popup Modal ── */}
      {selectedAlbum && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8 bg-black/80 backdrop-blur-sm animate-fadeIn"
          onClick={closeAlbum}
        >
          <div
            className="relative bg-white rounded-4xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Modal Header */}
            <div className="sticky top-0 z-10 bg-white/90 backdrop-blur-xl rounded-t-4xl px-8 py-6 border-b border-slate-100 flex items-start justify-between">
              <div>
                <span className="text-[10px] uppercase tracking-widest font-black text-primary bg-secondary/15 px-3 py-1 rounded-full">
                  {selectedAlbum.country} • {selectedAlbum.region}
                </span>
                <h2 className="font-outfit font-black text-2xl mt-2 text-slate-900">{selectedAlbum.title}</h2>
                <p className="text-slate-400 text-sm mt-1">{selectedAlbum.images.length} photos • {selectedAlbum.date}</p>
              </div>
              <button
                onClick={closeAlbum}
                className="w-10 h-10 rounded-full bg-slate-100 hover:bg-red-100 hover:text-red-600 flex items-center justify-center text-slate-500 transition-colors font-bold text-lg shrink-0 mt-1"
              >
                ✕
              </button>
            </div>

            {/* Image Grid */}
            <div className="p-8 grid grid-cols-2 md:grid-cols-3 gap-4">
              {selectedAlbum.images.map((img, i) => (
                <div
                  key={i}
                  className="group relative aspect-4/3 rounded-2xl overflow-hidden cursor-zoom-in shadow-md hover:shadow-xl transition-all duration-300"
                  onClick={() => openLightbox(i)}
                >
                  <Image src={img.src} alt={img.caption} fill className="object-cover transition-transform duration-500 group-hover:scale-110" />
                  <div className="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
                    <span className="text-white text-xs font-semibold">{img.caption}</span>
                  </div>
                  <div className="absolute top-3 right-3 w-8 h-8 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <span className="text-white text-xs">⛶</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* ── Full-screen Lightbox ── */}
      {selectedAlbum && lightboxIndex !== null && (
        <div
          className="fixed inset-0 z-60 bg-black/95 backdrop-blur-xl flex items-center justify-center p-4"
          onClick={closeLightbox}
        >
          {/* Close */}
          <button
            onClick={closeLightbox}
            className="absolute top-6 right-6 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white font-bold text-xl transition-colors z-10"
          >
            ✕
          </button>

          {/* Prev */}
          <button
            onClick={(e) => { e.stopPropagation(); prevImage(); }}
            className="absolute left-6 w-12 h-12 rounded-full bg-white/10 hover:bg-primary flex items-center justify-center text-white font-bold text-xl transition-colors z-10"
          >
            ‹
          </button>

          {/* Image */}
          <div className="relative w-full max-w-4xl max-h-[80vh] aspect-video" onClick={(e) => e.stopPropagation()}>
            <Image
              src={selectedAlbum.images[lightboxIndex].src}
              alt={selectedAlbum.images[lightboxIndex].caption}
              fill
              className="object-contain"
            />
          </div>

          {/* Next */}
          <button
            onClick={(e) => { e.stopPropagation(); nextImage(); }}
            className="absolute right-6 w-12 h-12 rounded-full bg-white/10 hover:bg-primary flex items-center justify-center text-white font-bold text-xl transition-colors z-10"
          >
            ›
          </button>

          {/* Caption & Counter */}
          <div className="absolute bottom-8 left-0 right-0 text-center text-white">
            <p className="text-sm font-medium text-white/80">{selectedAlbum.images[lightboxIndex].caption}</p>
            <p className="text-xs text-white/40 mt-1">{lightboxIndex + 1} / {selectedAlbum.images.length}</p>
          </div>

          {/* Thumbnail strip */}
          <div className="absolute bottom-20 left-0 right-0 flex justify-center gap-2 px-4">
            {selectedAlbum.images.map((img, i) => (
              <button
                key={i}
                aria-label={`View image ${i + 1}: ${img.caption}`}
                onClick={(e) => { e.stopPropagation(); setLightboxIndex(i); }}
                className={`relative w-12 h-9 rounded-lg overflow-hidden border-2 transition-all ${i === lightboxIndex ? "border-secondary scale-110 shadow-lg" : "border-white/20 hover:border-white/50"}`}
              >
                <Image src={img.src} alt={img.caption} fill className="object-cover" />
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
