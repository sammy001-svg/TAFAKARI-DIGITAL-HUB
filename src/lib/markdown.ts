/**
 * Lightweight Markdown → HTML renderer.
 * Handles the common cases editors will actually use:
 * headings, bold, italic, lists, blockquotes, code, links, hr.
 * Content is written by trusted admins so sanitisation is minimal
 * (only escapes raw < > & in text nodes to prevent HTML injection).
 */

function escapeHtml(text: string): string {
  return text
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

/** Process inline markdown: bold, italic, code, links, strikethrough */
function inline(text: string): string {
  return escapeHtml(text)
    // Links  [label](url)
    .replace(
      /\[([^\]]+)\]\(([^)]+)\)/g,
      '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-secondary underline hover:opacity-80">$1</a>'
    )
    // Bold + italic  ***text***
    .replace(/\*\*\*(.+?)\*\*\*/g, "<strong><em>$1</em></strong>")
    // Bold  **text**
    .replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>")
    // Italic  *text*
    .replace(/\*(.+?)\*/g, "<em>$1</em>")
    // Strikethrough  ~~text~~
    .replace(/~~(.+?)~~/g, "<del>$1</del>")
    // Inline code  `code`
    .replace(
      /`([^`]+)`/g,
      '<code class="bg-slate-100 text-rose-600 px-1.5 py-0.5 rounded text-[0.85em] font-mono">$1</code>'
    );
}

export function markdownToHtml(raw: string): string {
  if (!raw?.trim()) return "";

  const lines = raw.split("\n");
  const out: string[] = [];
  let i = 0;

  while (i < lines.length) {
    const line = lines[i];

    // ── Fenced code block ───────────────────────────────────────
    if (line.startsWith("```")) {
      const lang = line.slice(3).trim();
      const code: string[] = [];
      i++;
      while (i < lines.length && !lines[i].startsWith("```")) {
        code.push(escapeHtml(lines[i]));
        i++;
      }
      i++; // skip closing ```
      out.push(
        `<pre class="bg-slate-900 text-slate-100 rounded-2xl px-6 py-5 overflow-x-auto text-sm font-mono my-6"${lang ? ` data-lang="${lang}"` : ""
        }><code>${code.join("\n")}</code></pre>`
      );
      continue;
    }

    // ── Heading ─────────────────────────────────────────────────
    const headingMatch = line.match(/^(#{1,6}) (.+)/);
    if (headingMatch) {
      const level = headingMatch[1].length;
      const text = inline(headingMatch[2]);
      const cls: Record<number, string> = {
        1: "text-3xl font-bold mt-10 mb-4",
        2: "text-2xl font-bold mt-8 mb-3",
        3: "text-xl font-semibold mt-6 mb-2",
        4: "text-lg font-semibold mt-5 mb-2",
        5: "text-base font-semibold mt-4 mb-1",
        6: "text-sm font-semibold mt-4 mb-1 text-slate-500",
      };
      out.push(`<h${level} class="${cls[level] ?? ""}">${text}</h${level}>`);
      i++;
      continue;
    }

    // ── Horizontal rule ─────────────────────────────────────────
    if (/^---+$/.test(line.trim())) {
      out.push('<hr class="border-slate-200 my-8" />');
      i++;
      continue;
    }

    // ── Blockquote ──────────────────────────────────────────────
    if (line.startsWith("> ")) {
      const quoteLines: string[] = [];
      while (i < lines.length && lines[i].startsWith("> ")) {
        quoteLines.push(inline(lines[i].slice(2)));
        i++;
      }
      out.push(
        `<blockquote class="border-l-4 border-secondary pl-5 my-5 text-slate-500 italic">${quoteLines.join("<br />")}</blockquote>`
      );
      continue;
    }

    // ── Unordered list ──────────────────────────────────────────
    if (/^[*\-] /.test(line)) {
      const items: string[] = [];
      while (i < lines.length && /^[*\-] /.test(lines[i])) {
        items.push(`<li class="ml-5 list-disc">${inline(lines[i].slice(2))}</li>`);
        i++;
      }
      out.push(`<ul class="my-4 space-y-1">${items.join("")}</ul>`);
      continue;
    }

    // ── Ordered list ────────────────────────────────────────────
    if (/^\d+\. /.test(line)) {
      const items: string[] = [];
      while (i < lines.length && /^\d+\. /.test(lines[i])) {
        items.push(
          `<li class="ml-5 list-decimal">${inline(lines[i].replace(/^\d+\. /, ""))}</li>`
        );
        i++;
      }
      out.push(`<ol class="my-4 space-y-1">${items.join("")}</ol>`);
      continue;
    }

    // ── Empty line ──────────────────────────────────────────────
    if (line.trim() === "") {
      i++;
      continue;
    }

    // ── Paragraph ───────────────────────────────────────────────
    // Collect consecutive non-blank, non-special lines
    const paraLines: string[] = [];
    while (
      i < lines.length &&
      lines[i].trim() !== "" &&
      !/^#{1,6} /.test(lines[i]) &&
      !lines[i].startsWith("> ") &&
      !lines[i].startsWith("```") &&
      !/^[*\-] /.test(lines[i]) &&
      !/^\d+\. /.test(lines[i]) &&
      !/^---+$/.test(lines[i].trim())
    ) {
      paraLines.push(inline(lines[i]));
      i++;
    }
    if (paraLines.length) {
      out.push(`<p class="my-4 leading-relaxed">${paraLines.join("<br />")}</p>`);
    }
  }

  return out.join("\n");
}
