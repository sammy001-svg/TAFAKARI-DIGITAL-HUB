<?php
/**
 * Intensity scoring block for the content forms.
 *
 * Renders one card per heat-map category containing a 0-10 slider for each of
 * its component elements. 0 means "not assessed" and is excluded from the
 * average; 1-10 is the standard severity scale where 10 is severe/critical.
 *
 * Field names are intensity[<element name>], read back with
 * post_intensity_scores() / heatmap_category_scores().
 */

/**
 * @param array    $current     element => score
 * @param int      $sourceCount independent corroborating sources (>= 1)
 * @param array    $provenance  ['source' => ?string, 'by' => ?string name, 'at' => ?string]
 *                              from the stored post; [] when creating
 */
function intensity_scoring_fields(array $current = [], int $sourceCount = 1, array $provenance = []): void {
    $tax = heatmap_taxonomy();
    $sourceCount = max(1, min(99, $sourceCount));
    ?>
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
      <div class="flex items-start justify-between gap-4 mb-2">
        <h2 class="font-outfit font-bold text-lg text-slate-900">Intensity Assessment</h2>
        <span id="int-overall" class="text-[11px] font-bold px-2.5 py-1 rounded-full"
              style="background:#f1f5f9;color:#64748b">Not assessed</span>
      </div>
      <p class="text-xs text-slate-400 mb-6 leading-relaxed">
        Score each element from 1 (minimal) to 10 (severe / critical). Leave an element at
        &ldquo;&mdash;&rdquo; if it was not assessed &mdash; it is excluded from the average.
        Each category cell on the heat map combines these scores across reports, weighted by
        corroboration and recency &mdash; see <a href="/methodology" target="_blank" rel="noopener"
        class="font-semibold underline" style="color:#750B25">the published methodology</a>.
      </p>

      <?php if (!empty($provenance['source']) || !empty($provenance['at'])): ?>
        <p class="text-[11px] text-slate-500 mb-5 px-3 py-2 rounded-xl bg-slate-50 border border-slate-100">
          <span class="font-bold"><?= h(score_source_label($provenance['source'] ?? null)) ?></span>
          <?php if (!empty($provenance['by'])): ?> &middot; last set by <?= h($provenance['by']) ?><?php endif; ?>
          <?php if (!empty($provenance['at'])): ?> &middot; <?= h(format_date($provenance['at'], 'j M Y, H:i')) ?><?php endif; ?>
        </p>
      <?php endif; ?>

      <div class="flex flex-wrap items-center gap-3 mb-6 px-4 py-3 rounded-2xl border border-slate-200">
        <label for="int-sources" class="text-xs font-bold text-slate-700 flex-1 min-w-[220px] leading-snug">
          Independent corroborating sources
          <span class="block font-normal text-slate-400 mt-0.5">
            How many separate, independent sources report this. More corroboration gives the
            report more weight (up to double, at 5 or more).
          </span>
        </label>
        <input type="number" id="int-sources" name="sourceCount" min="1" max="99" step="1"
               value="<?= (int)$sourceCount ?>"
               class="w-24 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm font-bold text-center focus:outline-none">
      </div>

      <div class="space-y-3">
        <?php foreach ($tax as $cat):
          $cname = $cat['name'];
          $ccol  = $cat['color'] ?: '#94a3b8';
          $vals = [];
          foreach ($cat['elements'] as $el) {
            $v = (int)($current[$el] ?? 0);
            if ($v >= 1 && $v <= 10) $vals[] = $v;
          }
          $avg    = $vals ? round(array_sum($vals) / count($vals), 1) : null;
          $isOpen = (bool)$vals;
        ?>
          <div class="int-cat rounded-2xl border border-slate-200 overflow-hidden"
               data-col="<?= h($ccol) ?>" style="border-left:4px solid <?= h($ccol) ?>">
            <button type="button" onclick="intToggle(this)"
                    class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-slate-50 transition-colors">
              <span class="flex items-center gap-2.5 min-w-0">
                <span style="width:10px;height:10px;border-radius:50%;background:<?= h($ccol) ?>;flex-shrink:0"></span>
                <span class="font-outfit font-bold text-sm text-slate-900 truncate"><?= h($cname) ?></span>
                <span class="text-[10px] text-slate-400 shrink-0"><?= count($cat['elements']) ?> elements</span>
              </span>
              <span class="flex items-center gap-2 shrink-0">
                <span class="int-cat-avg text-[11px] font-black px-2.5 py-1 rounded-full"
                      style="<?= $avg !== null ? 'background:' . h($ccol) . '1a;color:' . h($ccol) : 'background:#f1f5f9;color:#94a3b8' ?>"><?= $avg !== null ? h((string)$avg) . ' / 10' : '&mdash;' ?></span>
                <svg class="int-chev" width="14" height="14" fill="none" stroke="#94a3b8" stroke-width="2.5"
                     viewBox="0 0 24 24" style="transition:transform .2s<?= $isOpen ? ';transform:rotate(180deg)' : '' ?>">
                  <path d="M19 9l-7 7-7-7"/>
                </svg>
              </span>
            </button>

            <div class="int-body px-4 pb-4 pt-1 space-y-3<?= $isOpen ? '' : ' hidden' ?>">
              <?php foreach ($cat['elements'] as $el):
                $v = (int)($current[$el] ?? 0);
                if ($v < 0 || $v > 10) $v = 0;
              ?>
                <div class="flex items-center gap-3">
                  <label class="text-xs text-slate-600 flex-1 min-w-0 leading-snug"><?= h($el) ?></label>
                  <input type="range" min="0" max="10" step="1" value="<?= $v ?>"
                         class="int-range" name="intensity[<?= h($el) ?>]"
                         oninput="intSync(this)"
                         style="width:150px;flex-shrink:0;accent-color:<?= h($ccol) ?>">
                  <span class="int-val text-xs font-black text-center shrink-0"
                        style="width:30px;color:<?= $v ? h($ccol) : '#cbd5e1' ?>"><?= $v ?: '&mdash;' ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}

