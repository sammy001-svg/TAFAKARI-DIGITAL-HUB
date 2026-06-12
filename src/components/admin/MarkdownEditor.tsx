"use client";

import { useRef, useState } from "react";
import { markdownToHtml } from "@/lib/markdown";

type Props = {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  minRows?: number;
};

// Insert prefix+suffix around the selection (or around placeholder word)
function wrapSelection(
  ta: HTMLTextAreaElement,
  prefix: string,
  suffix: string,
  placeholder: string,
  onChange: (v: string) => void
) {
  const start = ta.selectionStart;
  const end = ta.selectionEnd;
  const selected = ta.value.slice(start, end) || placeholder;
  const newVal =
    ta.value.slice(0, start) + prefix + selected + suffix + ta.value.slice(end);
  onChange(newVal);
  setTimeout(() => {
    ta.focus();
    ta.setSelectionRange(start + prefix.length, start + prefix.length + selected.length);
  }, 0);
}

// Prepend prefix to the start of the current line
function prependLine(
  ta: HTMLTextAreaElement,
  prefix: string,
  onChange: (v: string) => void
) {
  const pos = ta.selectionStart;
  const lineStart = ta.value.lastIndexOf("\n", pos - 1) + 1;
  const newVal = ta.value.slice(0, lineStart) + prefix + ta.value.slice(lineStart);
  onChange(newVal);
  setTimeout(() => {
    ta.focus();
    const newPos = pos + prefix.length;
    ta.setSelectionRange(newPos, newPos);
  }, 0);
}

// Insert a snippet at cursor (e.g. code block)
function insertSnippet(
  ta: HTMLTextAreaElement,
  snippet: string,
  cursorOffset: number,
  onChange: (v: string) => void
) {
  const pos = ta.selectionStart;
  const newVal = ta.value.slice(0, pos) + snippet + ta.value.slice(pos);
  onChange(newVal);
  setTimeout(() => {
    ta.focus();
    ta.setSelectionRange(pos + cursorOffset, pos + cursorOffset);
  }, 0);
}

export default function MarkdownEditor({
  value,
  onChange,
  placeholder = "Write your content in Markdown…",
  minRows = 14,
}: Props) {
  const [tab, setTab] = useState<"write" | "preview">("write");
  const taRef = useRef<HTMLTextAreaElement>(null);

  const tools: { label: string; title: string; action: () => void }[] = [
    {
      label: "B",
      title: "Bold (Ctrl+B)",
      action: () =>
        taRef.current && wrapSelection(taRef.current, "**", "**", "bold text", onChange),
    },
    {
      label: "I",
      title: "Italic (Ctrl+I)",
      action: () =>
        taRef.current && wrapSelection(taRef.current, "*", "*", "italic text", onChange),
    },
    {
      label: "H2",
      title: "Heading 2",
      action: () => taRef.current && prependLine(taRef.current, "## ", onChange),
    },
    {
      label: "H3",
      title: "Heading 3",
      action: () => taRef.current && prependLine(taRef.current, "### ", onChange),
    },
    {
      label: "•",
      title: "Bullet list",
      action: () => taRef.current && prependLine(taRef.current, "- ", onChange),
    },
    {
      label: "1.",
      title: "Numbered list",
      action: () => taRef.current && prependLine(taRef.current, "1. ", onChange),
    },
    {
      label: '"',
      title: "Blockquote",
      action: () => taRef.current && prependLine(taRef.current, "> ", onChange),
    },
    {
      label: "`",
      title: "Inline code",
      action: () =>
        taRef.current && wrapSelection(taRef.current, "`", "`", "code", onChange),
    },
    {
      label: "```",
      title: "Code block",
      action: () =>
        taRef.current &&
        insertSnippet(taRef.current, "```\n\n```", 4, onChange),
    },
    {
      label: "—",
      title: "Horizontal rule",
      action: () =>
        taRef.current && insertSnippet(taRef.current, "\n---\n", 5, onChange),
    },
    {
      label: "🔗",
      title: "Link",
      action: () =>
        taRef.current &&
        wrapSelection(taRef.current, "[", "](https://)", "link text", onChange),
    },
  ];

  function handleKeyDown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
    if ((e.ctrlKey || e.metaKey) && e.key === "b") {
      e.preventDefault();
      taRef.current && wrapSelection(taRef.current, "**", "**", "bold text", onChange);
    }
    if ((e.ctrlKey || e.metaKey) && e.key === "i") {
      e.preventDefault();
      taRef.current && wrapSelection(taRef.current, "*", "*", "italic text", onChange);
    }
    // Auto-indent: Tab inserts 2 spaces
    if (e.key === "Tab") {
      e.preventDefault();
      taRef.current &&
        insertSnippet(taRef.current, "  ", 2, onChange);
    }
  }

  const previewHtml = markdownToHtml(value);

  return (
    <div className="rounded-2xl border border-slate-200 overflow-hidden bg-white focus-within:border-secondary/50 focus-within:ring-2 focus-within:ring-secondary/20 transition-all">
      {/* Tab bar */}
      <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-3 py-1.5 gap-2 flex-wrap">
        <div className="flex gap-0.5">
          {(["write", "preview"] as const).map((t) => (
            <button
              key={t}
              type="button"
              onClick={() => setTab(t)}
              className={[
                "px-3 py-1.5 text-[11px] font-black uppercase tracking-widest rounded-lg transition-colors",
                tab === t
                  ? "bg-white text-slate-900 shadow-sm"
                  : "text-slate-400 hover:text-slate-700",
              ].join(" ")}
            >
              {t === "write" ? "Write" : "Preview"}
            </button>
          ))}
        </div>

        {tab === "write" && (
          <div className="flex items-center gap-0.5 flex-wrap">
            {tools.map((tool) => (
              <button
                key={tool.label}
                type="button"
                title={tool.title}
                onClick={tool.action}
                className="px-2 py-1 text-[11px] font-bold text-slate-500 hover:text-slate-900 hover:bg-white rounded transition-colors font-mono leading-none"
              >
                {tool.label}
              </button>
            ))}
            <span className="ml-2 text-[10px] text-slate-300 font-mono hidden sm:inline">
              Markdown
            </span>
          </div>
        )}
      </div>

      {tab === "write" ? (
        <textarea
          ref={taRef}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          onKeyDown={handleKeyDown}
          placeholder={placeholder}
          rows={minRows}
          className="w-full px-6 py-5 text-sm text-slate-900 font-mono bg-transparent focus:outline-none resize-y leading-relaxed"
          spellCheck
        />
      ) : (
        <div className="px-6 py-5 min-h-[200px] text-slate-700 text-base leading-relaxed">
          {previewHtml ? (
            <div dangerouslySetInnerHTML={{ __html: previewHtml }} />
          ) : (
            <p className="text-slate-400 italic text-sm">Nothing to preview yet.</p>
          )}
        </div>
      )}
    </div>
  );
}
