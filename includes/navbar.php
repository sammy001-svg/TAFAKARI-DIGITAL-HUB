<?php
$_navLinks = [
  ['/heatmap',   'Heatmap',   'heatmap',  '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'],
  ['/gallery',   'Gallery',   'gallery',  '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/>'],
  ['/news',      'News',      'news',     '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8M15 18h-5M10 6h8v4h-8z"/>'],
  ['/podcasts',  'Podcasts',  'podcasts', '<path d="M12 2a3 3 0 0 1 3 3v6a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3z"/><path d="M19 10v1a7 7 0 0 1-14 0v-1"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/>'],
];
$_moreLinks = [
  ['/videos',        'Videos',        'videos',      '<polygon points="23,7 16,12 23,17"/><rect x="1" y="5" width="15" height="14" rx="2"/>'],
  ['/documents',     'Documents',     'documents',   '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'],
  ['/policy-briefs', 'Policy Briefs', 'policyBriefs','<rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6M9 16h6M9 8h1"/>'],
];
$_aboutLinks = [
  ['/about#purpose',    'Our Purpose', 'ourPurpose'],
  ['/about#mission',    'Our Mission', 'ourMission'],
  ['/about#what-we-do', 'What We Do',  'whatWeDo'],
];
$_currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$_homeActive  = ($_currentPath === '/');
$_aboutActive = (strpos($_currentPath, '/about') === 0);
$_moreActive  = in_array($_currentPath, array_column($_moreLinks, 0), true);
$_siteLogoUrl = get_setting('site_logo_url', '/public/crtp-logo.jpg');
?>

