import Link from "next/link";

export default function Footer() {
  return (
    <footer className="bg-[#3B0708] text-white/80 py-16 px-6 mt-20 border-t border-secondary/20">
      <div className="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-12">
        <div className="col-span-1 md:col-span-1">
          <div className="flex items-center gap-2 mb-6">
            <div className="w-8 h-8 premium-gradient rounded-lg flex items-center justify-center text-white font-bold text-lg">
              T
            </div>
            <span className="font-outfit font-bold text-lg tracking-tight text-white">
              Tafakari Hub
            </span>
          </div>
          <p className="text-sm leading-relaxed text-slate-300">
            A multi-user digital platform serving as a centralized knowledge repository, 
            media broadcasting center, and community engagement tool across Kenya, Ethiopia, and DRC.
          </p>
        </div>
        
        <div>
          <h4 className="font-outfit font-bold text-white mb-6">Quick Links</h4>
          <ul className="space-y-4 text-sm">
            <li><Link href="/" className="hover:text-secondary transition-colors">Home</Link></li>
            <li><Link href="/heatmap" className="hover:text-secondary transition-colors">Regional Heatmap</Link></li>
            <li><Link href="/gallery" className="hover:text-secondary transition-colors">Photo Gallery</Link></li>
            <li><Link href="/documents" className="hover:text-secondary transition-colors">Document Library</Link></li>
          </ul>
        </div>
        
        <div>
          <h4 className="font-outfit font-bold text-white mb-6">Resources</h4>
          <ul className="space-y-4 text-sm">
            <li><Link href="/podcasts" className="hover:text-secondary transition-colors">Podcasts</Link></li>
            <li><Link href="/videos" className="hover:text-secondary transition-colors">Video Library</Link></li>
            <li><Link href="/about" className="hover:text-secondary transition-colors">About Us</Link></li>
            <li><Link href="/contact" className="hover:text-secondary transition-colors">Contact</Link></li>
          </ul>
        </div>
        
        <div>
          <h4 className="font-outfit font-bold text-white mb-6">Contact Us</h4>
          <p className="text-sm mb-4 italic text-slate-300">Reflecting on our shared future.</p>
          <div className="flex gap-4">
            <a href="#" aria-label="Facebook" className="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-secondary hover:text-black transition-colors text-sm font-bold">f</a>
            <a href="#" aria-label="X / Twitter" className="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-secondary hover:text-black transition-colors text-sm font-bold">𝕏</a>
            <a href="https://www.youtube.com/@CRTPHUC" target="_blank" rel="noopener noreferrer" aria-label="YouTube" className="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-red-600 hover:text-white transition-colors text-sm font-bold">▶</a>
          </div>
        </div>
      </div>
      
      <div className="max-w-7xl mx-auto border-t border-slate-900 mt-16 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-slate-400">
        <p>&copy; {new Date().getFullYear()} Tafakari Digital Hub. All rights reserved.</p>
        <div className="flex gap-8 mt-4 md:mt-0">
          <a href="#" className="hover:text-white">Privacy Policy</a>
          <a href="#" className="hover:text-white">Terms of Service</a>
        </div>
      </div>
    </footer>
  );
}
