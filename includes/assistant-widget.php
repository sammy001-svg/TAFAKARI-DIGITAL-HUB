<?php
/** Ask Tafakari launcher and panel. Included by the footer; renders only when enabled. */
try {
    require_once __DIR__ . '/agent.php';
    require_once __DIR__ . '/assistant.php';
    $_askOn = assistant_available();
} catch (Throwable $e) {
    $_askOn = false;
}
if ($_askOn):
    if (empty($_SESSION['assistant_csrf'])) $_SESSION['assistant_csrf'] = bin2hex(random_bytes(16));
?>
<button type="button" class="tk-ask-fab no-print" id="tk-ask-open" aria-haspopup="dialog" aria-controls="tk-ask">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 01-11.6 7.1L4 20l1-4.6A8 8 0 1121 12z"/></svg>
  Ask Tafakari
</button>
<section class="tk-ask no-print" id="tk-ask" role="dialog" aria-modal="false" aria-labelledby="tk-ask-title" hidden>
  <header class="tk-ask__head">
    <div>
      <h2 id="tk-ask-title">Ask Tafakari</h2>
      <p>Answers from CRTP's published work only.</p>
    </div>
    <button type="button" class="tk-icon-btn" id="tk-ask-close" aria-label="Close" style="color:#fff">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
  </header>
  <div class="tk-ask__body" id="tk-ask-log" aria-live="polite">
    <p class="tk-ask__intro">Ask about a country, a report or how the heat map works. For example: <em>What are the latest developments in South Sudan?</em></p>
  </div>
  <form class="tk-ask__form" id="tk-ask-form">
    <label for="tk-ask-q" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">Your question</label>
    <textarea id="tk-ask-q" rows="2" maxlength="500" class="tk-textarea" style="min-height:0" placeholder="Type your question" required></textarea>
    <button type="submit" class="tk-btn tk-btn--primary" id="tk-ask-send">Ask</button>
  </form>
  <p class="tk-ask__note">AI can make mistakes, so check the linked sources. Don't enter personal details. This service can't help in an emergency.</p>
</section>
<script>
(function () {
  var TOKEN = <?= json_encode($_SESSION['assistant_csrf']) ?>;
  var $ = function (id) { return document.getElementById(id); };
  var panel = $('tk-ask'), fab = $('tk-ask-open'), log = $('tk-ask-log'), q = $('tk-ask-q'), send = $('tk-ask-send');
  function open()  { panel.hidden = false; fab.hidden = true; q.focus(); }
  function close() { panel.hidden = true; fab.hidden = false; fab.focus(); }
  fab.addEventListener('click', open);
  $('tk-ask-close').addEventListener('click', close);
  panel.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  function bubble(cls, text) {
    var p = document.createElement('div');
    p.className = 'tk-ask__msg ' + cls;
    p.textContent = text;                 // always text: answers are never treated as HTML
    log.appendChild(p);
    log.scrollTop = log.scrollHeight;
    return p;
  }
  function safeLocal(u) { return typeof u === 'string' && u.charAt(0) === '/' && u.charAt(1) !== '/'; }
  function addSources(list) {
    if (!list || !list.length) return;
    var ul = document.createElement('ol');
    ul.className = 'tk-ask__sources';
    list.forEach(function (s) {
      if (!safeLocal(s.url)) return;
      var li = document.createElement('li'), a = document.createElement('a');
      li.value = s.number;
      a.href = s.url;
      a.textContent = s.title + (s.date ? ' (' + s.date + ')' : '');
      li.appendChild(a);
      ul.appendChild(li);
    });
    log.appendChild(ul);
    log.scrollTop = log.scrollHeight;
  }
  q.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $('tk-ask-form').requestSubmit(); }
  });
  $('tk-ask-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var text = q.value.trim();
    if (!text || send.disabled) return;
    bubble('tk-ask__msg--me', text);
    q.value = '';
    send.disabled = true;
    var wait = bubble('tk-ask__msg--wait', 'Searching published work…');
    fetch('/api/assistant', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
                              body: JSON.stringify({ question: text, token: TOKEN }) })
      .then(function (r) { return r.json(); })
      .then(function (j) { wait.remove(); bubble('', j.answer || j.error || 'Sorry, something went wrong.'); addSources(j.sources); })
      .catch(function () { wait.remove(); bubble('', 'Sorry, the assistant could not be reached. Please try again.'); })
      .finally(function () { send.disabled = false; q.focus(); });
  });
})();
</script>
<?php endif; ?>
