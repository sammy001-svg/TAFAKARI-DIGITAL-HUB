"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

export default function SetupPage() {
  const router = useRouter();
  const [checking, setChecking] = useState(true);
  const [alreadySetup, setAlreadySetup] = useState(false);
  const [dbError, setDbError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [done, setDone] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [form, setForm] = useState({
    name: "",
    email: "",
    username: "",
    password: "",
    confirm: "",
  });

  useEffect(() => {
    fetch("/api/setup")
      .then(async (r) => {
        const data = await r.json();
        if (!r.ok) {
          setDbError(data.error ?? "Database connection failed.");
        } else if (!data.setupRequired) {
          setAlreadySetup(true);
        }
      })
      .catch(() => {
        setDbError("Could not reach the server. Make sure the app is running.");
      })
      .finally(() => setChecking(false));
  }, []);

  function set(field: string, value: string) {
    setForm((prev) => ({ ...prev, [field]: value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (form.password !== form.confirm) {
      setError("Passwords do not match.");
      return;
    }
    if (form.password.length < 8) {
      setError("Password must be at least 8 characters.");
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      const res = await fetch("/api/setup", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name:     form.name,
          email:    form.email,
          username: form.username,
          password: form.password,
        }),
      });

      let data: { error?: string; data?: unknown } = {};
      try {
        data = await res.json();
      } catch {
        setError("Server returned an unexpected response. Check the server logs.");
        return;
      }

      if (!res.ok) {
        setError(data.error ?? "Setup failed. Please try again.");
      } else {
        setDone(true);
      }
    } catch {
      setError("Cannot reach the server. Make sure the dev server is running and MySQL is started in XAMPP.");
    } finally {
      setSubmitting(false);
    }
  }

  const inputClass =
    "w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-secondary/50 focus:outline-none transition-all font-medium";

  // ── Checking ────────────────────────────────────────────────
  if (checking) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="text-slate-400 text-sm font-medium animate-pulse">Checking setup status…</div>
      </div>
    );
  }

  // ── DB Connection Error ──────────────────────────────────────
  if (dbError) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4">
        <div className="glass p-12 rounded-[2.5rem] border-white/50 bg-white max-w-md w-full">
          <div className="text-4xl mb-4">⚠️</div>
          <h1 className="font-outfit text-2xl font-bold text-slate-900 mb-3">Database Error</h1>
          <p className="text-rose-600 text-sm font-medium mb-6 p-4 bg-rose-50 rounded-2xl border border-rose-200">
            {dbError}
          </p>
          <div className="text-xs text-slate-500 space-y-2 bg-slate-50 rounded-2xl p-5">
            <p className="font-bold text-slate-700 mb-3">Checklist:</p>
            <p>1. Open <strong>XAMPP Control Panel</strong> and start <strong>MySQL</strong></p>
            <p>2. Check <strong>.env</strong> — DATABASE_URL must be:<br />
               <code className="font-mono bg-white px-2 py-0.5 rounded text-xs border">mysql://root:@localhost:3306/tafakari_hub</code>
            </p>
            <p>3. <strong>Restart</strong> the dev server: <code className="font-mono bg-white px-2 py-0.5 rounded text-xs border">npm run dev</code></p>
            <p>4. Run <code className="font-mono bg-white px-2 py-0.5 rounded text-xs border">npx prisma db push</code> if this is a fresh install</p>
          </div>
          <button
            type="button"
            onClick={() => window.location.reload()}
            className="mt-6 btn-primary w-full py-3 text-sm font-bold"
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  // ── Already Setup ────────────────────────────────────────────
  if (alreadySetup) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="glass p-12 rounded-[2.5rem] border-white/50 bg-white text-center max-w-md w-full mx-4">
          <div className="text-4xl mb-4">🔒</div>
          <h1 className="font-outfit text-2xl font-bold text-slate-900 mb-2">Setup Complete</h1>
          <p className="text-slate-500 text-sm mb-8">
            The platform is already configured. Please log in using your admin credentials.
          </p>
          <button
            type="button"
            onClick={() => router.push("/admin/login")}
            className="btn-primary w-full py-4 text-sm font-bold"
          >
            Go to Admin Login
          </button>
        </div>
      </div>
    );
  }

  // ── Success ──────────────────────────────────────────────────
  if (done) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="glass p-12 rounded-[2.5rem] border-white/50 bg-white text-center max-w-md w-full mx-4">
          <div className="text-5xl mb-4">🎉</div>
          <h1 className="font-outfit text-2xl font-bold text-slate-900 mb-2">Setup Complete!</h1>
          <p className="text-slate-500 text-sm mb-2">
            Your Super Admin account has been created successfully.
          </p>
          <p className="text-xs text-slate-400 mb-8">
            Username: <strong className="text-slate-700">{form.username}</strong>
          </p>
          <button
            type="button"
            onClick={() => router.push("/admin/login")}
            className="btn-primary w-full py-4 text-sm font-bold"
          >
            Proceed to Login →
          </button>
        </div>
      </div>
    );
  }

  // ── Setup Form ───────────────────────────────────────────────
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4">
      <div className="w-full max-w-lg">
        <div className="text-center mb-10">
          <div className="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center text-white font-black text-3xl mx-auto mb-6 shadow-lg shadow-primary/30">
            T
          </div>
          <h1 className="font-outfit text-3xl font-black text-slate-900">First-Time Setup</h1>
          <p className="text-slate-500 mt-2 text-sm">Create your Super Admin account to get started.</p>
          <div className="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-amber-50 rounded-full border border-amber-200">
            <span className="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            <span className="text-[10px] font-black uppercase tracking-widest text-amber-600">
              This page is disabled after setup
            </span>
          </div>
        </div>

        <form
          onSubmit={handleSubmit}
          className="glass p-10 rounded-[2.5rem] border-white/50 bg-white space-y-6"
        >
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Full Name</label>
              <input
                type="text"
                required
                className={inputClass}
                placeholder="e.g. Sammy Opiyo"
                value={form.name}
                onChange={(e) => set("name", e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Username</label>
              <input
                type="text"
                required
                pattern="[a-zA-Z0-9_]+"
                title="Letters, numbers and underscores only"
                className={inputClass}
                placeholder="e.g. superadmin"
                value={form.username}
                onChange={(e) => set("username", e.target.value)}
              />
            </div>
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Email Address</label>
            <input
              type="email"
              required
              className={inputClass}
              placeholder="admin@yoursite.com"
              value={form.email}
              onChange={(e) => set("email", e.target.value)}
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Password</label>
              <input
                type="password"
                required
                minLength={8}
                className={inputClass}
                placeholder="Min. 8 characters"
                value={form.password}
                onChange={(e) => set("password", e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Confirm Password</label>
              <input
                type="password"
                required
                minLength={8}
                className={inputClass}
                placeholder="Re-enter password"
                value={form.confirm}
                onChange={(e) => set("confirm", e.target.value)}
              />
            </div>
          </div>

          {error && (
            <div className="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-sm text-rose-700 font-medium">
              {error}
            </div>
          )}

          <button
            type="submit"
            disabled={submitting}
            className="btn-primary w-full py-4 text-sm font-bold rounded-2xl disabled:opacity-60"
          >
            {submitting ? "Creating Account…" : "Create Super Admin Account →"}
          </button>
        </form>

        <p className="text-center text-xs text-slate-400 mt-6">
          Tafakari Digital Hub &bull; Secure Platform Setup
        </p>
      </div>
    </div>
  );
}
