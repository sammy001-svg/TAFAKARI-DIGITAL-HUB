"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

// ── Types ────────────────────────────────────────────────────────────────────

type StatusResponse = {
  dbConnected: boolean;
  tablesReady: boolean;
  missingTables: string[];
  setupRequired: boolean;
  userCount: number;
  error?: string;
};

type WizardStep =
  | "checking"
  | "db-error"
  | "needs-init"
  | "initializing"
  | "init-error"
  | "create-admin"
  | "already-done"
  | "success";

// ── Progress bar ─────────────────────────────────────────────────────────────

const STEPS = ["Database", "Schema", "Admin Account"];

function StepIndicator({ current }: { current: number }) {
  return (
    <div className="flex items-center justify-center gap-0 mb-10">
      {STEPS.map((label, i) => {
        const done = i < current;
        const active = i === current;
        return (
          <div key={label} className="flex items-center">
            <div className="flex flex-col items-center gap-1.5">
              <div
                className={[
                  "w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all",
                  done  ? "bg-emerald-500 text-white"
                       : active ? "bg-primary text-white shadow-lg shadow-primary/30"
                       : "bg-slate-100 text-slate-400",
                ].join(" ")}
              >
                {done ? "✓" : i + 1}
              </div>
              <span
                className={[
                  "text-[10px] font-bold uppercase tracking-widest",
                  active ? "text-primary" : done ? "text-emerald-600" : "text-slate-400",
                ].join(" ")}
              >
                {label}
              </span>
            </div>
            {i < STEPS.length - 1 && (
              <div
                className={[
                  "w-12 h-0.5 mx-2 mb-5 transition-all",
                  done ? "bg-emerald-400" : "bg-slate-200",
                ].join(" ")}
              />
            )}
          </div>
        );
      })}
    </div>
  );
}

// ── Main component ────────────────────────────────────────────────────────────

