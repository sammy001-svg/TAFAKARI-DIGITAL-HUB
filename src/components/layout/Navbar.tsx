import Link from "next/link";

export default function Navbar() {
  return (
    <nav className="sticky top-0 z-50 bg-emerald-600/95 backdrop-blur-md text-white shadow-xl px-12 py-5 flex items-center justify-between border-b border-emerald-500/30">
      <div className="flex items-center gap-2">
        <div className="w-10 h-10 premium-gradient rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg">
          T
        </div>
        <span className="font-outfit font-bold text-xl tracking-tight hidden md:block">
          Tafakari Hub
        </span>
      </div>
      
      <div className="hidden md:flex items-center gap-8 font-medium text-sm">
        <Link href="/" className="hover:text-black transition-colors">Home</Link>
        <Link href="/heatmap" className="hover:text-black transition-colors">Heatmap</Link>
        <Link href="/gallery" className="hover:text-black transition-colors">Gallery</Link>
        <Link href="/podcasts" className="hover:text-black transition-colors">Podcasts</Link>
        <Link href="/videos" className="hover:text-black transition-colors">Videos</Link>
        <Link href="/documents" className="hover:text-black transition-colors">Documents</Link>
      </div>
      
      <div className="flex items-center gap-4">
        <Link href="/login" className="text-xs font-bold text-white bg-red-600 hover:bg-red-700 transition-colors px-4 py-2 rounded-lg">
          Login
        </Link>
        <button className="btn-primary py-2 px-5 text-sm">
          Get Involved
        </button>
      </div>
    </nav>
  );
}
