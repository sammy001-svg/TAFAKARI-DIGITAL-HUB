<footer class="text-white/80 py-16 px-6 mt-20 border-t border-yellow-800/30" style="background:#3B0708">
  <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-12">
    <div class="col-span-1">
      <div class="flex items-center gap-2 mb-6">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-lg" style="background:#9A1415">T</div>
        <span class="font-outfit font-bold text-lg tracking-tight text-white">Tafakari Hub</span>
      </div>
      <p class="text-sm leading-relaxed text-slate-300">
        A multi-user digital platform serving as a centralized knowledge repository,
        media broadcasting center, and community engagement tool across Kenya, Ethiopia, and DRC.
      </p>
    </div>

    <div>
      <h4 class="font-outfit font-bold text-white mb-6">Quick Links</h4>
      <ul class="space-y-4 text-sm">
        <li><a href="/"          class="hover:text-secondary transition-colors">Home</a></li>
        <li><a href="/heatmap"   class="hover:text-secondary transition-colors">Regional Heatmap</a></li>
        <li><a href="/gallery"   class="hover:text-secondary transition-colors">Photo Gallery</a></li>
        <li><a href="/documents" class="hover:text-secondary transition-colors">Document Library</a></li>
      </ul>
    </div>

    <div>
      <h4 class="font-outfit font-bold text-white mb-6">Resources</h4>
      <ul class="space-y-4 text-sm">
        <li><a href="/podcasts" class="hover:text-secondary transition-colors">Podcasts</a></li>
        <li><a href="/videos"   class="hover:text-secondary transition-colors">Video Library</a></li>
        <li><a href="/about"    class="hover:text-secondary transition-colors">About Us</a></li>
        <li><a href="/contact"  class="hover:text-secondary transition-colors">Contact</a></li>
      </ul>
    </div>

    <div>
      <h4 class="font-outfit font-bold text-white mb-6">Contact Us</h4>
      <p class="text-sm mb-4 italic text-slate-300">Reflecting on our shared future.</p>
      <div class="flex gap-4">
        <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-secondary hover:text-black transition-colors cursor-pointer text-xs font-bold">f</div>
        <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-secondary hover:text-black transition-colors cursor-pointer text-xs font-bold">t</div>
        <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-secondary hover:text-black transition-colors cursor-pointer text-xs font-bold">y</div>
      </div>
    </div>
  </div>

  <div class="max-w-7xl mx-auto border-t border-slate-900 mt-16 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-slate-400">
    <p>&copy; <?= date('Y') ?> Tafakari Digital Hub. All rights reserved.</p>
    <div class="flex gap-8 mt-4 md:mt-0">
      <a href="#" class="hover:text-white">Privacy Policy</a>
      <a href="#" class="hover:text-white">Terms of Service</a>
    </div>
  </div>
</footer>
