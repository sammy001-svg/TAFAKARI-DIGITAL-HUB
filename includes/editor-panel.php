<?php
declare(strict_types=1);
/**
 * Sticky publishing panel for the content editors (new + edit).
 *
 * The panel sits inside the editor <form>, so its buttons submit the form
 * exactly as before; it adds a live checklist of what a report still needs.
 *
 * @param string $title      heading, e.g. "New article" or "Edit report"
 * @param string $note       one line about what saving will do
 * @param string $actions    the form's submit buttons and cancel link (HTML)
 * @param string $statusHtml optional badge showing the current status
 */
function editor_panel(string $title, string $note, string $actions, string $statusHtml = ''): void {
    ?>
  <aside class="tk-editor__side" aria-label="Publishing">
    <div class="tk-card tk-publish">
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
          <span class="tk-eyebrow">Publishing</span>
          <?= $statusHtml ?>
        </div>
        <h2 class="tk-h3" style="margin-top:12px"><?= h($title) ?></h2>
        <p class="tk-help" style="margin-top:5px"><?= h($note) ?></p>
      </div>
      <div>
        <div class="tk-meter" aria-hidden="true"><span id="pub-meter" style="width:0%"></span></div>
        <p class="tk-help" id="pub-meter-label" style="margin-top:7px" aria-live="polite">Checking what is ready…</p>
      </div>
      <ul class="tk-check" id="pub-check">
        <li data-check="title">Title</li>
        <li data-check="place">Country and region</li>
        <li data-check="categories">Issue category</li>
        <li data-check="media">Media or file</li>
        <li data-check="scores" class="is-optional">Intensity scores</li>
      </ul>
      <div class="tk-publish__actions"><?= $actions ?></div>
    </div>
    <div class="tk-card" style="padding:16px 18px">
      <p class="tk-section-title" style="margin-bottom:8px">Good to know</p>
      <p class="tk-help">Only published documents appear on the heat map. Score the elements a report addresses;
        leave the others at &ldquo;&mdash;&rdquo;. <a href="/methodology" target="_blank" rel="noopener" style="color:var(--tk-brand);font-weight:700">How scores are used</a></p>
    </div>
  </aside>
<script>
/* Live checklist: reads the form as the user types; never blocks saving */
(function () {
  var form = document.currentScript.closest('form');
  if (!form) return;
  function val(name) { var el = form.querySelector('[name="' + name + '"]'); return el ? String(el.value || '').trim() : ''; }
  function type() {
    var r = form.querySelector('[name="type"]:checked') || form.querySelector('[name="type"]');
    return r ? r.value : 'ARTICLE';
  }
  function visible(el) { return !!(el && el.offsetParent !== null); }
  function update() {
    var t = type();
    var catBox = form.querySelector('[name="issueCategories[]"]');
    var needsCats = catBox && visible(catBox.closest('[id="category-field"]') || catBox.closest('div'));
    var needsMedia = t !== 'ARTICLE';
    var rows = {
      title: { done: val('title') !== '', show: true },
      place: { done: val('country') !== '' && val('region') !== '', show: true },
      categories: { done: !!form.querySelector('[name="issueCategories[]"]:checked'), show: t !== 'GALLERY_IMAGE' },
      media: { done: val('mediaUrl') !== '', show: needsMedia },
      scores: { done: Array.prototype.some.call(form.querySelectorAll('.int-range'), function (r) { return +r.value > 0; }), show: !!form.querySelector('.int-range') }
    };
    var need = 0, have = 0;
    Object.keys(rows).forEach(function (k) {
      var li = document.querySelector('#pub-check [data-check="' + k + '"]');
      if (!li) return;
      li.hidden = !rows[k].show;
      li.classList.toggle('is-done', rows[k].done);
      if (rows[k].show && k !== 'scores') { need++; if (rows[k].done) have++; }
    });
    var pct = need ? Math.round(100 * have / need) : 100;
    document.getElementById('pub-meter').style.width = pct + '%';
    document.getElementById('pub-meter-label').textContent = have === need
      ? 'Everything required is filled in.'
      : (need - have) + ' required item' + (need - have === 1 ? '' : 's') + ' still to fill in.';
  }
  form.addEventListener('input', update);
  form.addEventListener('change', update);
  document.addEventListener('DOMContentLoaded', update);
  setInterval(update, 1500);   // upload widgets set hidden fields without events
})();
</script>
    <?php
}
