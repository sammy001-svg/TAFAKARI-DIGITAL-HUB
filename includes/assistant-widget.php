<?php
/**
 * The Tafakari Assistant: floating chat button and panel, on every public page.
 *
 * Shown unless a super admin has switched the chat off. When the AI is not
 * ready (no key, or the Claude software missing), it still opens and offers
 * the contact details instead, so visitors are never left without help.
 *
 * Everything the assistant says is put on the page as text, never as HTML:
 * answers are split into paragraphs, lists and citation links here, in the
 * browser, from plain text. Only links to this site are ever shown.
 */
try {
    require_once __DIR__ . '/agent.php';
    require_once __DIR__ . '/reports.php';
    require_once __DIR__ . '/assistant.php';
    $_askShow  = assistant_settings()['enabled'];
    $_askReady = $_askShow && assistant_available();
} catch (Throwable $e) {
    $_askShow  = true;     // the chat itself can still point people to CRTP
    $_askReady = false;
}
if ($_askShow):
    if ($_askReady && empty($_SESSION['assistant_csrf'])) $_SESSION['assistant_csrf'] = bin2hex(random_bytes(16));
?>
<div class="tk-ask-teaser no-print" id="tk-ask-teaser" hidden>
  <button type="button" class="tk-ask-teaser__text" id="tk-ask-teaser-open"></button>
  <button type="button" class="tk-ask-teaser__x" id="tk-ask-teaser-x" aria-label="Close">&times;</button>
</div>
<button type="button" class="tk-ask-fab no-print" id="tk-ask-open" aria-haspopup="dialog" aria-controls="tk-ask" aria-expanded="false">
  <span class="tk-ask-fab__icon">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 01-11.6 7.1L4 20l1-4.6A8 8 0 1121 12z"/><path d="M8.5 11.5h.01M12 11.5h.01M15.5 11.5h.01"/></svg>
    <span class="tk-ask-fab__dot" id="tk-ask-dot" hidden></span>
  </span>
  <span class="tk-ask-fab__label" id="tk-ask-fab-label">Chat with us</span>
</button>
<section class="tk-ask no-print" id="tk-ask" role="dialog" aria-modal="false" aria-labelledby="tk-ask-title" hidden>
  <header class="tk-ask__head">
    <div class="tk-ask__brand">
      <span class="tk-ask__avatar" aria-hidden="true">T</span>
      <div style="min-width:0">
        <h2 id="tk-ask-title">Tafakari Assistant</h2>
        <p><span class="tk-ask__status<?= $_askReady ? '' : ' tk-ask__status--off' ?>" aria-hidden="true"></span><span id="tk-ask-sub"></span></p>
      </div>
    </div>
    <div class="tk-ask__actions">
      <button type="button" class="tk-ask__lang" id="tk-ask-lang"></button>
      <?php if ($_askReady): ?>
        <button type="button" class="tk-ask__icon" id="tk-ask-reset">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>
        </button>
      <?php endif; ?>
      <button type="button" class="tk-ask__icon" id="tk-ask-min">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 12h12"/></svg>
      </button>
      <button type="button" class="tk-ask__icon" id="tk-ask-close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>
  </header>
  <div class="tk-ask__body" id="tk-ask-log" role="log" aria-live="polite"></div>
  <?php if ($_askReady): ?>
    <form class="tk-ask__form" id="tk-ask-form">
      <div class="tk-ask__composer">
        <label for="tk-ask-q" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)" id="tk-ask-q-label">Your message</label>
        <textarea id="tk-ask-q" rows="1" maxlength="500" required></textarea>
        <button type="submit" class="tk-ask__send" id="tk-ask-send" disabled>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
      </div>
      <span class="tk-ask__count" id="tk-ask-count" aria-live="polite"></span>
    </form>
  <?php endif; ?>
  <p class="tk-ask__note"><span id="tk-ask-note"></span> <a href="/privacy" id="tk-ask-privacy"></a> · <a href="/contact" id="tk-ask-human"></a></p>
