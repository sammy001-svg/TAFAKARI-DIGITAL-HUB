<?php
declare(strict_types=1);
/**
 * Issue categories: a document can belong to several.
 *
 * Stored in Post.issueCategory as a comma-separated list ("A,B"), which the
 * public filters already read with FIND_IN_SET. The first category is the
 * main one. These helpers read and write that list, show it, and render the
 * one category field used by every content form, with its "Suggest with AI"
 * button (includes/agent-classify.php, api/classify.php).
 *
 * Requires functions.php.
 */

const ISSUE_CATEGORIES_MAX_LENGTH = 512;   // the width of Post.issueCategory

/** A post's categories (or a stored list), in order, without blanks or repeats. */
function post_issue_categories(mixed $post): array {
    $raw = is_array($post) ? (string) ($post['issueCategory'] ?? '') : (string) $post;
    $out = [];
    foreach (explode(',', $raw) as $c) {
        $c = trim($c);
        if ($c !== '' && !in_array($c, $out, true)) $out[] = $c;
    }
    return $out;
}

/** The value to store: known names only, in the order given, no commas inside a name. */
function issue_categories_store(array $cats, ?array $allowed = null): string {
    $out = [];
    foreach ($cats as $c) {
        $c = trim(str_replace(',', ' ', (string) $c));
        if ($c === '' || in_array($c, $out, true)) continue;
        if ($allowed !== null && !in_array($c, $allowed, true)) continue;
        $out[] = $c;
    }
    return implode(',', $out);
}

/** The categories ticked on a submitted form (issueCategories[]), checked against the list offered. */
function issue_categories_from_post(array $post, array $allowed): string {
    $picked = array_map('strval', (array) ($post['issueCategories'] ?? []));
    // The order of the categories offered, with the main one (first ticked) first
    $ordered = array_values(array_intersect($allowed, $picked));
    $main = trim((string) ($post['issueCategoryMain'] ?? ''));
    if ($main !== '' && in_array($main, $ordered, true)) $ordered = array_values(array_unique(array_merge([$main], $ordered)));
    return issue_categories_store($ordered, $allowed);
}

/** The main category first, then the others, known names only. */
function issue_categories_merge(string $main, array $others, ?array $allowed = null): string {
    return issue_categories_store(array_merge([$main], $others), $allowed);
}

/** Add the `categories` column (JSON list) to an agent table, once. True when it is there. */
function agent_categories_column(string $table, string $flag): bool {
    static $state = [];
    if (isset($state[$table])) return $state[$table];
    try {
        $v = get_setting($flag, '');
        if ($v === '') {
            set_setting($flag, 'attempted');
            db()->exec('ALTER TABLE `' . $table . '` ADD COLUMN `categories` TEXT NULL');
            set_setting($flag, 'ok');
            $v = 'ok';
        }
        return $state[$table] = ($v === 'ok');
    } catch (Throwable $e) {
        error_log("[agent_categories_column] {$table}: " . $e->getMessage() . ' - apply migrations/014 manually.');
        try { set_setting($flag, 'failed'); } catch (Throwable $e2) {}
        return $state[$table] = false;
    }
}

/** For inline text: "A · B". Plain text; escape it where it is printed. */
function issue_categories_text(mixed $post, string $sep = ' · '): string {
    return implode($sep, post_issue_categories($post));
}

/** One pill per category, in the markup and style given. */
function issue_category_pills(mixed $post, string $class, string $style = ''): string {
    $html = '';
    foreach (post_issue_categories($post) as $c) {
        $html .= '<span class="' . h($class) . '"' . ($style !== '' ? ' style="' . h($style) . '"' : '') . '>' . h($c) . '</span>';
    }
    return $html;
}