<!-- ── Navbar ──────────────────────────────────────────────────────── -->
<nav class="sticky top-0 text-white shadow-xl" style="background:#750B25;border-bottom:1px solid rgba(255,255,255,.08);z-index:1000">
  <div class="px-4 md:px-10 py-3 max-w-7xl mx-auto flex items-center justify-between">

    <!-- Logo -->
    <a href="/" class="flex items-center no-underline group shrink-0" aria-label="CRTP — Centre For Research Training and Publications">
      <div class="bg-white rounded-xl px-3 py-1.5 shadow-md group-hover:shadow-lg transition-shadow">
        <img src="<?= h($_siteLogoUrl) ?>" alt="CRTP"
             class="h-9 md:h-10 w-auto object-contain block"
             onerror="this.parentElement.innerHTML='<span class=\'font-outfit font-black text-base text-[#750B25] tracking-tight px-1\'>CRTP</span>'">
      </div>
    </a>

    <!-- Desktop links -->
    <div class="hidden md:flex items-center gap-5 font-semibold text-sm">
      <a href="/"
         class="relative pb-0.5 transition-colors whitespace-nowrap <?= $_homeActive ? 'font-bold' : 'text-white/80 hover:text-white' ?>"
         <?= $_homeActive ? 'style="color:#E7952A"' : '' ?>>
        <span data-i18n="home">Home</span>
        <?php if ($_homeActive): ?><span class="absolute -bottom-3.5 left-0 right-0 h-0.5 rounded-full" style="background:#E7952A"></span><?php endif; ?>
      </a>

      <!-- About Us dropdown -->
      <div class="relative group">
        <a href="/about"
           class="flex items-center gap-1 pb-0.5 transition-colors whitespace-nowrap <?= $_aboutActive ? 'font-bold' : 'text-white/80 hover:text-white' ?>"
           <?= $_aboutActive ? 'style="color:#E7952A"' : '' ?>>
          <span data-i18n="aboutUs">About Us</span>
          <svg class="w-3.5 h-3.5 mt-0.5 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
          <?php if ($_aboutActive): ?><span class="absolute -bottom-3.5 left-0 right-0 h-0.5 rounded-full" style="background:#E7952A"></span><?php endif; ?>
        </a>

        <div class="absolute left-0 top-full pt-3 invisible opacity-0 translate-y-1 group-hover:visible group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-150 z-50">
          <div class="w-64 rounded-2xl shadow-2xl py-2" style="background:#750B25;border:1px solid rgba(255,255,255,.12)">
            <?php foreach ($_aboutLinks as [$href, $label, $i18nKey]): ?>
              <a href="<?= h($href) ?>" data-i18n="<?= h($i18nKey) ?>"
                 class="block px-4 py-2.5 text-sm font-semibold text-white/85 hover:bg-white/10 hover:text-white transition-colors">
                <?= h($label) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <?php foreach ($_navLinks as [$href, $label, $i18nKey]):
        $active = ($_currentPath === $href); ?>
        <a href="<?= h($href) ?>"
           class="relative pb-0.5 transition-colors whitespace-nowrap <?= $active ? 'font-bold' : 'text-white/80 hover:text-white' ?>"
           <?= $active ? 'style="color:#E7952A"' : '' ?>>
          <span data-i18n="<?= h($i18nKey) ?>"><?= h($label) ?></span>
          <?php if ($active): ?><span class="absolute -bottom-3.5 left-0 right-0 h-0.5 rounded-full" style="background:#E7952A"></span><?php endif; ?>
        </a>
      <?php endforeach; ?>

      <!-- More dropdown -->
      <div class="relative group">
        <button type="button"
           class="flex items-center gap-1 pb-0.5 transition-colors whitespace-nowrap <?= $_moreActive ? 'font-bold' : 'text-white/80 hover:text-white' ?>"
           <?= $_moreActive ? 'style="color:#E7952A"' : '' ?>>
          <span data-i18n="more">More</span>
          <svg class="w-3.5 h-3.5 mt-0.5 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
          <?php if ($_moreActive): ?><span class="absolute -bottom-3.5 left-0 right-0 h-0.5 rounded-full" style="background:#E7952A"></span><?php endif; ?>
        </button>

        <div class="absolute left-0 top-full pt-3 invisible opacity-0 translate-y-1 group-hover:visible group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-150 z-50">
          <div class="w-56 rounded-2xl shadow-2xl py-2" style="background:#750B25;border:1px solid rgba(255,255,255,.12)">
            <?php foreach ($_moreLinks as [$href, $label, $i18nKey, $iconPath]):
              $mActive = ($_currentPath === $href); ?>
              <a href="<?= h($href) ?>"
                 class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-colors <?= $mActive ? 'text-white bg-white/10' : 'text-white/85 hover:bg-white/10 hover:text-white' ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;opacity:.8"><?= $iconPath ?></svg>
                <span data-i18n="<?= h($i18nKey) ?>"><?= h($label) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Desktop CTA -->
    <div class="hidden md:flex items-center gap-3 shrink-0">
      <!-- Language dropdown -->
      <div class="relative group">
        <button type="button" aria-label="Language"
                class="flex items-center gap-1.5 text-xs font-bold text-white/70 hover:text-white transition-colors px-2 py-2">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m4.5 12l4.5-9 4.5 9M14 15h9M6.5 9c0 5-3 9-4.5 9M4 9h8M8.5 4c0 4-1.5 7-4 9"/></svg>
          <span id="nav-lang-label">English</span>
          <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="absolute right-0 top-full pt-3 invisible opacity-0 translate-y-1 group-hover:visible group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-150 z-50">
          <div class="w-36 rounded-2xl shadow-2xl py-2" style="background:#750B25;border:1px solid rgba(255,255,255,.12)">
            <button type="button" onclick="setSiteLocale('en')" class="w-full text-left px-4 py-2.5 text-sm font-semibold text-white/85 hover:bg-white/10 hover:text-white transition-colors">English</button>
            <button type="button" onclick="setSiteLocale('sw')" class="w-full text-left px-4 py-2.5 text-sm font-semibold text-white/85 hover:bg-white/10 hover:text-white transition-colors">Kiswahili</button>
            <button type="button" onclick="setSiteLocale('fr')" class="w-full text-left px-4 py-2.5 text-sm font-semibold text-white/85 hover:bg-white/10 hover:text-white transition-colors">French</button>
          </div>
        </div>
      </div>

      <a href="/search" aria-label="Search"
         class="w-9 h-9 flex items-center justify-center rounded-full text-white/60 hover:text-white hover:bg-white/10 transition-all">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </a>
      <a href="/admin/login" data-i18n="login"
         class="text-sm font-bold text-slate-900 px-5 py-2.5 rounded-full hover:brightness-110 transition-all no-underline"
         style="background:#E7952A">Login</a>
      <a href="/contact" data-i18n="getInvolved"
         class="text-sm font-bold text-white px-5 py-2.5 rounded-full border-2 border-white/35 hover:border-white/65 hover:bg-white/10 transition-all no-underline">
        Contact Us
      </a>
    </div>

    <!-- Mobile hamburger -->
    <button id="nav-hamburger" onclick="_navOpen()"
            class="md:hidden w-11 h-11 flex flex-col items-center justify-center gap-[5px] rounded-xl hover:bg-white/10 transition-colors"
            aria-label="Open navigation" aria-expanded="false">
      <span class="w-5 h-0.5 bg-white rounded-full transition-all duration-300" id="hb1"></span>
      <span class="w-5 h-0.5 bg-white rounded-full transition-all duration-300" id="hb2"></span>
      <span class="w-3 h-0.5 bg-white/70 rounded-full self-start ml-[5px] transition-all duration-300" id="hb3"></span>
    </button>

  </div>
