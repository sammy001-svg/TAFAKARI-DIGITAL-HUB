<?php
/**
 * The floating chat button and panel, on every public page.
 *
 * Shown unless a super admin has switched the chat off. When the AI is not
 * ready (no key, or the Claude software missing), it still opens and offers
 * the contact details instead, so visitors are never left without help.
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
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 01-11.6 7.1L4 20l1-4.6A8 8 0 1121 12z"/></svg>
  <span id="tk-ask-fab-label">Chat with us</span>
  <span class="tk-ask-fab__dot" id="tk-ask-dot" hidden></span>
</button>
<section class="tk-ask no-print" id="tk-ask" role="dialog" aria-modal="false" aria-labelledby="tk-ask-title" hidden>
  <header class="tk-ask__head">
    <div>
      <h2 id="tk-ask-title">Tafakari</h2>
      <p id="tk-ask-sub"></p>
    </div>
    <div class="tk-ask__actions">
      <button type="button" class="tk-ask__icon" id="tk-ask-min">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 12h12"/></svg>
      </button>
      <button type="button" class="tk-ask__icon" id="tk-ask-close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>
  </header>
  <div class="tk-ask__body" id="tk-ask-log" aria-live="polite"></div>
  <?php if ($_askReady): ?>
    <form class="tk-ask__form" id="tk-ask-form">
      <label for="tk-ask-q" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">Your message</label>
      <textarea id="tk-ask-q" rows="2" maxlength="500" class="tk-textarea" style="min-height:0" required></textarea>
      <button type="submit" class="tk-btn tk-btn--primary" id="tk-ask-send"></button>
    </form>
  <?php endif; ?>
  <p class="tk-ask__note"><span id="tk-ask-note"></span> <button type="button" id="tk-ask-reset"></button></p>
</section>
<script>
(function () {
  var READY = <?= $_askReady ? 'true' : 'false' ?>;
  var TOKEN = <?= json_encode($_askReady ? $_SESSION['assistant_csrf'] : '') ?>;
  var STORE = 'tk-chat-v1', SEEN = 'tk-chat-greeted', OPEN = 'tk-chat-open';
  var $ = function (id) { return document.getElementById(id); };
  var panel = $('tk-ask'), fab = $('tk-ask-open'), log = $('tk-ask-log'), q = $('tk-ask-q'), send = $('tk-ask-send'), teaser = $('tk-ask-teaser');

  function read(s, k) { try { return window[s].getItem(k); } catch (e) { return null; } }
  function write(s, k, v) { try { window[s].setItem(k, v); } catch (e) {} }

  var sw = read('localStorage', 'tafakari-locale') === 'sw';
  var EN = {
    fab: 'Chat with us', resume: 'Continue chat', sub: 'AI assistant · Msaidizi wa AI', send: 'Send', hint: 'Type in English or Kiswahili',
    min: 'Minimise chat', close: 'End chat',
    teaser: 'Hello! Welcome to Tafakari. Can I help you today?',
    note: 'AI can make mistakes, so please check the sources. Don’t share personal details. This service can’t help in an emergency.',
    reset: 'Start over', error: 'Sorry, I can’t be reached right now. Please try again.',
    offline: 'Hello, and welcome to Tafakari! Our chat assistant is resting just now. You can still reach CRTP by email at crtp.communications@hekima.ac.ke or through our Contact page.'
  };
  var SW = {
    fab: 'Zungumza nasi', resume: 'Endelea kuzungumza', sub: 'Msaidizi wa AI · AI assistant', send: 'Tuma', hint: 'Andika kwa Kiswahili au Kiingereza',
    min: 'Punguza', close: 'Maliza mazungumzo',
    teaser: 'Habari! Karibu Tafakari. Naweza kukusaidia leo?',
    note: 'AI inaweza kukosea, tafadhali hakiki vyanzo. Usitoe taarifa zako binafsi. Huduma hii haiwezi kusaidia wakati wa dharura.',
    reset: 'Anza upya', error: 'Samahani, siwezi kupatikana kwa sasa. Tafadhali jaribu tena.',
    offline: 'Habari, karibu Tafakari! Msaidizi wetu wa mazungumzo amepumzika kwa sasa. Bado unaweza kuwasiliana na CRTP kwa barua pepe crtp.communications@hekima.ac.ke au kupitia ukurasa wetu wa Mawasiliano.'
  };
  var T = sw ? SW : EN;
  var WELCOME_EN = 'Hello, and welcome to Tafakari! I’m CRTP’s assistant. Ask me about our work, our reports, or peace and conflict in the region.';
  var WELCOME_SW = 'Habari, karibu Tafakari! Mimi ni msaidizi wa CRTP. Niulize kuhusu kazi zetu, ripoti zetu, au hali ya amani na migogoro katika ukanda wetu.';
  var CHIPS_EN = ['What does CRTP do?', 'How does the heat map work?'];
  var CHIPS_SW = ['CRTP inafanya nini?', 'Hali ikoje Sudani Kusini?'];

  $('tk-ask-sub').textContent = T.sub;
  $('tk-ask-min').setAttribute('aria-label', T.min); $('tk-ask-min').title = T.min;
  $('tk-ask-close').setAttribute('aria-label', T.close); $('tk-ask-close').title = T.close;
  $('tk-ask-note').textContent = READY ? T.note : '';
  $('tk-ask-reset').textContent = T.reset;
  $('tk-ask-reset').hidden = !READY;
  $('tk-ask-teaser-open').textContent = T.teaser;
  if (READY) { send.textContent = T.send; q.placeholder = T.hint; }

  // Kept only in this browser tab, so the chat survives moving between pages
  var items = [];
  try { items = JSON.parse(read('sessionStorage', STORE) || '[]'); if (!Array.isArray(items)) items = []; } catch (e) { items = []; }
  function save() { write('sessionStorage', STORE, JSON.stringify(items.slice(-20))); }
  function label() {
    $('tk-ask-fab-label').textContent = items.length ? T.resume : T.fab;
    $('tk-ask-dot').hidden = !items.length;
  }

  function safeLocal(u) { return typeof u === 'string' && u.charAt(0) === '/' && u.charAt(1) !== '/'; }
  function bubble(cls, text) {
    var p = document.createElement('div');
    p.className = 'tk-ask__msg ' + cls;
    p.textContent = text;                 // always text: answers are never treated as HTML
    log.appendChild(p);
    return p;
  }
  function addSources(list) {
    list = (list || []).filter(function (s) { return safeLocal(s.url); });
    if (!list.length) return;
    var ul = document.createElement('ol');
    ul.className = 'tk-ask__sources';
    list.forEach(function (s) {
      var li = document.createElement('li'), a = document.createElement('a');
      li.value = s.number;
      a.href = s.url;
      a.textContent = s.title + (s.date ? ' (' + s.date + ')' : '');
      li.appendChild(a);
      ul.appendChild(li);
    });
    log.appendChild(ul);
  }
  function bottom() { log.scrollTop = log.scrollHeight; }
  function render() {
    log.textContent = '';
    if (!READY) {
      bubble('', T.offline);
      var c = document.createElement('a');
      c.className = 'tk-btn tk-btn--outline tk-btn--sm tk-ask__contact';
      c.href = '/contact';
      c.textContent = sw ? 'Wasiliana nasi' : 'Contact CRTP';
      log.appendChild(c);
      return;
    }
    bubble('', (sw ? WELCOME_SW : WELCOME_EN));
    bubble('', (sw ? WELCOME_EN : WELCOME_SW));
    if (!items.length) {
      var row = document.createElement('div');
      row.className = 'tk-ask__chips';
      (sw ? CHIPS_SW.concat(CHIPS_EN) : CHIPS_EN.concat(CHIPS_SW)).forEach(function (c) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = c;
        b.addEventListener('click', function () { ask(c); });
        row.appendChild(b);
      });
      log.appendChild(row);
    }
    items.forEach(function (m) {
      bubble(m.me ? 'tk-ask__msg--me' : '', m.text);
      if (!m.me) addSources(m.sources);
    });
    bottom();
  }

  function hideTeaser() { teaser.hidden = true; }
  function open() {
    hideTeaser(); write('sessionStorage', SEEN, '1'); write('sessionStorage', OPEN, '1');
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
  function end() { items = []; save(); minimise(); }

  fab.addEventListener('click', open);
  $('tk-ask-teaser-open').addEventListener('click', open);
  $('tk-ask-teaser-x').addEventListener('click', function () { hideTeaser(); write('sessionStorage', SEEN, '1'); });
  $('tk-ask-min').addEventListener('click', minimise);
  $('tk-ask-close').addEventListener('click', end);
  panel.addEventListener('keydown', function (e) { if (e.key === 'Escape') minimise(); });
  $('tk-ask-reset').addEventListener('click', function () { items = []; save(); render(); if (READY) q.focus(); });

  label();
  // Stay open from page to page on larger screens, if it was open
  if (read('sessionStorage', OPEN) === '1' && window.innerWidth >= 768) {
    open();
  } else if (!read('sessionStorage', SEEN)) {
    // A short greeting, once per visit
    setTimeout(function () { if (panel.hidden && !read('sessionStorage', SEEN)) { teaser.hidden = false; write('sessionStorage', SEEN, '1'); } }, 4000);
  }

  if (!READY) return;

  function ask(text) {
    if (!text || send.disabled) return;
    var history = items.slice(-6).map(function (m) { return { role: m.me ? 'user' : 'assistant', text: m.text }; });
    items.push({ me: true, text: text });
    save(); render();
    q.value = '';
    send.disabled = true;
    var wait = document.createElement('div');
    wait.className = 'tk-ask__typing';
    wait.setAttribute('aria-label', '…');
    wait.innerHTML = '<i></i><i></i><i></i>';
    log.appendChild(wait);
    bottom();
    fetch('/api/assistant', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
                              body: JSON.stringify({ question: text, history: history, token: TOKEN }) })
      .then(function (r) { return r.json(); })
      .then(function (j) { items.push({ text: j.answer || j.error || T.error, sources: j.sources || [] }); })
      .catch(function () { items.push({ text: T.error, sources: [] }); })
      .finally(function () { send.disabled = false; save(); if (!panel.hidden) { render(); q.focus(); } label(); });
  }
  q.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $('tk-ask-form').requestSubmit(); }
  });
  $('tk-ask-form').addEventListener('submit', function (e) { e.preventDefault(); ask(q.value.trim()); });
})();
</script>
<?php endif; ?>