/** True when the AI suggestion button can work (the Claude software and a readable key). */
function issue_category_ai_ready(): bool {
    try {
        if (!function_exists('agent_api_key')) require_once __DIR__ . '/agent.php';
        return agent_sdk_available() && agent_api_key() !== '';
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * The category field used by every content form: each category as a visible
 * tick-box, and a button that asks the AI which categories fit. The AI only
 * ticks boxes; nothing is saved until the person submits the form.
 *
 * @param string[] $selected categories currently chosen, main one first
 * @param array{options?:string[],required?:bool,ai?:bool,fields?:array{title?:string,description?:string,content?:string},label?:string,id?:string} $opts
 */
function issue_category_field(array $selected, array $opts = []): void {
    static $n = 0;
    $n++;
    $id       = $opts['id'] ?? ('icf' . $n);
    $options  = $opts['options'] ?? issue_categories_with($selected);
    $required = $opts['required'] ?? true;
    $ai       = ($opts['ai'] ?? true) && issue_category_ai_ready();
    $fields   = ($opts['fields'] ?? []) + ['title' => 'title', 'description' => 'description', 'content' => 'content'];
    $main     = $selected[0] ?? '';
    if (empty($_SESSION['classify_csrf'])) $_SESSION['classify_csrf'] = bin2hex(random_bytes(16));
    ?>
    <div class="tk-icf" id="<?= h($id) ?>" data-required="<?= $required ? '1' : '0' ?>" data-token="<?= h($ai ? $_SESSION['classify_csrf'] : '') ?>"
         data-fields="<?= h(json_encode($fields)) ?>">
      <div class="tk-icf__head">
        <span class="tk-icf__label" id="<?= h($id) ?>-label"><?= h($opts['label'] ?? 'Issue categories') ?><?= $required ? ' *' : '' ?></span>
        <?php if ($ai): ?>
          <button type="button" class="tk-icf__ai" data-icf-ai>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9z"/><path d="M19 15l.8 1.9 1.9.8-1.9.8L19 20.5l-.8-1.9-1.9-.8 1.9-.8z"/></svg>
            <span>Suggest with AI</span>
          </button>
        <?php endif; ?>
      </div>
      <p class="tk-icf__help">Tick every category the document addresses. The first one ticked is shown as its main category.</p>
      <div class="tk-icf__opts" role="group" aria-labelledby="<?= h($id) ?>-label">
        <?php foreach ($options as $c): $on = in_array($c, $selected, true); ?>
          <label class="tk-icf__opt<?= $on ? ' is-on' : '' ?>">
            <input type="checkbox" name="issueCategories[]" value="<?= h($c) ?>" <?= $on ? 'checked' : '' ?>>
            <span><?= h($c) ?><?= is_retired_category($c) ? ' (retired)' : '' ?></span>
            <em class="tk-icf__main" <?= $c === $main && $on ? '' : 'hidden' ?>>main</em>
          </label>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="issueCategoryMain" value="<?= h($main) ?>">
      <div class="tk-icf__note" role="status" aria-live="polite" hidden></div>
    </div>
    <?php
    issue_category_scripts();
}

/** Styles and behaviour for the field, printed once per page. */
function issue_category_scripts(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    ?>
    <style>
    .tk-icf__head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:4px; }
    .tk-icf__label { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.12em; color:#94a3b8; }
    .tk-icf__help { font-size:11.5px; color:#94a3b8; margin:0 0 9px; line-height:1.45; }
    .tk-icf__opts { display:flex; flex-wrap:wrap; gap:7px; }
    .tk-icf__opt { position:relative; display:inline-flex; align-items:center; gap:7px; padding:7px 12px; border:1.5px solid #e2e8f0; border-radius:999px; background:#f8fafc; font-size:13px; font-weight:600; color:#334155; cursor:pointer; user-select:none; transition:border-color .15s, background .15s; }
    .tk-icf__opt:hover { border-color:#cbd5e1; }
    .tk-icf__opt input { width:15px; height:15px; margin:0; accent-color:#750B25; }
    .tk-icf__opt.is-on { border-color:#750B25; background:#F7EDEF; color:#750B25; }
    .tk-icf__opt:focus-within { box-shadow:0 0 0 3px rgba(117,11,37,.16); }
    .tk-icf__main { font-style:normal; font-size:9.5px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; padding:2px 6px; border-radius:999px; background:#750B25; color:#fff; }
    .tk-icf__main[hidden] { display:none; }
    .tk-icf__opt.is-ai::after { content:'AI'; position:absolute; top:-7px; right:-5px; font-size:8.5px; font-weight:800; padding:1px 5px; border-radius:999px; background:#E7952A; color:#0D0102; }
    .tk-icf__ai { display:inline-flex; align-items:center; gap:6px; padding:6px 11px; border:1px solid #E7952A; border-radius:999px; background:#FCF3E6; color:#9A5B0F; font-size:12px; font-weight:700; cursor:pointer; }
    .tk-icf__ai svg { width:14px; height:14px; }
    .tk-icf__ai:hover { background:#F8E5C8; }
    .tk-icf__ai:disabled { opacity:.6; cursor:progress; }
    .tk-icf__note { margin-top:9px; padding:9px 12px; border-radius:12px; background:#FCF3E6; color:#4A4440; font-size:12px; line-height:1.5; }
    .tk-icf__note.is-err { background:#FDEEEE; color:#B91C1C; }
    .tk-icf__note ul { margin:4px 0 0; padding-left:16px; }
    .tk-icf__note button { background:none; border:0; padding:0; color:#750B25; font-weight:700; text-decoration:underline; cursor:pointer; font-size:12px; }
    .tk-icf.is-missing .tk-icf__opts { outline:2px solid #ED1C24; outline-offset:4px; border-radius:12px; }
    </style>
    <script>
    (function () {
      function boxes(f) { return Array.prototype.slice.call(f.querySelectorAll('input[name="issueCategories[]"]')); }
      // The main category is the first one ticked; it stays main until it is unticked
      function sync(f) {
        var main = f.querySelector('input[name="issueCategoryMain"]');
        var on = boxes(f).filter(function (b) { return b.checked; }).map(function (b) { return b.value; });
        if (on.indexOf(main.value) === -1) main.value = on[0] || '';
        boxes(f).forEach(function (b) {
          var opt = b.closest('.tk-icf__opt');
          opt.classList.toggle('is-on', b.checked);
          opt.querySelector('.tk-icf__main').hidden = !(b.checked && b.value === main.value);
        });
        if (on.length) f.classList.remove('is-missing');
      }
      function note(f, html, err) {
        var n = f.querySelector('.tk-icf__note');
        n.innerHTML = html; n.hidden = !html; n.classList.toggle('is-err', !!err);
      }
      function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
      function fieldValue(form, name) {
        var el = form ? form.querySelector('[name="' + name + '"]') : null;
        return el ? String(el.value || '') : '';
      }
      document.querySelectorAll('.tk-icf').forEach(function (f) {
        var form = f.closest('form');
        boxes(f).forEach(function (b) { b.addEventListener('change', function () { f.querySelectorAll('.tk-icf__opt.is-ai').forEach(function (o) { o.classList.remove('is-ai'); }); sync(f); }); });
        sync(f);
        if (form && f.getAttribute('data-required') === '1') {
          form.addEventListener('submit', function (e) {
            if (f.offsetParent === null) return;   // hidden (for example on a gallery image): not needed
            if (!boxes(f).some(function (b) { return b.checked; })) {
              e.preventDefault();
              f.classList.add('is-missing');
              note(f, 'Tick at least one issue category.', true);
              f.scrollIntoView({ block: 'center' });
            }
          });
        }
        var ai = f.querySelector('[data-icf-ai]');
        if (!ai) return;
        ai.addEventListener('click', function () {
          var names = JSON.parse(f.getAttribute('data-fields') || '{}');
          var body = { token: f.getAttribute('data-token'), title: fieldValue(form, names.title), description: fieldValue(form, names.description), content: fieldValue(form, names.content) };
          if ((body.title + body.description + body.content).trim().length < 20) { note(f, 'Write the title and a summary or the text first, so the AI has something to read.', true); return; }
          var before = boxes(f).filter(function (b) { return b.checked; }).map(function (b) { return b.value; });
          var beforeMain = f.querySelector('input[name="issueCategoryMain"]').value;
          ai.disabled = true; ai.querySelector('span').textContent = 'Reading…';
          note(f, '', false);
          fetch('/api/classify', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
            .then(function (r) { return r.json(); })
            .then(function (j) {
              if (!j || !j.ok) { note(f, esc((j && (j.message || j.error)) || 'The AI could not suggest categories just now.'), true); return; }
              if (!j.categories.length) { note(f, esc(j.message || 'The AI found no category that fits this document.'), false); return; }
              var picked = j.categories.map(function (c) { return c.name; });
              boxes(f).forEach(function (b) {
                b.checked = picked.indexOf(b.value) !== -1;
                b.closest('.tk-icf__opt').classList.toggle('is-ai', b.checked);
              });
              f.querySelector('input[name="issueCategoryMain"]').value = picked[0];
              sync(f);
              note(f, '<strong>AI suggestion</strong>, for you to check:<ul>' + j.categories.map(function (c, i) {
                return '<li><strong>' + esc(c.name) + '</strong>' + (i === 0 ? ' (main)' : '') + (c.reason ? ': ' + esc(c.reason) : '') + '</li>';
              }).join('') + '</ul><button type="button" data-icf-undo>Undo</button>', false);
              f.querySelector('[data-icf-undo]').addEventListener('click', function () {
                boxes(f).forEach(function (b) { b.checked = before.indexOf(b.value) !== -1; b.closest('.tk-icf__opt').classList.remove('is-ai'); });
                f.querySelector('input[name="issueCategoryMain"]').value = beforeMain;
                sync(f); note(f, '', false);
              });
            })
            .catch(function () { note(f, 'The AI could not be reached. Choose the categories yourself.', true); })
            .finally(function () { ai.disabled = false; ai.querySelector('span').textContent = 'Suggest with AI'; });
        });
      });
    })();
    </script>
    <?php
}
