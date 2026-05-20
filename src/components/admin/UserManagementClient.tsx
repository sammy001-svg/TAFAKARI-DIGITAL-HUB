"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { formatDate } from "@/lib/format";

type User = {
  id: string;
  name: string | null;
  email: string | null;
  username: string | null;
  role: string;
  createdAt: string;
  _count: { posts: number };
};

type ModalState =
  | { mode: "create" }
  | { mode: "edit"; user: User }
  | null;

const inputClass =
  "w-full bg-slate-50 border-none rounded-2xl px-5 py-3.5 text-slate-900 focus:ring-2 focus:ring-secondary/50 focus:outline-none transition-all font-medium text-sm";

export default function UserManagementClient({
  initialUsers,
  currentUserId,
}: {
  initialUsers: User[];
  currentUserId: string;
}) {
  const router = useRouter();
  const [modal, setModal] = useState<ModalState>(null);
  const [formData, setFormData] = useState({
    name: "", email: "", username: "", password: "", role: "ADMIN",
  });
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [deletingId, setDeletingId] = useState<string | null>(null);

  function openCreate() {
    setFormData({ name: "", email: "", username: "", password: "", role: "ADMIN" });
    setError(null);
    setModal({ mode: "create" });
  }

  function openEdit(user: User) {
    setFormData({ name: user.name ?? "", email: user.email ?? "", username: user.username ?? "", password: "", role: user.role });
    setError(null);
    setModal({ mode: "edit", user });
  }

  function set(field: string, value: string) {
    setFormData((prev) => ({ ...prev, [field]: value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      const isEdit = modal?.mode === "edit";
      const url = isEdit ? `/api/users/${(modal as { mode: "edit"; user: User }).user.id}` : "/api/users";
      const body: Record<string, string> = { name: formData.name, email: formData.email, username: formData.username, role: formData.role };
      if (formData.password) body.password = formData.password;
      if (!isEdit) body.password = formData.password; // required for create

      const res = await fetch(url, {
        method: isEdit ? "PUT" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (!res.ok) {
        setError(data.error ?? "Failed");
      } else {
        setModal(null);
        router.refresh();
      }
    } catch {
      setError("Network error");
    } finally {
      setSubmitting(false);
    }
  }

  async function handleDelete(user: User) {
    if (user.id === currentUserId) return;
    if (user._count.posts > 0) {
      alert(`Cannot delete: user has ${user._count.posts} post(s). Remove or reassign posts first.`);
      return;
    }
    if (!confirm(`Delete user "${user.name ?? user.username}"? This cannot be undone.`)) return;
    setDeletingId(user.id);
    try {
      const res = await fetch(`/api/users/${user.id}`, { method: "DELETE" });
      if (!res.ok) {
        const data = await res.json();
        alert(data.error ?? "Delete failed");
      } else {
        router.refresh();
      }
    } catch {
      alert("Network error");
    } finally {
      setDeletingId(null);
    }
  }

  return (
    <>
      <div className="flex justify-between items-center mb-8">
        <p className="text-slate-500 text-sm">{initialUsers.length} user{initialUsers.length !== 1 ? "s" : ""} registered</p>
        <button onClick={openCreate} className="btn-primary px-6 py-3 text-sm">
          + Add User
        </button>
      </div>

      <div className="glass rounded-[2.5rem] border-white/50 bg-white overflow-hidden">
        <table className="w-full">
          <thead>
            <tr className="border-b border-slate-100">
              <th className="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">User</th>
              <th className="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden md:table-cell">Role</th>
              <th className="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden lg:table-cell">Posts</th>
              <th className="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden lg:table-cell">Joined</th>
              <th className="px-8 py-5"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-50">
            {initialUsers.map((user) => (
              <tr key={user.id} className="hover:bg-slate-50/50 transition-colors group">
                <td className="px-8 py-5">
                  <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-sm font-black text-primary shrink-0">
                      {(user.name ?? user.username ?? "U")[0].toUpperCase()}
                    </div>
                    <div>
                      <p className="text-sm font-bold text-slate-900">{user.name ?? "—"}</p>
                      <p className="text-[10px] text-slate-400">@{user.username} &bull; {user.email}</p>
                    </div>
                  </div>
                </td>
                <td className="px-8 py-5 hidden md:table-cell">
                  <span className={`inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest ${
                    user.role === "SUPER_ADMIN"
                      ? "bg-rose-50 text-rose-600 ring-1 ring-rose-200"
                      : "bg-slate-100 text-slate-600"
                  }`}>
                    {user.role === "SUPER_ADMIN" ? "Super Admin" : "Admin"}
                  </span>
                </td>
                <td className="px-8 py-5 text-sm font-bold text-slate-700 hidden lg:table-cell">{user._count.posts}</td>
                <td className="px-8 py-5 text-xs text-slate-400 hidden lg:table-cell">{formatDate(user.createdAt)}</td>
                <td className="px-8 py-5">
                  <div className="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                      onClick={() => openEdit(user)}
                      className="px-4 py-2 text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-colors"
                    >
                      Edit
                    </button>
                    {user.id !== currentUserId && (
                      <button
                        onClick={() => handleDelete(user)}
                        disabled={deletingId === user.id}
                        className="px-4 py-2 text-[10px] font-black uppercase tracking-widest bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-100 transition-colors disabled:opacity-50"
                      >
                        {deletingId === user.id ? "..." : "Delete"}
                      </button>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {initialUsers.length === 0 && (
          <div className="text-center py-16 text-slate-400">No users found.</div>
        )}
      </div>

      {/* Modal */}
      {modal && (
        <div className="fixed inset-0 z-[100] bg-black/40 backdrop-blur-sm flex items-center justify-center p-4" onClick={() => setModal(null)}>
          <div className="bg-white rounded-[2.5rem] p-10 w-full max-w-lg shadow-2xl" onClick={(e) => e.stopPropagation()}>
            <h2 className="font-outfit font-bold text-2xl mb-8">
              {modal.mode === "create" ? "Add New User" : "Edit User"}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-5">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Full Name</label>
                  <input type="text" required className={inputClass} value={formData.name} onChange={(e) => set("name", e.target.value)} />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Username</label>
                  <input type="text" required className={inputClass} value={formData.username} onChange={(e) => set("username", e.target.value)} />
                </div>
              </div>
              <div className="space-y-2">
                <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Email</label>
                <input type="email" required className={inputClass} value={formData.email} onChange={(e) => set("email", e.target.value)} />
              </div>
              <div className="space-y-2">
                <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">
                  {modal.mode === "edit" ? "New Password (leave blank to keep)" : "Password"}
                </label>
                <input
                  type="password"
                  required={modal.mode === "create"}
                  minLength={8}
                  placeholder="Min. 8 characters"
                  className={inputClass}
                  value={formData.password}
                  onChange={(e) => set("password", e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Role</label>
                <select className={inputClass} value={formData.role} onChange={(e) => set("role", e.target.value)}>
                  <option value="ADMIN">Admin (Content Creator)</option>
                  <option value="SUPER_ADMIN">Super Admin</option>
                </select>
              </div>

              {error && (
                <div className="p-4 bg-rose-50 rounded-2xl text-sm text-rose-700 font-medium">{error}</div>
              )}

              <div className="flex gap-3 pt-2">
                <button type="button" onClick={() => setModal(null)} className="px-6 py-3 rounded-full border-2 border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                  Cancel
                </button>
                <button type="submit" disabled={submitting} className="grow btn-primary py-3 text-sm font-bold rounded-full disabled:opacity-50">
                  {submitting ? "Saving..." : modal.mode === "create" ? "Create User" : "Save Changes"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  );
}
