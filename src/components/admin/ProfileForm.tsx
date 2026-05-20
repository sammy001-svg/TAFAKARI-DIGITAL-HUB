"use client";

import { useState } from "react";
import { formatDate } from "@/lib/format";

type User = {
  id: string;
  name: string | null;
  email: string | null;
  username: string | null;
  role: string;
  createdAt: string;
};

export default function ProfileForm({ user }: { user: User }) {
  const [profile, setProfile] = useState({
    name: user.name ?? "",
    email: user.email ?? "",
    username: user.username ?? "",
  });
  const [passwords, setPasswords] = useState({
    current: "",
    next: "",
    confirm: "",
  });
  const [profileStatus, setProfileStatus] = useState<{ type: "success" | "error"; message: string } | null>(null);
  const [passwordStatus, setPasswordStatus] = useState<{ type: "success" | "error"; message: string } | null>(null);
  const [savingProfile, setSavingProfile] = useState(false);
  const [savingPassword, setSavingPassword] = useState(false);

  async function handleProfileSave(e: React.FormEvent) {
    e.preventDefault();
    setSavingProfile(true);
    setProfileStatus(null);
    try {
      const res = await fetch(`/api/users/${user.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: profile.name, email: profile.email, username: profile.username }),
      });
      const data = await res.json();
      if (!res.ok) {
        setProfileStatus({ type: "error", message: data.error ?? "Update failed" });
      } else {
        setProfileStatus({ type: "success", message: "Profile updated successfully" });
      }
    } catch {
      setProfileStatus({ type: "error", message: "Network error" });
    } finally {
      setSavingProfile(false);
    }
  }

  async function handlePasswordSave(e: React.FormEvent) {
    e.preventDefault();
    if (passwords.next !== passwords.confirm) {
      setPasswordStatus({ type: "error", message: "New passwords do not match" });
      return;
    }
    if (passwords.next.length < 8) {
      setPasswordStatus({ type: "error", message: "Password must be at least 8 characters" });
      return;
    }
    setSavingPassword(true);
    setPasswordStatus(null);
    try {
      const res = await fetch(`/api/users/${user.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ password: passwords.next }),
      });
      const data = await res.json();
      if (!res.ok) {
        setPasswordStatus({ type: "error", message: data.error ?? "Update failed" });
      } else {
        setPasswordStatus({ type: "success", message: "Password changed successfully" });
        setPasswords({ current: "", next: "", confirm: "" });
      }
    } catch {
      setPasswordStatus({ type: "error", message: "Network error" });
    } finally {
      setSavingPassword(false);
    }
  }

  const inputClass =
    "w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-secondary/50 focus:outline-none transition-all font-medium";

  const roleBadge =
    user.role === "SUPER_ADMIN"
      ? "bg-rose-100 text-rose-700 ring-1 ring-rose-200"
      : "bg-slate-100 text-slate-600";

  return (
    <div className="max-w-2xl mx-auto space-y-10">
      {/* Profile info card */}
      <div className="glass p-8 rounded-[2.5rem] border-white/50 bg-white">
        <div className="flex items-center gap-6 mb-8">
          <div className="w-16 h-16 rounded-2xl bg-primary/10 flex items-center justify-center text-2xl font-black text-primary">
            {(user.name ?? user.username ?? "U")[0].toUpperCase()}
          </div>
          <div>
            <div className="flex items-center gap-2 mb-1">
              <h2 className="font-outfit font-bold text-xl text-slate-900">{user.name ?? user.username}</h2>
              <span className={`text-[10px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded-full ${roleBadge}`}>
                {user.role === "SUPER_ADMIN" ? "Super Admin" : "Admin"}
              </span>
            </div>
            <p className="text-xs text-slate-400">Member since {formatDate(user.createdAt)}</p>
          </div>
        </div>

        <form onSubmit={handleProfileSave} className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Full Name</label>
              <input type="text" className={inputClass} value={profile.name} onChange={(e) => setProfile((p) => ({ ...p, name: e.target.value }))} />
            </div>
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Username</label>
              <input type="text" className={inputClass} value={profile.username} onChange={(e) => setProfile((p) => ({ ...p, username: e.target.value }))} />
            </div>
          </div>
          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Email Address</label>
            <input type="email" className={inputClass} value={profile.email} onChange={(e) => setProfile((p) => ({ ...p, email: e.target.value }))} />
          </div>

          {profileStatus && (
            <div className={`p-4 rounded-2xl text-sm font-medium ${profileStatus.type === "success" ? "bg-emerald-50 text-emerald-700" : "bg-rose-50 text-rose-700"}`}>
              {profileStatus.message}
            </div>
          )}

          <button type="submit" disabled={savingProfile} className="btn-primary py-4 px-8 text-sm font-bold rounded-full disabled:opacity-50">
            {savingProfile ? "Saving..." : "Save Profile"}
          </button>
        </form>
      </div>

      {/* Password change card */}
      <div className="glass p-8 rounded-[2.5rem] border-white/50 bg-white">
        <h3 className="font-outfit font-bold text-xl mb-6">Change Password</h3>
        <form onSubmit={handlePasswordSave} className="space-y-6">
          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">New Password</label>
            <input type="password" className={inputClass} placeholder="Min. 8 characters" value={passwords.next} onChange={(e) => setPasswords((p) => ({ ...p, next: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Confirm New Password</label>
            <input type="password" className={inputClass} value={passwords.confirm} onChange={(e) => setPasswords((p) => ({ ...p, confirm: e.target.value }))} />
          </div>

          {passwordStatus && (
            <div className={`p-4 rounded-2xl text-sm font-medium ${passwordStatus.type === "success" ? "bg-emerald-50 text-emerald-700" : "bg-rose-50 text-rose-700"}`}>
              {passwordStatus.message}
            </div>
          )}

          <button type="submit" disabled={savingPassword} className="btn-primary py-4 px-8 text-sm font-bold rounded-full disabled:opacity-50">
            {savingPassword ? "Updating..." : "Update Password"}
          </button>
        </form>
      </div>
    </div>
  );
}
