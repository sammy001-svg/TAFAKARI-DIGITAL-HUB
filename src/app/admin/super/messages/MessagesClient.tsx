"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { formatDate } from "@/lib/format";

interface Message {
  id: string;
  fullName: string;
  email: string;
  country: string;
  interest: string;
  message: string;
  isRead: boolean;
  createdAt: Date;
}

export default function MessagesClient({ messages }: { messages: Message[] }) {
  const router = useRouter();
  const [expanded, setExpanded] = useState<string | null>(null);
  const [loading, setLoading] = useState<string | null>(null);

  async function markRead(id: string) {
    setLoading(id);
    await fetch(`/api/contact/${id}`, { method: "PATCH" });
    setLoading(null);
    router.refresh();
  }

  async function deleteMessage(id: string) {
    setLoading(id);
    await fetch(`/api/contact/${id}`, { method: "DELETE" });
    setLoading(null);
    router.refresh();
  }

  const unread = messages.filter((m) => !m.isRead).length;

  return (
    <div>
      {unread > 0 && (
        <div className="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl text-amber-800 text-sm font-medium">
          {unread} unread message{unread !== 1 ? "s" : ""} waiting for review.
        </div>
      )}

      {messages.length === 0 ? (
        <div className="text-center py-20 text-slate-400">
          <div className="text-4xl mb-3">📭</div>
          <p className="font-semibold">No contact messages yet.</p>
        </div>
      ) : (
        <div className="flex flex-col gap-3">
          {messages.map((msg) => (
            <div
              key={msg.id}
              className={`rounded-2xl border transition-all ${
                msg.isRead
                  ? "bg-white border-slate-100"
                  : "bg-amber-50/60 border-amber-200"
              }`}
            >
              {/* Header row */}
              <div
                className="flex items-center gap-4 px-6 py-4 cursor-pointer"
                onClick={() => setExpanded(expanded === msg.id ? null : msg.id)}
              >
                <div
                  className={`w-2.5 h-2.5 rounded-full shrink-0 ${
                    msg.isRead ? "bg-slate-200" : "bg-amber-400"
                  }`}
                />
                <div className="grow min-w-0">
                  <div className="flex items-center gap-3 flex-wrap">
                    <span className="font-bold text-slate-900">{msg.fullName}</span>
                    <span className="text-slate-400 text-xs">•</span>
                    <span className="text-slate-500 text-sm">{msg.email}</span>
                    <span className="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold">
                      {msg.country}
                    </span>
                    <span className="text-xs px-2 py-0.5 rounded-full bg-secondary/10 text-secondary font-semibold">
                      {msg.interest}
                    </span>
                  </div>
                  <p className="text-slate-500 text-sm mt-0.5 truncate">{msg.message}</p>
                </div>
                <span className="text-xs text-slate-400 shrink-0">{formatDate(msg.createdAt)}</span>
                <span className="text-slate-400 text-sm shrink-0">
                  {expanded === msg.id ? "▲" : "▼"}
                </span>
              </div>

              {/* Expanded body */}
              {expanded === msg.id && (
                <div className="px-6 pb-5 border-t border-slate-100 pt-4">
                  <p className="text-slate-700 text-sm leading-relaxed whitespace-pre-wrap mb-5">
                    {msg.message}
                  </p>
                  <div className="flex gap-3">
                    <a
                      href={`mailto:${msg.email}?subject=Re: Tafakari Hub Contact`}
                      className="px-4 py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary/90 transition-colors"
                    >
                      Reply by Email
                    </a>
                    {!msg.isRead && (
                      <button
                        type="button"
                        disabled={loading === msg.id}
                        onClick={() => markRead(msg.id)}
                        className="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200 transition-colors disabled:opacity-50"
                      >
                        {loading === msg.id ? "..." : "Mark as Read"}
                      </button>
                    )}
                    <button
                      type="button"
                      disabled={loading === msg.id}
                      onClick={() => deleteMessage(msg.id)}
                      className="px-4 py-2 bg-rose-50 text-rose-600 rounded-xl text-xs font-bold hover:bg-rose-100 transition-colors disabled:opacity-50 ml-auto"
                    >
                      {loading === msg.id ? "..." : "Delete"}
                    </button>
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
