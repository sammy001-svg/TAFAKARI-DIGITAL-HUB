"use client";

import { useState, useEffect } from "react";
import { signIn } from "next-auth/react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import Image from "next/image";

const CAROUSEL_SLIDES = [
  {
    image: "/gallery_featured_1.png",
    title: "Data-Driven Insight",
    subtitle: "Real-time intelligence from Kenya, Ethiopia & DRC",
    tag: "Regional Intelligence",
  },
  {
    image: "/gallery_nairobi.png",
    title: "Field Reporting",
    subtitle: "Community-led documentation from Nairobi to Goma",
    tag: "Field Operations",
  },
  {
    image: "/gallery_addis.png",
    title: "Education & Reform",
    subtitle: "Empowering communities through knowledge and access",
    tag: "Social Impact",
  },
  {
    image: "/gallery_goma.png",
    title: "Peace & Stability",
    subtitle: "Monitoring, reporting, and advocating for lasting peace",
    tag: "Security Watch",
  },
];

export default function AdminLogin() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [slide, setSlide] = useState(0);
  const router = useRouter();

  // Auto-advance carousel
  useEffect(() => {
    const timer = setInterval(() => {
      setSlide((s) => (s + 1) % CAROUSEL_SLIDES.length);
    }, 4000);
    return () => clearInterval(timer);
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError("");
    try {
      const res = await signIn("credentials", { username, password, redirect: false });
      if (res?.error) {
        setError("Invalid username or password");
      } else {
        router.push("/admin/dashboard");
      }
    } catch {
      setError("An error occurred. Please try again.");
    } finally {
      setIsLoading(false);
    }
  };

  const current = CAROUSEL_SLIDES[slide];

  return (
    <div className="min-h-screen flex overflow-hidden bg-slate-950">

      {/* ── LEFT: Marketing Carousel ── */}
      <div className="hidden lg:flex relative flex-1 overflow-hidden">
        {/* Background image with smooth crossfade */}
        {CAROUSEL_SLIDES.map((s, i) => (
          <div
            key={i}
            className={`absolute inset-0 transition-opacity duration-1000 ${i === slide ? "opacity-100" : "opacity-0"}`}
          >
            <Image src={s.image} alt={s.title} fill className="object-cover" priority={i === 0} />
          </div>
        ))}

        {/* Solid dark overlay */}
        <div className="absolute inset-0 bg-[#1F0404]/80 z-10" />

        {/* Content */}
        <div className="relative z-20 flex flex-col justify-between p-14 w-full">
          {/* Logo */}
          <Link href="/" className="flex items-center gap-3 w-fit">
            <div className="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg">
              T
            </div>
            <span className="font-outfit font-bold text-xl tracking-tight text-white">Tafakari Hub</span>
          </Link>

          {/* Slide text */}
          <div className="space-y-5 max-w-lg">
            <span className="inline-block text-[11px] font-black uppercase tracking-widest text-secondary bg-secondary/15 border border-secondary/20 px-3 py-1 rounded-full">
              {current.tag}
            </span>
            <h2 className="font-outfit font-black text-4xl xl:text-5xl text-white leading-tight">
              {current.title}
            </h2>
            <p className="text-slate-300 text-lg leading-relaxed">{current.subtitle}</p>

            {/* Dot indicators */}
            <div className="flex gap-2 pt-4">
              {CAROUSEL_SLIDES.map((_, i) => (
                <button
                  key={i}
                  aria-label={`Go to slide ${i + 1}`}
                  onClick={() => setSlide(i)}
                  className={`h-1.5 rounded-full transition-all duration-500 ${
                    i === slide ? "w-8 bg-secondary" : "w-2 bg-white/30 hover:bg-white/50"
                  }`}
                />
              ))}
            </div>
          </div>

          {/* Bottom tagline */}
          <p className="text-slate-500 text-xs">
            © 2026 Tafakari Digital Hub. Serving Kenya, Ethiopia & the DRC.
          </p>
        </div>
      </div>

      {/* ── RIGHT: Login Form ── */}
      <div className="flex flex-1 items-center justify-center px-6 py-12 bg-slate-950 relative overflow-hidden">
        {/* Subtle background glow */}
        <div className="absolute top-[-20%] right-[-20%] w-[60%] h-[60%] bg-secondary/10 rounded-full blur-[100px] pointer-events-none" />
        <div className="absolute bottom-[-20%] left-[-20%] w-[60%] h-[60%] bg-white/5 rounded-full blur-[100px] pointer-events-none" />

        <div className="w-full max-w-md z-10">
          {/* Mobile logo (shown on small screens) */}
          <div className="flex items-center gap-2 mb-10 lg:hidden">
            <div className="w-9 h-9 bg-primary rounded-xl flex items-center justify-center text-white font-bold">T</div>
            <span className="font-outfit font-bold text-lg text-white">Tafakari Hub</span>
          </div>

          {/* Form header */}
          <div className="mb-10">
            <h1 className="font-outfit text-3xl font-bold text-white">Welcome back</h1>
            <p className="text-slate-400 mt-2 text-sm">Sign in to access the editorial portal.</p>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-5">
            {error && (
              <div className="bg-rose-500/10 border border-rose-500/20 text-rose-400 px-4 py-3 rounded-xl text-sm text-center">
                {error}
              </div>
            )}

            <div className="space-y-2">
              <label className="text-xs font-semibold text-slate-400 uppercase tracking-widest">
                Username
              </label>
              <input
                type="text"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                required
                className="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-secondary/50 focus:border-secondary/50 transition-all"
                placeholder="Enter your identifier"
              />
            </div>

            <div className="space-y-2">
              <div className="flex justify-between items-center">
                <label className="text-xs font-semibold text-slate-400 uppercase tracking-widest">
                  Password
                </label>
                <span className="text-xs text-secondary hover:text-secondary/80 cursor-pointer transition-colors">
                  Forgot password?
                </span>
              </div>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                className="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-secondary/50 focus:border-secondary/50 transition-all"
                placeholder="••••••••••••"
              />
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className="w-full btn-primary py-4 mt-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 rounded-xl font-bold text-sm uppercase tracking-widest"
            >
              {isLoading ? (
                <>
                  <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  Authenticating...
                </>
              ) : (
                "Sign In to Dashboard →"
              )}
            </button>
          </form>

          {/* Footer note */}
          <div className="mt-10 space-y-4 text-center">
            <p className="text-slate-600 text-xs">
              Protected by regional access controls. All login events are audited.
            </p>
            <Link href="/" className="text-slate-500 text-sm hover:text-secondary transition-colors inline-block">
              ← Return to Public Portal
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
