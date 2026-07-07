"use client";

import Link from "next/link";
import { useState } from "react";
import { useLanguage } from "@/components/providers/LanguageProvider";
import { LOCALES, LOCALE_LABELS } from "@/lib/i18n/translations";

export default function Navbar() {
  const { locale, setLocale, t } = useLanguage();
  const [menuOpen, setMenuOpen] = useState(false);
  const [aboutMobileOpen, setAboutMobileOpen] = useState(false);
  const [coverageMobileOpen, setCoverageMobileOpen] = useState(false);
  const [langMobileOpen, setLangMobileOpen] = useState(false);

  const NAV_LINKS = [
    { href: "/heatmap", label: t("heatmap") },
    { href: "/gallery", label: t("gallery") },
    { href: "/news", label: t("news") },
    { href: "/podcasts", label: t("podcasts") },
    { href: "/videos", label: t("videos") },
    { href: "/documents", label: t("documents") },
  ];

  const COVERAGE_COUNTRIES = [
    { href: "/about#coverage-kenya", label: `🇰🇪 ${t("countryKenya")}` },
    { href: "/about#coverage-ethiopia", label: `🇪🇹 ${t("countryEthiopia")}` },
    { href: "/about#coverage-drc", label: `🇨🇩 ${t("countryDrc")}` },
  ];

  const ABOUT_LINKS = [
    { href: "/about#purpose", label: t("ourPurpose") },
    { href: "/about#mission", label: t("ourMission") },
    { href: "/about#what-we-do", label: t("whatWeDo") },
  ];

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
          <Link href="/" className="hover:text-secondary transition-colors">
            {t("home")}
          </Link>

          {/* About Us dropdown */}
          <div className="relative group">
            <Link
              href="/about"
              className="flex items-center gap-1 hover:text-secondary transition-colors"
            >
              {t("aboutUs")}
              <svg
                className="w-3.5 h-3.5 mt-0.5 transition-transform group-hover:rotate-180"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2.5}
              >
                <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </Link>

            <div className="absolute left-0 top-full pt-3 invisible opacity-0 translate-y-1 group-hover:visible group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-150 z-50">
              <div className="w-64 bg-primary border border-secondary/20 rounded-2xl shadow-2xl py-2">
                {ABOUT_LINKS.map((link) => (
                  <Link
                    key={link.href}
                    href={link.href}
                    className="block px-4 py-2.5 text-sm font-medium hover:bg-white/10 hover:text-secondary transition-colors"
                  >
                    {link.label}
                  </Link>
                ))}

                {/* Coverage Area nested flyout */}
                <div className="relative group/coverage">
                  <div className="flex items-center justify-between px-4 py-2.5 text-sm font-medium cursor-default hover:bg-white/10 hover:text-secondary transition-colors">
                    {t("coverageArea")}
                    <svg
                      className="w-3.5 h-3.5"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      strokeWidth={2.5}
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                  </div>

                  <div className="absolute left-full top-0 pl-2 invisible opacity-0 translate-x-1 group-hover/coverage:visible group-hover/coverage:opacity-100 group-hover/coverage:translate-x-0 transition-all duration-150">
                    <div className="w-48 bg-primary border border-secondary/20 rounded-2xl shadow-2xl py-2">
                      {COVERAGE_COUNTRIES.map((link) => (
                        <Link
                          key={link.href}
                          href={link.href}
                          className="block px-4 py-2.5 text-sm font-medium hover:bg-white/10 hover:text-secondary transition-colors"
                        >
                          {link.label}
                        </Link>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

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
          {/* Language dropdown */}
          <div className="relative group">
            <button
              type="button"
              aria-label={t("language")}
              className="flex items-center gap-1.5 text-xs font-bold text-white/80 hover:text-secondary transition-colors px-2 py-2"
            >
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M3 5h12M9 3v2m4.5 12l4.5-9 4.5 9M14 15h9M6.5 9c0 5-3 9-4.5 9M4 9h8M8.5 4c0 4-1.5 7-4 9" />
              </svg>
              {LOCALE_LABELS[locale]}
              <svg className="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <div className="absolute right-0 top-full pt-3 invisible opacity-0 translate-y-1 group-hover:visible group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-150 z-50">
              <div className="w-36 bg-primary border border-secondary/20 rounded-2xl shadow-2xl py-2">
                {LOCALES.map((code) => (
                  <button
                    key={code}
                    type="button"
                    onClick={() => setLocale(code)}
                    className={`w-full text-left px-4 py-2.5 text-sm font-medium hover:bg-white/10 hover:text-secondary transition-colors ${
                      locale === code ? "text-secondary font-bold" : ""
                    }`}
                  >
                    {LOCALE_LABELS[code]}
                  </button>
                ))}
              </div>
            </div>
          </div>

          <Link
            href="/login"
            className="text-xs font-bold text-white bg-secondary/80 hover:bg-secondary transition-colors px-4 py-2 rounded-lg"
          >
            {t("login")}
          </Link>
          <Link href="/contact">
            <button className="btn-primary py-2 px-5 text-sm">
              {t("getInvolved")}
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
          <Link
            href="/"
            onClick={() => setMenuOpen(false)}
            className="text-sm font-medium hover:text-secondary transition-colors py-1"
          >
            {t("home")}
          </Link>

          {/* About Us accordion */}
          <div className="flex flex-col">
            <div className="flex items-center justify-between py-1">
              <Link
                href="/about"
                onClick={() => setMenuOpen(false)}
                className="text-sm font-medium hover:text-secondary transition-colors"
              >
                {t("aboutUs")}
              </Link>
              <button
                type="button"
                onClick={() => setAboutMobileOpen((o) => !o)}
                aria-label="Toggle About Us submenu"
                className="p-1"
              >
                <svg
                  className={`w-4 h-4 transition-transform ${aboutMobileOpen ? "rotate-180" : ""}`}
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth={2.5}
                >
                  <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
            </div>

            {aboutMobileOpen && (
              <div className="flex flex-col gap-2 pl-4 mt-1 border-l border-secondary/20">
                {ABOUT_LINKS.map((link) => (
                  <Link
                    key={link.href}
                    href={link.href}
                    onClick={() => setMenuOpen(false)}
                    className="text-sm text-white/80 hover:text-secondary transition-colors py-1"
                  >
                    {link.label}
                  </Link>
                ))}

                <div className="flex flex-col">
                  <button
                    type="button"
                    onClick={() => setCoverageMobileOpen((o) => !o)}
                    className="flex items-center justify-between text-sm text-white/80 hover:text-secondary transition-colors py-1"
                  >
                    {t("coverageArea")}
                    <svg
                      className={`w-4 h-4 transition-transform ${coverageMobileOpen ? "rotate-180" : ""}`}
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      strokeWidth={2.5}
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>

                  {coverageMobileOpen && (
                    <div className="flex flex-col gap-2 pl-4 mt-1 border-l border-secondary/20">
                      {COVERAGE_COUNTRIES.map((link) => (
                        <Link
                          key={link.href}
                          href={link.href}
                          onClick={() => setMenuOpen(false)}
                          className="text-sm text-white/70 hover:text-secondary transition-colors py-1"
                        >
                          {link.label}
                        </Link>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>

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

          {/* Language accordion */}
          <div className="flex flex-col pt-3 border-t border-secondary/20">
            <button
              type="button"
              onClick={() => setLangMobileOpen((o) => !o)}
              className="flex items-center justify-between text-sm font-medium hover:text-secondary transition-colors py-1"
            >
              {t("language")}: {LOCALE_LABELS[locale]}
              <svg
                className={`w-4 h-4 transition-transform ${langMobileOpen ? "rotate-180" : ""}`}
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2.5}
              >
                <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            {langMobileOpen && (
              <div className="flex flex-col gap-2 pl-4 mt-1 border-l border-secondary/20">
                {LOCALES.map((code) => (
                  <button
                    key={code}
                    type="button"
                    onClick={() => {
                      setLocale(code);
                      setLangMobileOpen(false);
                    }}
                    className={`text-left text-sm hover:text-secondary transition-colors py-1 ${
                      locale === code ? "text-secondary font-bold" : "text-white/80"
                    }`}
                  >
                    {LOCALE_LABELS[code]}
                  </button>
                ))}
              </div>
            )}
          </div>

          <div className="flex gap-3 mt-2 pt-3 border-t border-secondary/20">
            <Link
              href="/login"
              onClick={() => setMenuOpen(false)}
              className="flex-1 text-center text-xs font-bold text-white bg-secondary/80 hover:bg-secondary transition-colors px-4 py-2 rounded-lg"
            >
              {t("login")}
            </Link>
            <Link
              href="/contact"
              onClick={() => setMenuOpen(false)}
              className="flex-1 text-center btn-primary py-2 px-4 text-xs"
            >
              {t("getInvolved")}
            </Link>
          </div>
        </div>
      )}
    </nav>
  );
}
