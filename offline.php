<?php
http_response_code(503);
header('Cache-Control: no-store');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>You're Offline — Tafakari Digital Hub</title>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#750B25">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@700;900&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html,body{height:100%}
    body{
      font-family:'Inter',sans-serif;
      background:#0D0102;
      color:#fff;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      min-height:100vh;
      padding:24px;
      text-align:center;
    }
    .logo-wrap{background:#fff;border-radius:16px;padding:10px 16px;display:inline-block;margin-bottom:32px}
    .logo-wrap img{height:44px;width:auto;display:block}
    .signal{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 28px;background:rgba(231,149,42,.12);border:2px solid rgba(231,149,42,.25)}
    h1{font-family:'Outfit',sans-serif;font-size:clamp(26px,5vw,40px);font-weight:900;color:#fff;margin-bottom:12px;line-height:1.15}
    p{font-size:15px;color:rgba(255,255,255,.55);max-width:380px;line-height:1.65;margin-bottom:32px}
    .btn-retry{
      display:inline-flex;align-items:center;gap:8px;
      background:#E7952A;color:#0D0102;
      font-family:'Outfit',sans-serif;font-weight:700;font-size:14px;
      padding:13px 28px;border-radius:50px;border:none;cursor:pointer;
      transition:opacity .15s;text-decoration:none
    }
    .btn-retry:hover{opacity:.88}
    .links{margin-top:36px;display:flex;flex-wrap:wrap;gap:12px;justify-content:center}
    .links a{
      font-size:12px;font-weight:600;color:rgba(255,255,255,.35);
      text-decoration:none;padding:8px 16px;border-radius:50px;
      border:1px solid rgba(255,255,255,.1);transition:all .2s
    }
    .links a:hover{color:rgba(255,255,255,.8);border-color:rgba(255,255,255,.3)}
    .status{margin-top:40px;display:flex;align-items:center;gap:8px;font-size:11px;color:rgba(255,255,255,.25);font-weight:600;text-transform:uppercase;letter-spacing:.08em}
    .dot{width:6px;height:6px;border-radius:50%;background:#ef4444}
  </style>
</head>
<body>

  <div class="logo-wrap">
    <img src="/public/crtp-logo.jpg" alt="CRTP — Tafakari Digital Hub"
         onerror="this.parentElement.innerHTML='<span style=\'font-family:Outfit,sans-serif;font-weight:900;color:#750B25;font-size:18px\'>CRTP</span>'">
  </div>

  <div class="signal">
    <svg width="32" height="32" fill="none" stroke="#E7952A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
      <path d="M1 6C4.61 2.39 9.55 0.5 12 0.5s7.39 1.89 11 5.5M5 10c1.88-1.88 4.3-2.99 7-2.99s5.12 1.11 7 2.99M9 14c.88-.88 2.06-1.41 3-1.41s2.12.53 3 1.41M12 18h.01"/>
    </svg>
  </div>

  <h1>You're Offline</h1>
  <p>It looks like you've lost your internet connection. Some cached pages may still be available. Reconnect to browse the latest content.</p>

  <button class="btn-retry" onclick="window.location.reload()">
    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
      <path d="M1 4v6h6M23 20v-6h-6M20.49 9A9 9 0 005.64 5.64L1 10M23 14l-4.64 4.36A9 9 0 013.51 15"/>
    </svg>
    Try Again
  </button>

  <div class="links">
    <a href="/">Home</a>
    <a href="/news">News</a>
    <a href="/heatmap">Heatmap</a>
    <a href="/gallery">Gallery</a>
    <a href="/podcasts">Podcasts</a>
    <a href="/documents">Documents</a>
  </div>

  <div class="status">
    <span class="dot"></span>
    No connection detected
  </div>

  <script>
  window.addEventListener('online', function() {
    document.querySelector('.dot').style.background = '#10b981';
    document.querySelector('.status').lastElementChild.textContent = 'Back online — reloading…';
    setTimeout(function() { window.location.reload(); }, 800);
  });
  </script>
</body>
</html>