</nav>

<!-- Mobile: dim backdrop -->
<div id="nav-backdrop"
     onclick="_navClose()"
     style="position:fixed;inset:0;z-index:800;background:rgba(0,0,0,.6);opacity:0;pointer-events:none;transition:opacity .3s;backdrop-filter:blur(2px)">
</div>

<!-- Mobile: right-side drawer -->
<div id="nav-drawer"
     role="dialog" aria-modal="true" aria-label="Navigation menu"
     style="position:fixed;top:0;right:0;bottom:0;z-index:900;
            width:min(320px,90vw);background:#750B25;
            transform:translateX(100%);transition:transform .32s cubic-bezier(.4,0,.2,1);
            box-shadow:-8px 0 48px rgba(0,0,0,.4);overflow-y:auto;display:flex;flex-direction:column">

  <!-- Drawer header -->
  <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.1);flex-shrink:0">
    <div style="background:#fff;border-radius:12px;padding:8px 14px;box-shadow:0 2px 8px rgba(0,0,0,.2)">
      <img src="<?= h($_siteLogoUrl) ?>" alt="CRTP"
           style="height:32px;width:auto;object-fit:contain;display:block"
           onerror="this.parentElement.innerHTML='<span style=\'font-family:Outfit,sans-serif;font-weight:900;font-size:14px;color:#750B25;padding:0 4px\'>CRTP</span>'">
    </div>
    <button onclick="_navClose()"
            aria-label="Close menu"
            style="width:40px;height:40px;border-radius:50%;border:none;background:rgba(255,255,255,.1);
                   color:#fff;font-size:22px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;
                   transition:background .15s"
            onmouseover="this.style.background='rgba(255,255,255,.18)'"
            onmouseout="this.style.background='rgba(255,255,255,.1)'">&times;</button>
  </div>

  <!-- Drawer nav links -->
  <div style="padding:12px;flex:1">
    <!-- Home -->
    <a href="/" onclick="_navClose()"
       style="display:flex;align-items:center;gap:14px;padding:13px 16px;border-radius:16px;
              text-decoration:none;font-size:15px;font-weight:600;margin-bottom:4px;
              transition:background .15s,color .15s;<?= $_homeActive
                ? 'background:rgba(231,149,42,.16);color:#E7952A;border:1px solid rgba(231,149,42,.28)'
                : 'color:rgba(255,255,255,.78);border:1px solid transparent' ?>"
       onmouseover="if(!<?= $_homeActive?'true':'false' ?>){this.style.background='rgba(255,255,255,.07)'}"
       onmouseout="if(!<?= $_homeActive?'true':'false' ?>){this.style.background='transparent'}">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"
           stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"
           style="flex-shrink:0;opacity:<?= $_homeActive?'1':'.7' ?>"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
      <span style="flex:1" data-i18n="home">Home</span>
    </a>

    <!-- About Us accordion -->
    <div style="margin-bottom:4px">
      <div style="display:flex;align-items:center;border-radius:16px;<?= $_aboutActive
        ? 'background:rgba(231,149,42,.16);color:#E7952A;border:1px solid rgba(231,149,42,.28)'
        : 'color:rgba(255,255,255,.78);border:1px solid transparent' ?>">
        <a href="/about" onclick="_navClose()"
           style="flex:1;display:flex;align-items:center;gap:14px;padding:13px 0 13px 16px;text-decoration:none;font-size:15px;font-weight:600;color:inherit">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;opacity:<?= $_aboutActive?'1':'.7' ?>"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
          <span data-i18n="aboutUs">About Us</span>
        </a>
        <button type="button" onclick="_toggleDrawerSection('about-us-sub')" aria-label="Toggle About Us submenu"
                style="background:none;border:none;color:inherit;padding:13px 16px;cursor:pointer;display:flex;align-items:center">
          <svg id="about-us-sub-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="transition:transform .2s"><path d="M19 9l-7 7-7-7"/></svg>
        </button>
      </div>
      <div id="about-us-sub" style="display:none;padding:6px 0 2px 20px;border-left:1px solid rgba(255,255,255,.12);margin-left:20px">
        <?php foreach ($_aboutLinks as [$href, $label, $i18nKey]): ?>
          <a href="<?= h($href) ?>" data-i18n="<?= h($i18nKey) ?>" onclick="_navClose()"
             style="display:block;padding:9px 12px;text-decoration:none;font-size:14px;font-weight:600;color:rgba(255,255,255,.75)">
            <?= h($label) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php foreach ($_navLinks as [$href, $label, $i18nKey, $iconPath]):
      $active = ($_currentPath === $href);
      $style  = $active
        ? 'background:rgba(231,149,42,.16);color:#E7952A;border:1px solid rgba(231,149,42,.28)'
        : 'color:rgba(255,255,255,.78);border:1px solid transparent';
    ?>
      <a href="<?= h($href) ?>"
         onclick="_navClose()"
         style="display:flex;align-items:center;gap:14px;padding:13px 16px;border-radius:16px;
                text-decoration:none;font-size:15px;font-weight:600;margin-bottom:4px;
                transition:background .15s,color .15s;<?= $style ?>"
         onmouseover="if(!<?= $active?'true':'false' ?>){this.style.background='rgba(255,255,255,.07)'}"
         onmouseout="if(!<?= $active?'true':'false' ?>){this.style.background='transparent'}">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"
             style="flex-shrink:0;opacity:<?= $active?'1':'.7' ?>"><?= $iconPath ?></svg>
        <span style="flex:1" data-i18n="<?= h($i18nKey) ?>"><?= h($label) ?></span>
        <?php if ($active): ?>
          <svg width="14" height="14" fill="none" stroke="#E7952A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>

    <!-- More accordion -->
    <div style="margin-bottom:4px">
      <div style="display:flex;align-items:center;border-radius:16px;<?= $_moreActive
        ? 'background:rgba(231,149,42,.16);color:#E7952A;border:1px solid rgba(231,149,42,.28)'
        : 'color:rgba(255,255,255,.78);border:1px solid transparent' ?>">
        <button type="button" onclick="_toggleDrawerSection('more-sub')" aria-label="Toggle More submenu"
                style="flex:1;display:flex;align-items:center;gap:14px;padding:13px 0 13px 16px;background:none;border:none;font-size:15px;font-weight:600;color:inherit;text-align:left;cursor:pointer">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;opacity:<?= $_moreActive?'1':'.7' ?>"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
          <span style="flex:1" data-i18n="more">More</span>
        </button>
        <button type="button" onclick="_toggleDrawerSection('more-sub')" aria-label="Toggle More submenu"
                style="background:none;border:none;color:inherit;padding:13px 16px;cursor:pointer;display:flex;align-items:center">
          <svg id="more-sub-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="transition:transform .2s<?= $_moreActive ? ';transform:rotate(180deg)' : '' ?>"><path d="M19 9l-7 7-7-7"/></svg>
        </button>
      </div>
      <div id="more-sub" style="display:<?= $_moreActive ? 'block' : 'none' ?>;padding:6px 0 2px 20px;border-left:1px solid rgba(255,255,255,.12);margin-left:20px">
        <?php foreach ($_moreLinks as [$href, $label, $i18nKey, $iconPath]):
          $mActive = ($_currentPath === $href); ?>
          <a href="<?= h($href) ?>" onclick="_navClose()"
             style="display:flex;align-items:center;gap:10px;padding:9px 12px;text-decoration:none;font-size:14px;font-weight:600;<?= $mActive ? 'color:#E7952A' : 'color:rgba(255,255,255,.75)' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;opacity:.8"><?= $iconPath ?></svg>
            <span data-i18n="<?= h($i18nKey) ?>"><?= h($label) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Search -->
    <a href="/search" onclick="_navClose()"
       style="display:flex;align-items:center;gap:14px;padding:13px 16px;border-radius:16px;
              text-decoration:none;font-size:15px;font-weight:600;color:rgba(255,255,255,.78);
              border:1px solid transparent;transition:background .15s;margin-top:4px;
              border-top:1px solid rgba(255,255,255,.1);padding-top:17px;margin-top:8px"
       onmouseover="this.style.background='rgba(255,255,255,.07)'"
       onmouseout="this.style.background='transparent'">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;opacity:.7"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <span data-i18n="search">Search</span>
    </a>
  </div>

  <!-- Drawer Language accordion -->
  <div style="padding:0 16px;border-top:1px solid rgba(255,255,255,.1);flex-shrink:0">
    <button type="button" onclick="_toggleDrawerSection('lang-sub')"
            style="width:100%;display:flex;align-items:center;justify-content:space-between;background:none;border:none;padding:14px 0;font-size:14px;font-weight:700;color:rgba(255,255,255,.85);cursor:pointer">
      <span>Language: <span id="nav-lang-label-mobile">English</span></span>
      <svg id="lang-sub-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="transition:transform .2s"><path d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div id="lang-sub" style="display:none;padding-bottom:8px">
      <button type="button" onclick="setSiteLocale('en')" style="display:block;width:100%;text-align:left;background:none;border:none;padding:8px 12px;font-size:13px;font-weight:600;color:rgba(255,255,255,.7);cursor:pointer">English</button>
      <button type="button" onclick="setSiteLocale('sw')" style="display:block;width:100%;text-align:left;background:none;border:none;padding:8px 12px;font-size:13px;font-weight:600;color:rgba(255,255,255,.7);cursor:pointer">Kiswahili</button>
      <button type="button" onclick="setSiteLocale('fr')" style="display:block;width:100%;text-align:left;background:none;border:none;padding:8px 12px;font-size:13px;font-weight:600;color:rgba(255,255,255,.7);cursor:pointer">French</button>
    </div>
  </div>

  <!-- Drawer CTA buttons -->
  <div style="padding:16px;border-top:1px solid rgba(255,255,255,.1);flex-shrink:0">
    <a href="/admin/login" onclick="_navClose()"
       style="display:block;text-align:center;text-decoration:none;font-size:14px;font-weight:700;
              color:#0D0102;background:#E7952A;padding:14px 16px;border-radius:16px;margin-bottom:10px;
              transition:opacity .15s"
       onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
      Login to Dashboard
    </a>
    <a href="/contact" onclick="_navClose()" data-i18n="getInvolved"
       style="display:block;text-align:center;text-decoration:none;font-size:14px;font-weight:700;
              color:#fff;padding:13px 16px;border-radius:16px;
              border:2px solid rgba(255,255,255,.3);transition:border-color .15s"
       onmouseover="this.style.borderColor='rgba(255,255,255,.55)'" onmouseout="this.style.borderColor='rgba(255,255,255,.3)'">
      Contact Us
    </a>
  </div>

  <!-- Drawer footer -->
  <div style="padding:16px 20px 24px;border-top:1px solid rgba(255,255,255,.07);flex-shrink:0">
    <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:rgba(231,149,42,.5);margin:0 0 4px">Tafakari Digital Hub</p>
    <p style="font-size:10px;color:rgba(255,255,255,.22);margin:0">Centre For Research Training and Publications</p>
  </div>

