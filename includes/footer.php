<footer class="text-white/80 py-16 px-6 mt-20 border-t border-yellow-800/30" style="background:#2A0515">
  <div class="max-w-7xl mx-auto">

    <!-- Top row: logo + columns -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-14">

      <!-- Brand column -->
      <div class="col-span-1">
        <!-- CRTP Logo -->
        <div class="flex items-center gap-3 mb-5">
          <img src="/public/crtp-logo.png" alt="CRTP Logo"
               class="h-12 w-auto object-contain brightness-0 invert"
               onerror="this.style.display='none';document.getElementById('footer-logo-fallback').style.display='flex'">
          <div id="footer-logo-fallback" class="items-center gap-2" style="display:none">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold text-lg" style="background:#750B25">C</div>
          </div>
          <div class="flex flex-col leading-tight">
            <span class="font-outfit font-bold text-lg tracking-tight text-white">Tafakari Hub</span>
            <span class="text-[9px] uppercase tracking-[.14em] text-white/40 font-semibold">CRTP Platform</span>
          </div>
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

    <!-- Partners & Network band -->
    <div class="border-t border-white/10 pt-10 pb-8">
      <p class="text-[10px] font-black uppercase tracking-[.18em] text-white/30 mb-6 text-center">Our Partners &amp; Network Members</p>
      <div class="flex flex-wrap items-center justify-center gap-10">

        <!-- Hekima University College -->
        <div class="flex items-center gap-3 opacity-70 hover:opacity-100 transition-opacity">
          <img src="/public/hekima-logo.png" alt="Hekima University College"
               class="h-10 w-auto object-contain brightness-0 invert"
               onerror="this.style.display='none';document.getElementById('hekima-fallback').style.display='flex'">
          <div id="hekima-fallback" class="items-center gap-2" style="display:none">
            <div class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-white font-bold text-sm">H</div>
          </div>
          <div class="flex flex-col leading-tight">
            <span class="text-[12px] font-bold text-white/80 leading-none">Hekima University College</span>
            <span class="text-[9px] text-white/40 uppercase tracking-widest mt-0.5">Institute of Peace Studies</span>
          </div>
        </div>

        <!-- Placeholder for additional partners -->
        <div class="flex items-center gap-2 opacity-40">
          <div class="w-8 h-8 rounded-full border border-white/20 flex items-center justify-center text-white/40 text-xs font-bold">+</div>
          <span class="text-[11px] text-white/40">Become a Partner</span>
        </div>
      </div>
    </div>

    <!-- Bottom bar -->
    <div class="border-t border-slate-900 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-slate-400 gap-3">
      <p>&copy; <?= date('Y') ?> Tafakari Digital Hub &mdash; A CRTP Initiative. All rights reserved.</p>
      <div class="flex gap-8">
        <a href="#" class="hover:text-white">Privacy Policy</a>
        <a href="#" class="hover:text-white">Terms of Service</a>
      </div>
    </div>
  </div>
</footer>
