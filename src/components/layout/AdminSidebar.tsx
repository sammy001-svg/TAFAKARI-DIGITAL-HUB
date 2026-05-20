"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { signOut, useSession } from "next-auth/react";

export default function AdminSidebar() {
  const pathname = usePathname();
  const { data: session } = useSession();
  const role = session?.user?.role;

  const navItems = [
    { name: "Overview", href: "/admin/dashboard", icon: "📊" },
    { name: "My Content", href: "/admin/content", icon: "📝" },
    { name: "Create New", href: "/admin/content/new", icon: "➕" },
    { name: "My Profile", href: "/admin/profile", icon: "👤" },
  ];

  const superAdminItems = [
    { name: "Approval Queue", href: "/admin/super/approvals", icon: "⚖️" },
    { name: "Comment Moderation", href: "/admin/super/moderation", icon: "🛡️" },
    { name: "User Management", href: "/admin/super/users", icon: "👥" },
    { name: "Platform Settings", href: "/admin/super/settings", icon: "⚙️" },
    { name: "Audit Logs", href: "/admin/super/logs", icon: "📜" },
  ];

  return (
    <aside className="w-72 h-screen sticky top-0 bg-[#1F0404] border-r border-white/10 flex flex-col p-6 overflow-y-auto">
      <div className="flex items-center gap-3 mb-12 px-2">
        <div className="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg border border-secondary/20">
          T
        </div>
        <div className="flex flex-col">
          <span className="font-outfit font-bold text-lg text-white leading-tight">Admin Portal</span>
          <span className="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Tafakari Hub</span>
        </div>
      </div>

      <nav className="grow space-y-8">
        <div className="space-y-1">
          <h4 className="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Core Actions</h4>
          {navItems.map((item) => {
            const isActive = pathname === item.href;
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all ${
                  isActive 
                    ? "bg-primary text-white shadow-lg shadow-primary/20" 
                    : "text-slate-400 hover:text-white hover:bg-white/5"
                }`}
              >
                <span className="text-lg opacity-80">{item.icon}</span>
                {item.name}
              </Link>
            );
          })}
        </div>

        {role === "SUPER_ADMIN" && (
          <div className="space-y-1">
            <h4 className="px-4 text-[10px] font-bold text-rose-500 uppercase tracking-widest mb-4">Super Authority</h4>
            {superAdminItems.map((item) => {
              const isActive = pathname === item.href;
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={`flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all ${
                    isActive 
                      ? "bg-white text-slate-900 shadow-lg shadow-white/10" 
                      : "text-slate-400 hover:text-white hover:bg-white/5"
                  }`}
                >
                  <span className="text-lg opacity-80">{item.icon}</span>
                  {item.name}
                </Link>
              );
            })}
          </div>
        )}
      </nav>

      <div className="mt-auto border-t border-white/5 pt-6 space-y-4">
        <div className="flex items-center gap-3 px-2">
          <div className="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-300">
            {session?.user?.name?.[0] || "U"}
          </div>
          <div className="flex flex-col min-w-0">
             <span className="text-sm font-bold text-white truncate">{session?.user?.name || "User"}</span>
             <span className="text-[10px] text-slate-500 capitalize">{role?.toLowerCase().replace("_", " ")}</span>
          </div>
        </div>
        
        <button 
          onClick={() => signOut({ callbackUrl: "/" })}
          className="w-full text-left px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:text-rose-400 hover:bg-rose-500/5 transition-all flex items-center gap-3"
        >
          <span className="text-lg">🚪</span>
          Sign Out
        </button>
      </div>
    </aside>
  );
}