function intensity_scoring_scripts(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    ?>
    <script>
    function intToggle(btn) {
      var card = btn.closest('.int-cat');
      var body = card.querySelector('.int-body');
      var chev = card.querySelector('.int-chev');
      var nowHidden = body.classList.toggle('hidden');
      chev.style.transform = nowHidden ? '' : 'rotate(180deg)';
    }

    function intTier(avg) {
      if (avg >= 9) return ['Critical', '#ED1C24'];
      if (avg >= 7) return ['High',     '#E7952A'];
      if (avg >= 5) return ['Moderate', '#F59E0B'];
      if (avg >= 2) return ['Low',      '#65A30D'];
      return ['Stable', '#059669'];
    }

    function intRecalc(card) {
      var col = card.getAttribute('data-col') || '#94a3b8';
      var vals = [];
      card.querySelectorAll('.int-range').forEach(function (r) {
        var v = parseInt(r.value, 10);
        if (v >= 1) vals.push(v);
      });
      var badge = card.querySelector('.int-cat-avg');
      if (vals.length) {
        var sum = 0;
        for (var i = 0; i < vals.length; i++) sum += vals[i];
        var avg = Math.round((sum / vals.length) * 10) / 10;
        badge.textContent = avg.toFixed(1) + ' / 10';
        badge.style.background = col + '1a';
        badge.style.color = col;
      } else {
        badge.innerHTML = '&mdash;';
        badge.style.background = '#f1f5f9';
        badge.style.color = '#94a3b8';
      }
      intOverall();
    }

    function intOverall() {
      var all = [];
      document.querySelectorAll('.int-range').forEach(function (r) {
        var v = parseInt(r.value, 10);
        if (v >= 1) all.push(v);
      });
      var el = document.getElementById('int-overall');
      if (!el) return;
      if (!all.length) {
        el.textContent = 'Not assessed';
        el.style.background = '#f1f5f9';
        el.style.color = '#64748b';
        return;
      }
      var sum = 0;
      for (var i = 0; i < all.length; i++) sum += all[i];
      var avg = sum / all.length;
      var t = intTier(avg);
      el.textContent = avg.toFixed(1) + ' / 10 · ' + t[0] + ' (' + all.length + ' scored)';
      el.style.background = t[1] + '1a';
      el.style.color = t[1];
    }

    function intSync(range) {
      var card = range.closest('.int-cat');
      var out  = range.parentElement.querySelector('.int-val');
      var v    = parseInt(range.value, 10);
      if (out) {
        out.innerHTML = v ? v : '&mdash;';
        out.style.color = v ? (card.getAttribute('data-col') || '#750B25') : '#cbd5e1';
      }
      intRecalc(card);
    }

    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.int-cat').forEach(intRecalc);
      intOverall();
    });
    </script>
    <?php
}