export default function SetupPage() {
  const router = useRouter();

  const [step, setStep]             = useState<WizardStep>("checking");
  const [dbError, setDbError]       = useState("");
  const [missingTables, setMissing] = useState<string[]>([]);
  const [initErrors, setInitErrors] = useState<string[]>([]);
  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError]   = useState("");

  const [form, setForm] = useState({
    name: "", email: "", username: "", password: "", confirm: "",
  });

  // ── Step 1: Check DB + schema on mount ───────────────────────
  useEffect(() => {
    fetch("/api/setup")
      .then(async (r) => {
        const data: StatusResponse = await r.json();
        if (!data.dbConnected) {
          setDbError(data.error ?? "Cannot connect to the database.");
          setStep("db-error");
        } else if (!data.tablesReady) {
          setMissing(data.missingTables);
          setStep("needs-init");
        } else if (!data.setupRequired) {
          setStep("already-done");
        } else {
          setStep("create-admin");
        }
      })
      .catch(() => {
        setDbError("Could not reach the server. Make sure the app is running.");
        setStep("db-error");
      });
  }, []);

  // ── Step 2: Initialise database tables ───────────────────────
  async function runInitDb() {
    setStep("initializing");
    try {
      const res = await fetch("/api/setup/init-db", { method: "POST" });
      const data = await res.json();
      if (!res.ok || !data.success) {
        setInitErrors(data.errors ?? [data.error ?? "Unknown error"]);
        setStep("init-error");
      } else {
        setStep("create-admin");
      }
    } catch {
      setInitErrors(["Network error — could not reach the server."]);
      setStep("init-error");
    }
  }

  // ── Step 3: Create first admin account ───────────────────────
  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (form.password !== form.confirm) { setFormError("Passwords do not match."); return; }
    if (form.password.length < 8) { setFormError("Password must be at least 8 characters."); return; }
    setSubmitting(true);
    setFormError("");
    try {
      const res = await fetch("/api/setup", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: form.name, email: form.email, username: form.username, password: form.password }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        setFormError(data.error ?? "Setup failed. Please try again.");
      } else {
        setStep("success");
      }
    } catch {
      setFormError("Cannot reach the server.");
    } finally {
      setSubmitting(false);
    }
  }

  const inputCls =
    "w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-secondary/50 focus:outline-none transition-all font-medium";

  // ── Checking ─────────────────────────────────────────────────
  if (step === "checking") {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="text-slate-400 text-sm font-medium animate-pulse">Checking setup status…</div>
      </div>
    );
  }

  // ── Already Done ─────────────────────────────────────────────
  if (step === "already-done") {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="glass p-12 rounded-[2.5rem] border-white/50 bg-white text-center max-w-md w-full mx-4">
          <div className="text-4xl mb-4">🔒</div>
          <h1 className="font-outfit text-2xl font-bold text-slate-900 mb-2">Setup Complete</h1>
          <p className="text-slate-500 text-sm mb-8">
            The platform is already configured. Please log in using your admin credentials.
          </p>
          <button type="button" onClick={() => router.push("/admin/login")} className="btn-primary w-full py-4 text-sm font-bold">
            Go to Admin Login
          </button>
        </div>
      </div>
    );
  }

  // ── Success ──────────────────────────────────────────────────
  if (step === "success") {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="glass p-12 rounded-[2.5rem] border-white/50 bg-white text-center max-w-md w-full mx-4">
          <div className="text-5xl mb-4">🎉</div>
          <h1 className="font-outfit text-2xl font-bold text-slate-900 mb-2">Setup Complete!</h1>
          <p className="text-slate-500 text-sm mb-2">Your Super Admin account has been created successfully.</p>
          <p className="text-xs text-slate-400 mb-8">
            Username: <strong className="text-slate-700">{form.username}</strong>
          </p>
          <button type="button" onClick={() => router.push("/admin/login")} className="btn-primary w-full py-4 text-sm font-bold">
            Proceed to Login →
          </button>
        </div>
      </div>
    );
  }

  // ── Shared page shell ─────────────────────────────────────────
  const stepIndex =
    step === "db-error"     ? 0
    : step === "needs-init" || step === "initializing" || step === "init-error" ? 1
    : 2; // create-admin

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4">
      <div className="w-full max-w-lg">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center text-white font-black text-3xl mx-auto mb-6 shadow-lg shadow-primary/30">
            T
          </div>
          <h1 className="font-outfit text-3xl font-black text-slate-900">First-Time Setup</h1>
          <p className="text-slate-500 mt-2 text-sm">Follow the steps to configure your platform.</p>
          <div className="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-amber-50 rounded-full border border-amber-200">
            <span className="w-1.5 h-1.5 rounded-full bg-amber-500" />
            <span className="text-[10px] font-black uppercase tracking-widest text-amber-600">
              This page is disabled after setup
            </span>
          </div>
        </div>

        <StepIndicator current={stepIndex} />

        {/* ── DB Error ─────────────────────────────────────────── */}
        {step === "db-error" && (
          <div className="glass p-10 rounded-[2.5rem] border-white/50 bg-white">
            <div className="text-3xl mb-3">⚠️</div>
            <h2 className="font-outfit text-xl font-bold text-slate-900 mb-3">Database Unreachable</h2>
            <p className="text-rose-600 text-sm font-medium mb-6 p-4 bg-rose-50 rounded-2xl border border-rose-200 font-mono break-all">
              {dbError}
            </p>
            <div className="text-xs text-slate-500 space-y-2.5 bg-slate-50 rounded-2xl p-5">
              <p className="font-bold text-slate-700 mb-2">Local (XAMPP)</p>
              <p>1. Open XAMPP → start <strong>MySQL</strong></p>
              <p>2. Ensure <code className="font-mono bg-white px-1.5 py-0.5 rounded border text-[11px]">.env</code> contains:<br />
                <code className="font-mono bg-white px-1.5 py-0.5 rounded border text-[11px]">DATABASE_URL=&quot;mysql://root:@localhost:3306/tafakari_hub&quot;</code>
              </p>
              <p>3. Restart dev server: <code className="font-mono bg-white px-1.5 py-0.5 rounded border text-[11px]">npm run dev</code></p>
              <p className="font-bold text-slate-700 mt-4 mb-2">cPanel</p>
              <p>1. In Node.js App Manager set <strong>DATABASE_URL</strong> to your cPanel MySQL URL</p>
              <p>2. Restart the Node.js application</p>
            </div>
            <button type="button" onClick={() => window.location.reload()} className="mt-6 btn-primary w-full py-3 text-sm font-bold">
              Retry Connection
            </button>
          </div>
        )}

        {/* ── Needs DB Init ─────────────────────────────────────── */}
        {step === "needs-init" && (
          <div className="glass p-10 rounded-[2.5rem] border-white/50 bg-white">
            <div className="text-3xl mb-3">🗄️</div>
            <h2 className="font-outfit text-xl font-bold text-slate-900 mb-2">Database Connected</h2>
            <p className="text-slate-500 text-sm mb-6">
              The database is reachable but the schema has not been created yet.
              Click below to create all required tables automatically.
            </p>
            <div className="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6">
              <p className="text-xs font-bold text-amber-700 mb-2">Missing tables:</p>
              <div className="flex flex-wrap gap-2">
                {missingTables.map((t) => (
                  <span key={t} className="px-2 py-1 bg-white border border-amber-200 rounded-lg text-xs font-mono text-amber-800">
                    {t}
                  </span>
                ))}
              </div>
            </div>
            <button type="button" onClick={runInitDb} className="btn-primary w-full py-4 text-sm font-bold">
              Initialize Database Schema →
            </button>
          </div>
        )}

        {/* ── Initializing ─────────────────────────────────────── */}
        {step === "initializing" && (
          <div className="glass p-10 rounded-[2.5rem] border-white/50 bg-white text-center">
            <div className="text-4xl mb-4 animate-spin">⚙️</div>
            <h2 className="font-outfit text-xl font-bold text-slate-900 mb-2">Creating Tables…</h2>
            <p className="text-slate-400 text-sm">Running CREATE TABLE statements. This takes a second.</p>
          </div>
        )}

        {/* ── Init Error ────────────────────────────────────────── */}
        {step === "init-error" && (
          <div className="glass p-10 rounded-[2.5rem] border-white/50 bg-white">
            <div className="text-3xl mb-3">❌</div>
            <h2 className="font-outfit text-xl font-bold text-slate-900 mb-3">Schema Init Failed</h2>
            <div className="bg-rose-50 border border-rose-200 rounded-2xl p-4 mb-6 space-y-1">
              {initErrors.map((e, i) => (
                <p key={i} className="text-xs font-mono text-rose-700 break-all">{e}</p>
              ))}
            </div>
            <div className="text-xs text-slate-500 bg-slate-50 rounded-2xl p-4 mb-6 space-y-1.5">
              <p className="font-bold text-slate-700 mb-1">Manual alternative (phpMyAdmin)</p>
              <p>1. Open phpMyAdmin and select your database</p>
              <p>2. Click <strong>Import</strong> → choose <code className="font-mono bg-white px-1.5 py-0.5 rounded border text-[11px]">prisma/mysql-init.sql</code></p>
              <p>3. Click <strong>Go</strong>, then reload this page</p>
            </div>
            <div className="flex gap-3">
              <button type="button" onClick={runInitDb} className="btn-primary flex-1 py-3 text-sm font-bold">
                Retry
              </button>
              <button type="button" onClick={() => window.location.reload()} className="flex-1 py-3 text-sm font-bold border border-slate-200 rounded-2xl text-slate-600 hover:bg-slate-50 transition-colors">
                Reload Page
              </button>
            </div>
          </div>
        )}

        {/* ── Create Admin ──────────────────────────────────────── */}
        {step === "create-admin" && (
          <form onSubmit={handleSubmit} className="glass p-10 rounded-[2.5rem] border-white/50 bg-white space-y-6">
            <div>
              <h2 className="font-outfit text-xl font-bold text-slate-900 mb-1">Create Super Admin</h2>
              <p className="text-slate-400 text-sm">This account will have full control over the platform.</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Full Name</label>
                <input type="text" required className={inputCls} placeholder="e.g. Sammy Opiyo"
                  value={form.name} onChange={(e) => setForm((p) => ({ ...p, name: e.target.value }))} />
              </div>
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Username</label>
                <input type="text" required pattern="[a-zA-Z0-9_]+" title="Letters, numbers, underscores only"
                  className={inputCls} placeholder="e.g. superadmin"
                  value={form.username} onChange={(e) => setForm((p) => ({ ...p, username: e.target.value }))} />
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Email Address</label>
              <input type="email" required className={inputCls} placeholder="admin@yoursite.com"
                value={form.email} onChange={(e) => setForm((p) => ({ ...p, email: e.target.value }))} />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Password</label>
                <input type="password" required minLength={8} className={inputCls} placeholder="Min. 8 characters"
                  value={form.password} onChange={(e) => setForm((p) => ({ ...p, password: e.target.value }))} />
              </div>
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Confirm Password</label>
                <input type="password" required minLength={8} className={inputCls} placeholder="Re-enter password"
                  value={form.confirm} onChange={(e) => setForm((p) => ({ ...p, confirm: e.target.value }))} />
              </div>
            </div>

            {formError && (
              <div className="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-sm text-rose-700 font-medium">
                {formError}
              </div>
            )}

            <button type="submit" disabled={submitting} className="btn-primary w-full py-4 text-sm font-bold rounded-2xl disabled:opacity-60">
              {submitting ? "Creating Account…" : "Create Super Admin Account →"}
            </button>
          </form>
        )}

        <p className="text-center text-xs text-slate-400 mt-6">
          Tafakari Digital Hub &bull; Secure Platform Setup
        </p>
      </div>
    </div>
  );
}