</section>
<script>
(function () {
  var READY = <?= $_askReady ? 'true' : 'false' ?>;
  var TOKEN = <?= json_encode($_askReady ? $_SESSION['assistant_csrf'] : '') ?>;
  var STORE = 'tk-chat-v1', SEEN = 'tk-chat-greeted', OPEN = 'tk-chat-open', LANG = 'tk-chat-lang';
  var $ = function (id) { return document.getElementById(id); };
  var panel = $('tk-ask'), fab = $('tk-ask-open'), log = $('tk-ask-log'), q = $('tk-ask-q'), send = $('tk-ask-send'), teaser = $('tk-ask-teaser');

  function read(s, k) { try { return window[s].getItem(k); } catch (e) { return null; } }
  function write(s, k, v) { try { window[s].setItem(k, v); } catch (e) {} }
  function el(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; }
  function svg(path) { var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg'); s.setAttribute('viewBox', '0 0 24 24'); s.setAttribute('fill', 'none'); s.setAttribute('stroke', 'currentColor'); s.setAttribute('stroke-width', '2'); s.setAttribute('stroke-linecap', 'round'); s.setAttribute('stroke-linejoin', 'round'); s.setAttribute('aria-hidden', 'true'); path.split('|').forEach(function (d) { var p = document.createElementNS('http://www.w3.org/2000/svg', 'path'); p.setAttribute('d', d); s.appendChild(p); }); return s; }

  var TEXT = {
    en: {
      fab: 'Chat with us', resume: 'Continue chat', fresh: 'New reply', sub: READY ? 'Online · AI assistant' : 'Offline just now',
      lang: 'SW', langLabel: 'Badilisha kwenda Kiswahili', send: 'Send', hint: 'Ask about CRTP, our reports or the heat map…', you: 'Your message',
      min: 'Minimise chat', close: 'End chat', teaser: 'Hello! Welcome to Tafakari. Can I help you today?',
      note: 'AI can make mistakes, so check the sources. Please don’t share personal details. Not for emergencies.', privacy: 'Privacy', human: 'Contact CRTP', reset: 'New chat',
      welcome: 'Hello, and welcome to Tafakari! I’m CRTP’s AI assistant. I can answer questions about our work, our reports and the conflict heat map, using what CRTP has published.',
      chips: ['What does CRTP do?', 'Latest on South Sudan', 'How is the heat map scored?', 'Where can I download reports?'],
      typing: 'Looking through CRTP’s work', sources: 'Sources', helpful: 'Helpful', notHelpful: 'Not helpful', copy: 'Copy answer', copied: 'Copied', thanks: 'Thank you for the feedback',
      error: 'Sorry, I can’t be reached right now.', retry: 'Try again', count: ' characters left',
      offline: 'Hello, and welcome to Tafakari! Our assistant is resting just now. You can still reach CRTP by email at crtp.communications@hekima.ac.ke or through our Contact page.', contact: 'Contact CRTP'
    },
    sw: {
      fab: 'Zungumza nasi', resume: 'Endelea kuzungumza', fresh: 'Jibu jipya', sub: READY ? 'Mtandaoni · Msaidizi wa AI' : 'Hapatikani kwa sasa',
      lang: 'EN', langLabel: 'Switch to English', send: 'Tuma', hint: 'Uliza kuhusu CRTP, ripoti zetu au ramani…', you: 'Ujumbe wako',
      min: 'Punguza', close: 'Maliza mazungumzo', teaser: 'Habari! Karibu Tafakari. Naweza kukusaidia leo?',
      note: 'AI inaweza kukosea, tafadhali hakiki vyanzo. Usitoe taarifa zako binafsi. Si kwa dharura.', privacy: 'Faragha', human: 'Wasiliana na CRTP', reset: 'Mazungumzo mapya',
      welcome: 'Habari, karibu Tafakari! Mimi ni msaidizi wa AI wa CRTP. Naweza kujibu maswali kuhusu kazi zetu, ripoti zetu na ramani ya migogoro, kwa kutumia yale ambayo CRTP imechapisha.',
      chips: ['CRTP inafanya nini?', 'Hali ikoje Sudani Kusini?', 'Ramani ya joto inapimwaje?', 'Nitapata ripoti wapi?'],
      typing: 'Ninatafuta katika kazi za CRTP', sources: 'Vyanzo', helpful: 'Imesaidia', notHelpful: 'Haijasaidia', copy: 'Nakili jibu', copied: 'Imenakiliwa', thanks: 'Asante kwa maoni yako',
      error: 'Samahani, siwezi kupatikana kwa sasa.', retry: 'Jaribu tena', count: ' herufi zimebaki',
      offline: 'Habari, karibu Tafakari! Msaidizi wetu amepumzika kwa sasa. Bado unaweza kuwasiliana na CRTP kwa barua pepe crtp.communications@hekima.ac.ke au kupitia ukurasa wetu wa Mawasiliano.', contact: 'Wasiliana nasi'
    }
  };
  var lang = read('localStorage', LANG) || (read('localStorage', 'tafakari-locale') === 'sw' ? 'sw' : 'en');
  var T = TEXT[lang] || TEXT.en;

  // Kept only in this browser tab, so the chat survives moving between pages
  var items = [];
  try { items = JSON.parse(read('sessionStorage', STORE) || '[]'); if (!Array.isArray(items)) items = []; } catch (e) { items = []; }
  var waiting = false, unread = false;
  function save() { write('sessionStorage', STORE, JSON.stringify(items.slice(-24))); }

  function applyText() {
    T = TEXT[lang] || TEXT.en;
    $('tk-ask-sub').textContent = T.sub;
    $('tk-ask-lang').textContent = T.lang; $('tk-ask-lang').setAttribute('aria-label', T.langLabel); $('tk-ask-lang').title = T.langLabel;
    $('tk-ask-min').setAttribute('aria-label', T.min); $('tk-ask-min').title = T.min;
    $('tk-ask-close').setAttribute('aria-label', T.close); $('tk-ask-close').title = T.close;
    $('tk-ask-note').textContent = T.note;
    $('tk-ask-privacy').textContent = T.privacy;
    $('tk-ask-human').textContent = T.human;
    if (READY) { $('tk-ask-reset').setAttribute('aria-label', T.reset); $('tk-ask-reset').title = T.reset; }
    $('tk-ask-teaser-open').textContent = T.teaser;
    if (READY) { send.setAttribute('aria-label', T.send); send.title = T.send; q.placeholder = T.hint; $('tk-ask-q-label').textContent = T.you; }
    label();
  }
  function label() {
    $('tk-ask-fab-label').textContent = unread ? T.fresh : (items.length ? T.resume : T.fab);
    $('tk-ask-dot').hidden = !unread;
  }

  function safeLocal(u) { return typeof u === 'string' && u.charAt(0) === '/' && u.charAt(1) !== '/'; }
  function clock(t) { if (!t) return ''; var d = new Date(t); return isNaN(d) ? '' : d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }

  // An answer as paragraphs and lists, with [n] turned into links to its sources. Text only, never HTML.
  function addInline(parent, text, srcByNum) {
    var re = /\[(\d{1,2})\]/g, last = 0, m;
    while ((m = re.exec(text)) !== null) {
      if (m.index > last) parent.appendChild(document.createTextNode(text.slice(last, m.index)));
      var s = srcByNum[m[1]];
      if (s) {
        var a = el('a', 'tk-ask__cite', m[1]);
        a.href = s.url; a.title = s.title;
        parent.appendChild(a);
      }
      last = re.lastIndex;
    }
    if (last < text.length) parent.appendChild(document.createTextNode(text.slice(last)));
  }
  function formatAnswer(box, text, sources) {
    var srcByNum = {};
    (sources || []).forEach(function (s) { if (safeLocal(s.url)) srcByNum[String(s.number)] = s; });
    String(text || '').split(/\n\s*\n/).forEach(function (block) {
      var lines = block.split('\n').filter(function (l) { return l.trim() !== ''; });
      if (!lines.length) return;
      if (lines.every(function (l) { return /^\s*[-•*]\s+/.test(l); })) {
        var ul = el('ul');
        lines.forEach(function (l) { var li = el('li'); addInline(li, l.replace(/^\s*[-•*]\s+/, ''), srcByNum); ul.appendChild(li); });
        box.appendChild(ul);
      } else {
        var p = el('p'); addInline(p, lines.join('\n'), srcByNum); box.appendChild(p);
      }
    });
  }

  function sourceList(list) {
    list = (list || []).filter(function (s) { return safeLocal(s.url); });
    if (!list.length) return null;
    var wrap = el('div');
    wrap.appendChild(el('p', 'tk-ask__sources-label', T.sources));
    var ol = el('ol', 'tk-ask__sources');
    list.forEach(function (s) {
      var li = el('li'), a = el('a', 'tk-ask__source');
      a.href = s.url;
      a.appendChild(el('span', 'tk-ask__source-n', String(s.number)));
      var t = el('span');
      t.appendChild(el('span', 'tk-ask__source-t', s.title));
      if (s.date) t.appendChild(el('span', 'tk-ask__source-d', s.date));
      a.appendChild(t);
      li.appendChild(a);
      ol.appendChild(li);
    });
    wrap.appendChild(ol);
    return wrap;
  }

  function tools(m) {
    var bar = el('div', 'tk-ask__tools');
    if (m.ref) {
      [[1, T.helpful, 'M7 10v11|M15 5.9L14 10h5.8a2 2 0 012 2.3l-1.4 7A2 2 0 0118.4 21H7V10l4.3-7.1A1.5 1.5 0 0114 4.3z'],
       [-1, T.notHelpful, 'M17 14V3|M9 18.1L10 14H4.2a2 2 0 01-2-2.3l1.4-7A2 2 0 015.6 3H17v11l-4.3 7.1A1.5 1.5 0 0110 19.7z']].forEach(function (r) {
        var b = el('button', 'tk-ask__tool'); b.type = 'button';
        b.setAttribute('aria-label', r[1]); b.title = r[1];
        b.setAttribute('aria-pressed', m.rated === r[0] ? 'true' : 'false');
        b.disabled = !!m.rated;
        b.appendChild(svg(r[2]));
        b.addEventListener('click', function () { rate(m, r[0]); });
        bar.appendChild(b);
      });
    }
    var c = el('button', 'tk-ask__tool'); c.type = 'button';
    c.setAttribute('aria-label', T.copy); c.title = T.copy;
    c.appendChild(svg('M9 9h10v10H9z|M5 15V5h10'));
    c.addEventListener('click', function () {
      var done = function () { c.title = T.copied; c.setAttribute('aria-label', T.copied); c.setAttribute('aria-pressed', 'true'); };
      try { navigator.clipboard.writeText(m.text).then(done, function () {}); } catch (e) {}
    });
    bar.appendChild(c);
    if (m.rated) bar.appendChild(el('span', 'tk-ask__thanks', T.thanks));
    return bar;
  }

  function botRow(children) {
    var row = el('div', 'tk-ask__row');
    var av = el('span', 'tk-ask__avatar tk-ask__avatar--sm', 'T'); av.setAttribute('aria-hidden', 'true');
    var col = el('div', 'tk-ask__col');
    children.forEach(function (c) { if (c) col.appendChild(c); });
    row.appendChild(av); row.appendChild(col);
    log.appendChild(row);
    return col;
  }
  function chips(list, start) {
    var row = el('div', 'tk-ask__chips' + (start ? ' tk-ask__chips--start' : ''));
    list.forEach(function (c) {
      var b = el('button', null, c); b.type = 'button';
      b.addEventListener('click', function () { ask(c); });
      row.appendChild(b);
    });
    log.appendChild(row);
  }
  // Again once fonts and layout settle, so the newest message is fully in view
  function bottom() { log.scrollTop = log.scrollHeight; setTimeout(function () { log.scrollTop = log.scrollHeight; }, 120); }

  function render() {
    log.textContent = '';
    if (!READY) {
      var b = el('div', 'tk-ask__msg'); formatAnswer(b, T.offline, []);
      var c = el('a', 'tk-btn tk-btn--outline tk-btn--sm tk-ask__contact', T.contact); c.href = '/contact';
      botRow([b, c]);
      return;
    }
    var w = el('div', 'tk-ask__msg'); formatAnswer(w, T.welcome, []);
    botRow([w]);
    if (!items.length) chips(T.chips, true);
    var lastBot = -1;
    items.forEach(function (m, i) { if (!m.me) lastBot = i; });
    items.forEach(function (m, i) {
      if (m.me) {
        var row = el('div', 'tk-ask__row tk-ask__row--me'), col = el('div', 'tk-ask__col');
        col.appendChild(el('div', 'tk-ask__msg tk-ask__msg--me', m.text));
        if (m.t) col.appendChild(el('span', 'tk-ask__time', clock(m.t)));
        row.appendChild(col); log.appendChild(row);
        return;
      }
      var box = el('div', 'tk-ask__msg' + (m.err ? ' tk-ask__msg--err' : ''));
      formatAnswer(box, m.text, m.sources);
      var parts = [box, sourceList(m.sources)];
      if (m.err) {
        var r = el('button', 'tk-ask__retry', T.retry); r.type = 'button';
        r.addEventListener('click', function () { retry(i); });
        parts.push(r);
      } else {
        parts.push(tools(m));
      }
      botRow(parts);
      if (i === lastBot && !waiting && !m.err && m.suggestions && m.suggestions.length && i === items.length - 1) chips(m.suggestions, false);
    });
    if (waiting) {
      var ty = el('div', 'tk-ask__typing');
      ty.appendChild(document.createTextNode(T.typing));
      var dots = el('span', 'tk-ask__dots'); dots.innerHTML = '<i></i><i></i><i></i>';
      ty.appendChild(dots);
      botRow([ty]);
    }
    bottom();
  }

  function hideTeaser() { teaser.hidden = true; }
  function open() {
    hideTeaser(); write('sessionStorage', SEEN, '1'); write('sessionStorage', OPEN, '1');
    unread = false; label();
    panel.hidden = false; fab.hidden = true; fab.setAttribute('aria-expanded', 'true');
    render();
    (READY ? q : $('tk-ask-min')).focus();
  }
  // Minimise: hide the panel and keep the conversation
  function minimise() {
    write('sessionStorage', OPEN, '0');
    panel.hidden = true; fab.hidden = false; fab.setAttribute('aria-expanded', 'false');
    label(); fab.focus();
  }
  // End: clear the conversation, then hide
  function end() { items = []; save(); unread = false; minimise(); }

  fab.addEventListener('click', open);
  $('tk-ask-teaser-open').addEventListener('click', open);
  $('tk-ask-teaser-x').addEventListener('click', function () { hideTeaser(); write('sessionStorage', SEEN, '1'); });
  $('tk-ask-min').addEventListener('click', minimise);
  $('tk-ask-close').addEventListener('click', end);
  $('tk-ask-lang').addEventListener('click', function () { lang = lang === 'sw' ? 'en' : 'sw'; write('localStorage', LANG, lang); applyText(); render(); });
  panel.addEventListener('keydown', function (e) { if (e.key === 'Escape') minimise(); });
  if (READY) $('tk-ask-reset').addEventListener('click', function () { if (waiting) return; items = []; save(); render(); q.focus(); });

  applyText();
  // Stay open from page to page on larger screens, if it was open
  if (read('sessionStorage', OPEN) === '1' && window.innerWidth >= 768) {
    open();
  } else if (!read('sessionStorage', SEEN)) {
    // A short greeting, once per visit
    setTimeout(function () { if (panel.hidden && !read('sessionStorage', SEEN)) { teaser.hidden = false; write('sessionStorage', SEEN, '1'); } }, 4000);
  }

  if (!READY) return;

  function post(body) {
    body.token = TOKEN;
    return fetch('/api/assistant', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
      .then(function (r) { return r.json(); });
  }
  function rate(m, value) {
    if (m.rated || !m.ref) return;
    m.rated = value; save(); render();
    post({ action: 'feedback', ref: m.ref, rating: value }).catch(function () {});
  }
  function retry(i) {
    var question = null;
    for (var k = i - 1; k >= 0; k--) if (items[k].me) { question = items[k].text; items.splice(k, i - k + 1); break; }
    save();
    if (question) ask(question);
  }
  function ask(text) {
    text = (text || '').trim();
    if (!text || waiting) return;
    var history = items.filter(function (m) { return !m.err; }).slice(-6).map(function (m) { return { role: m.me ? 'user' : 'assistant', text: m.text }; });
    items.push({ me: true, text: text, t: Date.now() });
    waiting = true;
    save(); render();
    q.value = ''; grow(); update();
    post({ question: text, history: history, page: location.pathname })
      .then(function (j) {
        if (j && j.ok) items.push({ text: j.answer, sources: j.sources || [], suggestions: j.suggestions || [], ref: j.ref || null, t: Date.now() });
        else items.push({ text: (j && (j.answer || j.error)) || T.error, err: !(j && j.answer), sources: [], t: Date.now() });
      })
      .catch(function () { items.push({ text: T.error, err: true, sources: [], t: Date.now() }); })
      .finally(function () {
        waiting = false; save();
        if (panel.hidden) { unread = true; label(); } else { render(); q.focus(); }
      });
  }

  // The message box grows with what is typed, up to a few lines
  function grow() { q.style.height = 'auto'; q.style.height = Math.min(q.scrollHeight, 120) + 'px'; }
  function update() {
    var left = 500 - q.value.length, c = $('tk-ask-count');
    c.textContent = left <= 100 ? left + T.count : '';
    c.className = 'tk-ask__count' + (left <= 20 ? ' tk-ask__count--max' : '');
    send.disabled = waiting || q.value.trim() === '';
  }
  q.addEventListener('input', function () { grow(); update(); });
  q.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); if (!send.disabled) $('tk-ask-form').requestSubmit(); }
  });
  $('tk-ask-form').addEventListener('submit', function (e) { e.preventDefault(); ask(q.value); });
})();
</script>
<?php endif; ?>
