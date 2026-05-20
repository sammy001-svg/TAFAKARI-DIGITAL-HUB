"use client";

import { useState, useEffect } from "react";
import Image from "next/image";

interface CarouselSlide {
  id: number;
  title: string;
  description: string;
  image: string;
  link: string;
  linkText: string;
}

const slides: CarouselSlide[] = [
  {
    id: 1,
    title: "Reflecting on Regional Issues",
    description: "Deep insights into urban development and regional growth across Kenya, Ethiopia, and the DRC.",
    image: "/nairobi_sunset_hero.png",
    link: "/heatmap",
    linkText: "Explore Heatmap"
  },
  {
    id: 2,
    title: "Empowering Local Voices",
    description: "Centering community narratives and fostering dialogue across regional borders.",
    image: "/ethiopia_community_hero.png",
    link: "/podcasts",
    linkText: "Listen to Stories"
  },
  {
    id: 3,
    title: "Digital Data for Regional Impact",
    description: "A centralized repository for knowledge sharing and field documentation in the Great Lakes region.",
    image: "/drc_nature_hero.png",
    link: "/documents",
    linkText: "View Library"
  }
];

export default function HomeCarousel() {
  const [current, setCurrent] = useState(0);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrent((prev) => (prev + 1) % slides.length);
    }, 6500);
    return () => clearInterval(timer);
  }, []);

  return (
    <div className="relative w-full h-[85vh] md:h-[90vh] overflow-hidden group">
      {slides.map((slide, index) => (
        <div
          key={slide.id}
          className={`absolute inset-0 transition-all duration-1000 ease-in-out ${
            index === current ? "opacity-100 scale-100 z-10" : "opacity-0 scale-105 pointer-events-none z-0"
          }`}
        >
          {/* Solid Dark Overlay (No Gradients) */}
          <div className="absolute inset-0 bg-black/60 z-10" />
          
          <Image
            src={slide.image}
            alt={slide.title}
            fill
            className="object-cover"
            priority={index === 0}
          />

          <div className="absolute inset-0 z-20 flex flex-col items-center justify-center text-center px-6">
            <div className="max-w-4xl space-y-6">
              <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-secondary/20 backdrop-blur-md text-secondary text-xs font-bold uppercase tracking-widest border border-secondary/30 animate-in fade-in slide-in-from-top-4 duration-1000">
                <span className="flex h-2 w-2 rounded-full bg-secondary animate-pulse"></span>
                Tafakari Regional Hub
              </div>
              
              <h1 className="font-outfit font-black text-5xl md:text-8xl text-white uppercase tracking-tighter leading-[0.9] drop-shadow-2xl animate-in fade-in slide-in-from-bottom-8 duration-1000 delay-200">
                {slide.title}
              </h1>
              
              <p className="text-slate-200 text-lg md:text-xl max-w-2xl mx-auto leading-relaxed drop-shadow-md animate-in fade-in slide-in-from-bottom-6 duration-1000 delay-500">
                {slide.description}
              </p>
              
              <div className="flex flex-col sm:flex-row gap-4 justify-center pt-8 animate-in fade-in slide-in-from-bottom-4 duration-1000 delay-700">
                <a
                  href={slide.link}
                  className="bg-primary text-white px-10 py-5 rounded-full font-black text-sm uppercase tracking-widest hover:opacity-90 transition-all shadow-2xl hover:scale-105 hover:translate-y-[-2px] border border-secondary/30"
                >
                  {slide.linkText}
                </a>
                <a
                  href="/about"
                  className="bg-white/10 backdrop-blur-md text-white border border-white/20 px-10 py-5 rounded-full font-black text-sm uppercase tracking-widest hover:bg-white/20 transition-all"
                >
                  Our Mission
                </a>
              </div>
            </div>
          </div>
        </div>
      ))}

      {/* Modern Navigation Overlays */}
      <div className="absolute bottom-12 left-0 right-0 z-30 flex flex-col items-center gap-6">
        <div className="flex gap-3">
          {slides.map((_, index) => (
            <button
              key={index}
              onClick={() => setCurrent(index)}
              aria-label={`Go to slide ${index + 1}`}
              className={`h-1.5 rounded-full transition-all duration-500 overflow-hidden ${
                index === current ? "w-12 bg-white" : "w-3 bg-white/30 hover:bg-white/50"
              }`}
            >
              <div 
                className={`h-full bg-secondary transition-all duration-[6500ms] ease-linear rounded-full ${
                  index === current ? "w-full" : "w-0"
                }`}
              />
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
