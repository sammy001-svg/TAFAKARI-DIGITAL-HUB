export const dynamic = "force-dynamic";

import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import Link from "next/link";

export default async function AdminDashboard() {
  const session = await getServerSession(authOptions);

  const stats = [
    { label: "Total Published", value: "24", change: "+4", color: "indigo" },
    { label: "Pending Approval", value: "8", change: "Action Needed", color: "rose" },
    { label: "Total Views", value: "14.2k", change: "+12%", color: "emerald" },
    { label: "Downloads", value: "842", change: "+52", color: "amber" },
  ];

  return (
    <div className="flex flex-col gap-10">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="font-outfit text-3xl font-bold text-slate-900">Dashboard Overview</h1>
          <p className="text-slate-500 mt-1">Welcome back, <span className="text-indigo-600 font-bold">{session?.user?.name}</span></p>
        </div>
        <Link href="/admin/content/new" className="btn-primary">
          Create New Content
        </Link>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat) => (
          <div key={stat.label} className="glass p-6 rounded-3xl border-white/50 bg-white shadow-sm overflow-hidden relative">
            <div className={`absolute top-0 right-0 w-2 h-full bg-${stat.color}-500 opacity-20`}></div>
            <span className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 block">{stat.label}</span>
            <div className="flex justify-between items-end">
              <span className="text-4xl font-outfit font-black text-slate-900">{stat.value}</span>
              <span className={`text-[10px] font-bold px-2 py-1 rounded-full bg-${stat.color}-50 text-${stat.color}-600`}>
                {stat.change}
              </span>
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2 glass p-8 rounded-[2rem] border-white/50 bg-white">
          <div className="flex justify-between items-center mb-8">
            <h3 className="font-outfit font-bold text-xl">Recent Activity</h3>
            <button className="text-xs font-bold text-indigo-600 hover:underline">View All</button>
          </div>
          <div className="space-y-6">
             {[1, 2, 3, 4].map((i) => (
               <div key={i} className="flex items-center gap-4 group">
                 <div className="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-lg group-hover:bg-indigo-50 transition-colors">📄</div>
                 <div className="flex-grow">
                   <h4 className="text-sm font-bold text-slate-800">New article submitted for review</h4>
                   <p className="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">Kenya • Security • 2 hours ago</p>
                 </div>
                 <span className="text-[10px] font-bold text-rose-500 uppercase tracking-widest bg-rose-50 px-2 py-1 rounded-full">Pending</span>
               </div>
             ))}
          </div>
        </div>

        <div className="glass p-8 rounded-[2rem] border-white/50 bg-slate-950 text-white flex flex-col justify-between">
           <div>
            <h3 className="font-outfit font-bold text-xl mb-4">Quick Tip</h3>
            <p className="text-slate-400 text-sm leading-relaxed">
              Always ensure your content is tagged with a valid <strong>Region</strong> and <strong>Issue Category</strong> to ensure it correctly appears on the regional heatmap.
            </p>
           </div>
           <div className="mt-12 p-4 bg-white/5 rounded-2xl border border-white/10">
              <h4 className="text-xs font-bold uppercase tracking-widest mb-2 opacity-60">System Health</h4>
              <div className="flex items-center gap-2">
                 <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                 <span className="text-sm font-medium">All systems operational</span>
              </div>
           </div>
        </div>
      </div>
    </div>
  );
}
