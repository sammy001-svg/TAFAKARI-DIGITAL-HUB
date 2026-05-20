"use client";

import Link from "next/link";
import { useState } from "react";

const NAV_LINKS = [
  { href: "/", label: "Home" },
  { href: "/heatmap", label: "Heatmap" },
  { href: "/gallery", label: "Gallery" },
  { href: "/news", label: "News" },
  { href: "/podcasts", label: "Podcasts" },
  { href: "/videos", label: "Videos" },
  { href: "/documents", label: "Documents" },
];

export default function Navbar() {
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <nav className="sticky top-0 z-50 bg-primary/95 backdrop-blur-md text-white shadow-xl px-6 md:px-12 py-5 border-b border-secondary/20">
      <div className="flex items-center justify-between">
        {/* Logo */}
        <div className="flex items-center gap-2">
          <div className="w-10 h-10 premium-gradient rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg">
            T
          </div>
          <span className="font-outfit font-bold text-xl tracking-tight hidden md:block">
            Tafakari Hub
          </span>
        </div>

        {/* Desktop Links */}
        <div className="hidden md:flex items-center gap-8 font-medium text-sm">
          {NAV_LINKS.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              className="hover:text-secondary transition-colors"
            >
              {link.label}
            </Link>
          ))}
        </div>

        {/* Desktop CTA */}
        <div className="hidden md:flex items-center gap-4">
          <Link
            href="/login"
            className="text-xs font-bold text-white bg-secondary/80 hover:bg-secondary transition-colors px-4 py-2 rounded-lg"
          >
            Login
          </Link>
          <Link href="/contact">
            <button className="btn-primary py-2 px-5 text-sm">
              Get Involved
            </button>
          </Link>
        </div>

        {/* Hamburger Button (mobile only) */}
        <button
          id="navbar-hamburger"
          onClick={() => setMenuOpen((o) => !o)}
          className="md:hidden flex flex-col gap-1.5 p-2 rounded-lg hover:bg-white/10 transition-colors"
          aria-label="Toggle menu"
        >
          <span
            className={`block w-6 h-0.5 bg-white transition-all duration-300 ${menuOpen ? "rotate-45 translate-y-2" : ""}`}
          />
          <span
            className={`block w-6 h-0.5 bg-white transition-all duration-300 ${menuOpen ? "opacity-0" : ""}`}
          />
          <span
            className={`block w-6 h-0.5 bg-white transition-all duration-300 ${menuOpen ? "-rotate-45 -translate-y-2" : ""}`}
          />
        </button>
      </div>

      {/* Mobile Dropdown */}
      {menuOpen && (
        <div className="md:hidden mt-4 border-t border-secondary/20 pt-4 flex flex-col gap-3 pb-4">
          {NAV_LINKS.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              onClick={() => setMenuOpen(false)}
              className="text-sm font-medium hover:text-secondary transition-colors py-1"
            >
              {link.label}
            </Link>
          ))}
          <div className="flex gap-3 mt-2 pt-3 border-t border-secondary/20">
            <Link
              href="/login"
              onClick={() => setMenuOpen(false)}
              className="flex-1 text-center text-xs font-bold text-white bg-secondary/80 hover:bg-secondary transition-colors px-4 py-2 rounded-lg"
            >
              Login
            </Link>
            <Link
              href="/contact"
              onClick={() => setMenuOpen(false)}
              className="flex-1 text-center btn-primary py-2 px-4 text-xs"
            >
              Get Involved
            </Link>
          </div>
        </div>
      )}
    </nav>
  );
}