</div><!-- /#nav-drawer -->

<script>
(function(){
  var _open = false;

  window._navOpen = function() {
    _open = true;
    var drawer   = document.getElementById('nav-drawer');
    var backdrop = document.getElementById('nav-backdrop');
    var btn      = document.getElementById('nav-hamburger');
    drawer.style.transform   = 'translateX(0)';
    backdrop.style.opacity   = '1';
    backdrop.style.pointerEvents = 'auto';
    document.body.style.overflow = 'hidden';
    btn.setAttribute('aria-expanded','true');
    /* Animate hamburger → X */
    var h1 = document.getElementById('hb1'), h2 = document.getElementById('hb2'), h3 = document.getElementById('hb3');
    h1.style.transform = 'rotate(45deg) translateY(7px)';
    h2.style.opacity   = '0';
    h3.style.transform = 'rotate(-45deg) translateY(-7px)';
    h3.style.width     = '20px'; h3.style.opacity = '1'; h3.style.marginLeft = '0';
  };

  window._navClose = function() {
    _open = false;
    var drawer   = document.getElementById('nav-drawer');
    var backdrop = document.getElementById('nav-backdrop');
    var btn      = document.getElementById('nav-hamburger');
    drawer.style.transform       = 'translateX(100%)';
    backdrop.style.opacity       = '0';
    backdrop.style.pointerEvents = 'none';
    document.body.style.overflow = '';
    btn.setAttribute('aria-expanded','false');
    /* Reset hamburger */
    var h1 = document.getElementById('hb1'), h2 = document.getElementById('hb2'), h3 = document.getElementById('hb3');
    h1.style.transform = ''; h2.style.opacity = '1';
    h3.style.transform = ''; h3.style.width = '12px'; h3.style.opacity = '';  h3.style.marginLeft = '5px';
  };

  document.addEventListener('keydown', function(e){ if(e.key==='Escape' && _open) window._navClose(); });

  /* Prevent drawer-internal scroll from closing */
  var drawer = document.getElementById('nav-drawer');
  if (drawer) drawer.addEventListener('touchmove', function(e){ e.stopPropagation(); }, { passive:true });

  /* Mobile accordion toggles (About Us / Language) */
  window._toggleDrawerSection = function(id) {
    var section  = document.getElementById(id);
    var chevron  = document.getElementById(id + '-chevron');
    if (!section) return;
    var isOpen = section.style.display === 'block';
    section.style.display = isOpen ? 'none' : 'block';
    if (chevron) chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
  };
})();
</script>