/** Corroborating-source count from a form submission, clamped to 1..99. */
function source_count_from_post(array $src): int {
    $n = (int)($src['sourceCount'] ?? 1);
    return max(1, min(99, $n));
}

/**
 * "Also concerns" multi-select for cross-border items.
 *
 * Rendered beside the primary Country select, whose element id must be
 * `primary-country` so the primary can be hidden from this list.
 *
 * @param string[] $selected countries currently attributed beyond the primary
 */
function additional_countries_field(array $selected, string $primary = ''): void {
    ?>
    <div>
      <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
        Also concerns <span class="normal-case font-semibold tracking-normal text-slate-300">(optional, cross-border items)</span>
      </label>
      <div class="relative" id="ac-ms-wrap">
        <button type="button" onclick="acMsToggle()"
                class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-left flex items-center justify-between focus:outline-none">
          <span id="ac-ms-label" class="text-slate-400">No other countries</span>
          <svg width="14" height="14" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="ac-ms-dropdown" class="hidden absolute z-50 mt-1 w-full bg-white rounded-xl border border-slate-200 shadow-lg">
          <div class="p-2 border-b border-slate-100">
            <input type="text" placeholder="Search countries…" oninput="acMsSearch(this.value)"
                   class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:outline-none">
          </div>
          <div class="overflow-y-auto max-h-52 p-1.5">
            <?php foreach (african_countries() as $c): ?>
              <label class="ac-ms-opt flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-slate-50 cursor-pointer"
                     data-name="<?= h($c) ?>"<?= $c === $primary ? ' style="display:none"' : '' ?>>
                <input type="checkbox" name="additionalCountries[]" value="<?= h($c) ?>"
                       <?= (in_array($c, $selected, true) && $c !== $primary) ? 'checked' : '' ?>
                       onchange="acMsUpdate()" class="w-4 h-4 rounded" style="accent-color:#750B25">
                <span class="text-sm text-slate-700"><?= h($c) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <p class="text-[11px] text-slate-400 mt-1.5 leading-relaxed">
        For items about more than one country, such as cross-border armed groups, displacement or a
        regional peace initiative. The item counts towards every country selected, and once towards
        each monitoring region.
      </p>
    </div>
    <script>
    (function () {
      function q(s) { return document.querySelector(s); }
      window.acMsToggle = function () { q('#ac-ms-dropdown').classList.toggle('hidden'); };
      window.acMsSearch = function (v) {
        v = v.toLowerCase();
        document.querySelectorAll('.ac-ms-opt').forEach(function (o) {
          if (o.dataset.hiddenPrimary === '1') return;
          o.style.display = o.dataset.name.toLowerCase().indexOf(v) === -1 ? 'none' : '';
        });
      };
      window.acMsUpdate = function () {
        var picked = [];
        document.querySelectorAll('.ac-ms-opt input:checked').forEach(function (i) { picked.push(i.value); });
        var l = q('#ac-ms-label');
        l.textContent = picked.length ? picked.join(', ') : 'No other countries';
        l.className = picked.length ? 'text-slate-800 truncate' : 'text-slate-400';
      };
      // The primary country can never also be an additional one
      window.acMsSyncPrimary = function () {
        var p = q('#primary-country');
        var primary = p ? p.value : '';
        document.querySelectorAll('.ac-ms-opt').forEach(function (o) {
          var isP = o.dataset.name === primary;
          o.dataset.hiddenPrimary = isP ? '1' : '';
          o.style.display = isP ? 'none' : '';
          if (isP) o.querySelector('input').checked = false;
        });
        acMsUpdate();
      };
      document.addEventListener('click', function (e) {
        var w = q('#ac-ms-wrap');
        if (w && !w.contains(e.target)) q('#ac-ms-dropdown').classList.add('hidden');
      });
      document.addEventListener('DOMContentLoaded', function () {
        var p = q('#primary-country');
        if (p) p.addEventListener('change', acMsSyncPrimary);
        acMsSyncPrimary();
      });
    })();
    </script>
    <?php
}
