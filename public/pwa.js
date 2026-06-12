/* Tafakari Digital Hub — PWA: Service Worker registration + Install Prompt */
(function () {
  'use strict';

  /* ── Service Worker Registration ──────────────────────────────────────── */
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker
        .register('/sw.js', { scope: '/' })
        .then(function (reg) {
          reg.addEventListener('updatefound', function () {
            var newSW = reg.installing;
            if (!newSW) return;
            newSW.addEventListener('statechange', function () {
              if (newSW.state === 'installed' && navigator.serviceWorker.controller) {
                showUpdateToast();
              }
            });
          });
        })
        .catch(function (err) {
          console.warn('[PWA] SW registration failed:', err);
        });
    });
  }

  /* ── Helpers ──────────────────────────────────────────────────────────── */
  var STORE_DISMISS  = 'tafakari_pwa_dismissed';
  var STORE_INSTALL  = 'tafakari_pwa_installed';
  var deferredPrompt = null;
  var bannerShown    = false;

  function isIOS() {
    return /iP(ad|hone|od)/.test(navigator.userAgent) && !window.MSStream;
  }
  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches ||
           navigator.standalone === true;
  }
  function isAdmin() {
    return window.location.pathname.startsWith('/admin');
  }
  function shouldShowPrompt() {
    if (isAdmin() || isStandalone()) return false;
    if (localStorage.getItem(STORE_INSTALL)) return false;
    var d = localStorage.getItem(STORE_DISMISS);
    if (d && Date.now() - parseInt(d, 10) < 30 * 86400000) return false;
    return true;
  }

  /* ── CSS injection ────────────────────────────────────────────────────── */
  function injectCSS() {
    if (document.getElementById('pwa-css')) return;
    var s = document.createElement('style');
    s.id = 'pwa-css';
    s.textContent = [
      '#pwa-banner{',
        'position:fixed;bottom:0;left:0;right:0;z-index:99999;',
        'transform:translateY(110%);',
        'transition:transform .45s cubic-bezier(.34,1.56,.64,1);',
        'padding:0 0 env(safe-area-inset-bottom,0px);',
      '}',
      '#pwa-banner.pwa-show{transform:translateY(0)}',
      '@media(min-width:640px){',
        '#pwa-banner{left:auto;right:20px;bottom:20px;width:360px;',
        'border-radius:20px;overflow:hidden;',
        'box-shadow:0 24px 64px rgba(0,0,0,.18),0 4px 20px rgba(117,11,37,.14);}',
      '}',
      '#pwa-toast{',
        'position:fixed;bottom:20px;left:50%;transform:translateX(-50%) translateY(80px);',
        'z-index:99998;',
        'background:#0D0102;color:#fff;',
        'font-size:13px;font-weight:600;padding:12px 20px;border-radius:50px;',
        'white-space:nowrap;border:1px solid rgba(255,255,255,.1);',
        'transition:transform .3s,opacity .3s;opacity:0;',
      '}',
      '#pwa-toast.show{transform:translateX(-50%) translateY(0);opacity:1}',
    ].join('');
    document.head.appendChild(s);
  }

  /* ── Banner HTML ──────────────────────────────────────────────────────── */
  function buildBanner(ios) {
    var el = document.createElement('div');
    el.id  = 'pwa-banner';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-label', 'Install Tafakari Digital Hub');

    var iosBlock = ios ? [
      '<div style="background:rgba(231,149,42,.1);border:1px solid rgba(231,149,42,.28);border-radius:12px;padding:11px 14px;margin:0 0 14px">',
        '<p style="font-size:12px;color:#92400e;margin:0;line-height:1.65">',
          'Tap the <strong>Share</strong> button ',
          '<svg style="display:inline-block;vertical-align:middle;margin:0 1px" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>',
          ' in Safari, then select <strong>"Add to Home Screen"</strong>.',
        '</p>',
      '</div>',
      '<button id="pwa-got-it" style="width:100%;padding:13px;border:none;border-radius:12px;background:#750B25;color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:Outfit,sans-serif;letter-spacing:.01em">',
        'Got it',
      '</button>',
    ].join('') : [
      '<div style="display:flex;gap:10px">',
        '<button id="pwa-install-btn" style="flex:1;padding:13px;border:none;border-radius:12px;background:#750B25;color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:Outfit,sans-serif;letter-spacing:.01em;transition:opacity .15s" onmouseover="this.style.opacity=\'.85\'" onmouseout="this.style.opacity=\'1\'">',
          'Install App',
        '</button>',
        '<button id="pwa-later-btn" style="flex:1;padding:13px;border:1.5px solid #e2e8f0;border-radius:12px;background:#fff;color:#64748b;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'#fff\'">',
          'Not Now',
        '</button>',
      '</div>',
    ].join('');

    el.innerHTML = [
      '<div style="background:#fff;border-top:3px solid #750B25;padding:18px 18px 16px">',
        /* Header row */
        '<div style="display:flex;align-items:flex-start;gap:13px;margin-bottom:12px">',
          '<img src="/pwa-icon.php?size=64" alt="App icon"',
               'style="width:54px;height:54px;border-radius:13px;border:1px solid rgba(117,11,37,.1);flex-shrink:0;object-fit:contain"',
               'onerror="this.style.display=\'none\'">',
          '<div style="flex:1;min-width:0;padding-top:2px">',
            '<p style="font-family:Outfit,sans-serif;font-weight:900;font-size:15px;color:#0D0102;line-height:1.2;margin:0 0 3px">',
              'Tafakari Digital Hub',
            '</p>',
            '<p style="font-size:11px;color:#94a3b8;margin:0;font-weight:600;text-transform:uppercase;letter-spacing:.07em">',
              'CRTP Knowledge Platform',
            '</p>',
          '</div>',
          '<button id="pwa-close-btn"',
                  'style="width:26px;height:26px;border:none;background:rgba(0,0,0,.07);border-radius:50%;cursor:pointer;font-size:16px;color:#64748b;flex-shrink:0;display:flex;align-items:center;justify-content:center;line-height:1;padding:0"',
                  'aria-label="Close">&times;</button>',
        '</div>',
        /* Body */
        '<p style="font-size:13px;color:#475569;margin:0 0 14px;line-height:1.6">',
          ios
            ? 'Add to your Home Screen for quick access, offline reading, and a full-screen experience.'
            : 'Install our app for instant access, offline reading, and a native experience on any device.',
        '</p>',
        /* Action area */
        iosBlock,
      '</div>',
    ].join('');

    return el;
  }

  /* ── Show / hide banner ───────────────────────────────────────────────── */
  function showBanner(ios) {
    if (bannerShown) return;
    bannerShown = true;
    injectCSS();

    var banner = buildBanner(ios);
    document.body.appendChild(banner);

    requestAnimationFrame(function () {
      requestAnimationFrame(function () { banner.classList.add('pwa-show'); });
    });

    function dismiss(remember) {
      banner.classList.remove('pwa-show');
      if (remember) localStorage.setItem(STORE_DISMISS, Date.now().toString());
      setTimeout(function () { if (banner.parentNode) banner.parentNode.removeChild(banner); }, 500);
    }

    var closeBtn = document.getElementById('pwa-close-btn');
    if (closeBtn) closeBtn.addEventListener('click', function () { dismiss(true); });

    if (ios) {
      var gotIt = document.getElementById('pwa-got-it');
      if (gotIt) gotIt.addEventListener('click', function () { dismiss(true); });
    } else {
      var installBtn = document.getElementById('pwa-install-btn');
      var laterBtn   = document.getElementById('pwa-later-btn');

      if (installBtn) {
        installBtn.addEventListener('click', function () {
          if (!deferredPrompt) { dismiss(false); return; }
          deferredPrompt.prompt();
          deferredPrompt.userChoice.then(function (result) {
            if (result.outcome === 'accepted') {
              localStorage.setItem(STORE_INSTALL, '1');
            }
            deferredPrompt = null;
            dismiss(false);
          });
        });
      }
      if (laterBtn) laterBtn.addEventListener('click', function () { dismiss(true); });
    }

    /* Close on backdrop tap (mobile sheet) */
    banner.addEventListener('click', function (e) {
      if (e.target === banner) dismiss(true);
    });
  }

  /* ── Update toast ─────────────────────────────────────────────────────── */
  function showUpdateToast() {
    injectCSS();
    var t = document.createElement('div');
    t.id = 'pwa-toast';
    t.innerHTML = '✦ New version available — <a href="" onclick="window.location.reload();return false;" style="color:#E7952A;font-weight:700;text-decoration:none">Refresh</a>';
    document.body.appendChild(t);
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { t.classList.add('show'); });
    });
  }

  /* ── Event wiring ─────────────────────────────────────────────────────── */
  if (isStandalone()) {
    localStorage.setItem(STORE_INSTALL, '1');
    return;
  }

  if (!shouldShowPrompt()) return;

  /* Chrome / Edge / Android — capture native install event */
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    setTimeout(function () { showBanner(false); }, 2800);
  });

  /* iOS Safari — no beforeinstallprompt, show manual instructions */
  if (isIOS()) {
    setTimeout(function () { showBanner(true); }, 2800);
  }

  window.addEventListener('appinstalled', function () {
    localStorage.setItem(STORE_INSTALL, '1');
    var b = document.getElementById('pwa-banner');
    if (b) b.parentNode.removeChild(b);
  });

}());
