<?php
/**
 * Compare several monitoring cycles side by side (included by heatmap.php).
 * Uses $scoredReports, $availableCycles, $hmTax, $hmGroups, $hm_fmt from the page.
 */
if (count($availableCycles) >= 2):
    $_pValid = array_column($availableCycles, 'start');
    $_pAsked = array_values(array_unique(array_intersect(array_map('strval', (array) ($_GET['periods'] ?? [])), $_pValid)));
    $_pSel   = array_slice(count($_pAsked) >= 2 ? $_pAsked : array_slice($_pValid, 0, 3), 0, 6);
    sort($_pSel);
    $_pNames = array_column($hmTax, 'name');
    $_pCat   = in_array($_GET['pcat'] ?? '', $_pNames, true) ? $_GET['pcat'] : '';
    $_pLabel = array_column($availableCycles, 'label', 'start');
    $_pCells = [];
    foreach ($_pSel as $_s) $_pCells[$_s] = hm_build_cells($scoredReports, $_s);

    // The chosen indicator, or the mean of all indicators
    $_pValue = function (array $byCat) use ($_pCat): ?float {
        if ($_pCat !== '') return isset($byCat[$_pCat]) ? (float) $byCat[$_pCat]['score'] : null;
        if (!$byCat) return null;
        return round(array_sum(array_column($byCat, 'score')) / count($byCat), 1);
    };
    $_pRow = function (string $level, string $name) use ($_pSel, $_pCells, $_pValue, $hm_fmt): string {
        $html = '<tr style="border-top:1px solid #f1f5f9"><td class="px-4 py-2.5 font-bold text-slate-800 whitespace-nowrap sticky left-0 bg-white">' . h($name) . '</td>';
        $prev = null;
        foreach ($_pSel as $_s) {
            $v = $_pValue($_pCells[$_s][$level][$name] ?? []);
            $html .= '<td class="px-3 py-2.5 text-center">';
            if ($v === null) {
                $html .= '<span class="text-slate-300 text-xs">&mdash;</span>';
            } else {
                $html .= '<span class="inline-block font-outfit font-black text-[13px] rounded-lg px-2.5 py-1" style="background:' . h(hm_tier_color($v)) . ';color:#fff;min-width:38px" title="' . h(hm_tier_label($v)) . '">' . h($hm_fmt($v)) . '</span>';
                if ($prev !== null) {
                    $d = round($v - $prev, 1);
                    $col = $d > 0.05 ? '#ED1C24' : ($d < -0.05 ? '#047857' : '#94a3b8');
                    $arrow = $d > 0.05 ? '&uarr;' : ($d < -0.05 ? '&darr;' : '&rarr;');
                    $html .= '<span class="block text-[10px] font-bold mt-1" style="color:' . $col . '">' . $arrow . ' ' . (abs($d) < 0.05 ? '0' : h(sprintf('%+.1f', $d))) . '</span>';
                }
                $prev = $v;
            }
            $html .= '</td>';
        }
        return $html . '</tr>';
    };

    $_pCountries = [];
    foreach ($_pCells as $_c) $_pCountries = array_merge($_pCountries, array_keys($_c['country']));
    $_pCountries = array_values(array_unique($_pCountries));
    sort($_pCountries);
?>
  <section id="hm-periods" style="margin-top:32px;scroll-margin-top:90px">
    <h3 class="font-outfit font-black text-base text-slate-900 mb-1">Compare several periods</h3>
    <p class="text-xs text-slate-400 mb-3 max-w-3xl leading-relaxed">Choose two to six monitoring cycles to read side by side, oldest first. Arrows show the change from the previous column.</p>
    <form method="GET" action="/heatmap#hm-periods" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 mb-3">
      <?php foreach (['cycle', 'compare'] as $_k): if (($_GET[$_k] ?? '') !== '' && is_string($_GET[$_k])): ?>
        <input type="hidden" name="<?= $_k ?>" value="<?= h($_GET[$_k]) ?>">
      <?php endif; endforeach; ?>
      <div class="flex flex-wrap gap-x-4 gap-y-2 mb-3">
        <?php foreach (array_slice($availableCycles, 0, 16) as $_cy): ?>
          <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 cursor-pointer">
            <input type="checkbox" name="periods[]" value="<?= h($_cy['start']) ?>" <?= in_array($_cy['start'], $_pSel, true) ? 'checked' : '' ?> style="accent-color:#750B25">
            <?= h($_cy['label']) ?>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <label class="text-[11px] font-bold text-slate-400" for="hm-pcat">Indicator</label>
        <select id="hm-pcat" name="pcat" class="px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700">
          <option value="">All indicators (overall)</option>
          <?php foreach ($_pNames as $_n): ?><option value="<?= h($_n) ?>" <?= $_n === $_pCat ? 'selected' : '' ?>><?= h($_n) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-white" style="background:#750B25">Compare</button>
      </div>
    </form>
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
      <table class="w-full text-sm" style="border-collapse:collapse">
        <thead><tr style="background:#f8fafc">
          <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-wider text-slate-400 sticky left-0" style="background:#f8fafc">Region / country</th>
          <?php foreach ($_pSel as $_s): ?><th class="px-3 py-3 text-[10px] font-black uppercase tracking-wider text-center text-slate-500 whitespace-nowrap"><?= h($_pLabel[$_s]) ?></th><?php endforeach; ?>
        </tr></thead>
        <tbody>
          <?php foreach ($hmGroups as $_g) {
              foreach ($_pSel as $_s) if (isset($_pCells[$_s]['region'][$_g['name']])) { echo $_pRow('region', $_g['name']); break; }
          }
          foreach ($_pCountries as $_c) echo $_pRow('country', $_c); ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>
