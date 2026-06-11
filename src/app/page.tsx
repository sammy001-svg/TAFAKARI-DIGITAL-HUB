export const dynamic = "force-dynamic";

import Image from "next/image";
import Link from "next/link";
import HomeCarousel from "@/components/ui/HomeCarousel";
import prisma from "@/lib/prisma";
import { formatDate } from "@/lib/format";

export default async function Home() {
  const latestArticles = await prisma.post.findMany({
    where: { type: "ARTICLE", status: "PUBLISHED" },
    orderBy: { createdAt: "desc" },
    select: {
      id: true,
      title: true,
      description: true,
      thumbnailUrl: true,
      country: true,
      issueCategory: true,
      createdAt: true,
    },
    take: 3,
  });
  return (
    <div className="flex flex-col gap-20 pb-20">
      {/* Immersive Full-Width Hero */}
      <section className="relative w-full">
        <HomeCarousel />
      </section>

      {/* Main Content Sections */}
      <section className="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-8 pt-10">
        <div className="glass p-8 rounded-3xl border-secondary/20 hover:border-white/40 transition-all hover:translate-y-[-5px] text-white">
          <div className="w-12 h-12 bg-white/10 text-secondary rounded-2xl flex items-center justify-center text-2xl mb-6 font-bold shadow-sm border border-white/5">
            M
          </div>
          <h3 className="font-outfit font-bold text-xl mb-3 text-white">Media Broadcasting</h3>
          <p className="text-white/70 text-sm leading-relaxed">
            Galleries, podcasts, and video libraries documenting field activities and regional oral reports.
          </p>
        </div>
        
        <div className="glass p-8 rounded-3xl border-secondary/20 hover:border-white/40 transition-all hover:translate-y-[-5px] text-white">
          <div className="w-12 h-12 bg-white/20 text-white rounded-2xl flex items-center justify-center text-2xl mb-6 font-bold shadow-sm border border-white/10">
            K
          </div>
          <h3 className="font-outfit font-bold text-xl mb-3 text-white">Knowledge Repository</h3>
          <p className="text-white/70 text-sm leading-relaxed">
            A structured library of research reports, policy briefs, and actionable datasets for public use.
          </p>
        </div>
        
        <div className="glass p-8 rounded-3xl border-secondary/20 hover:border-white/40 transition-all hover:translate-y-[-5px] text-white">
          <div className="w-12 h-12 bg-primary text-white rounded-2xl flex items-center justify-center text-2xl mb-6 font-bold shadow-sm border border-white/20">
            C
          </div>
          <h3 className="font-outfit font-bold text-xl mb-3 text-white">Community Engagement</h3>
          <p className="text-white/70 text-sm leading-relaxed">
            Moderated comments and contact submissions ensuring safe and constructive dialogue.
          </p>
        </div>
      </section>
      
      {/* Latest Articles — only shown when DB has published content */}
      {latestArticles.length > 0 && (
        <section className="max-w-7xl mx-auto px-6">
          <div className="flex items-center justify-between mb-10">
            <div>
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-bold uppercase tracking-widest mb-3">
                Latest from the Region
              </div>
              <h2 className="font-outfit text-3xl font-bold">Recent Articles</h2>
            </div>
            <Link
              href="/news"
              className="text-sm font-black text-primary uppercase tracking-widest hover:underline hidden md:block"
            >
              View All &rarr;
            </Link>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {latestArticles.map((art) => (
              <Link key={art.id} href={`/news/${art.id}`} className="group flex flex-col gap-4">
                <div className="h-48 bg-slate-100 rounded-3xl overflow-hidden relative border border-slate-200">
                  {art.thumbnailUrl ? (
                    <Image
                      src={art.thumbnailUrl}
                      alt={art.title}
                      fill
                      className="object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                  ) : (
                    <div className="absolute inset-0 premium-gradient opacity-10 group-hover:opacity-20 transition-opacity" />
                  )}
                  <div className="absolute top-4 left-4">
                    <span className="glass px-2 py-1 rounded-full text-[10px] font-bold text-white uppercase backdrop-blur-md">
                      {art.country}
                    </span>
                  </div>
                </div>
                <div>
                  <span className="text-[10px] font-black uppercase tracking-widest text-secondary block mb-1">
                    {art.issueCategory}
                  </span>
                  <h3 className="font-outfit font-bold text-lg leading-snug group-hover:text-primary transition-colors line-clamp-2">
                    {art.title}
                  </h3>
                  <p className="text-xs text-slate-400 mt-1">{formatDate(art.createdAt)}</p>
                </div>
              </Link>
            ))}
          </div>

          <div className="mt-6 md:hidden text-center">
            <Link href="/news" className="text-sm font-black text-primary uppercase tracking-widest">
              View All Articles &rarr;
            </Link>
          </div>
        </section>
      )}

      {/* Countries Section */}
      <section className="bg-slate-50 dark:bg-slate-900/50 py-24 px-6 border-y border-secondary/10">
        <div className="max-w-7xl mx-auto text-center mb-16">
          <h2 className="font-outfit text-4xl font-bold mb-4 text-primary dark:text-secondary">Target Geographies</h2>
          <p className="text-slate-500 max-w-2xl mx-auto dark:text-slate-400">
            Providing structured information sharing and visualization across three strategic nations.
          </p>
        </div>
        
        <div className="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-12">
          {/* Kenya */}
          <div className="flex flex-col gap-6 group">
            <div className="h-56 glass bg-primary rounded-3xl overflow-hidden relative group border-secondary/20 shadow-xl group-hover:scale-[1.02] transition-all">
               <Image src="/nairobi_city.png" alt="Nairobi Cityscape" fill className="object-cover opacity-60 group-hover:opacity-80 transition-opacity" />
               <div className="absolute inset-0 bg-black/50 z-10" />
               <div className="absolute top-4 right-4 w-12 h-12 z-20 shadow-xl rounded-full overflow-hidden border-2 border-white/20">
                 <Image src="/kenya_flag.png" alt="Kenya Flag" fill className="object-cover" />
               </div>
               <div className="absolute bottom-6 left-6 z-20">
                 <div className="font-outfit font-black text-4xl text-white uppercase tracking-tighter">KE</div>
               </div>
            </div>
            <div>
              <h4 className="font-outfit font-bold text-2xl mb-2 text-primary dark:text-secondary">Kenya</h4>
              <p className="text-slate-500 text-sm italic dark:text-slate-400">47 Counties • Nairobi, Mombasa, Kisumu</p>
            </div>
          </div>
          
          {/* Ethiopia */}
          <div className="flex flex-col gap-6 group">
            <div className="h-56 glass bg-primary rounded-3xl overflow-hidden relative group border-secondary/20 shadow-xl group-hover:scale-[1.02] transition-all">
               <Image src="/addis_ababa_city.png" alt="Addis Ababa Cityscape" fill className="object-cover opacity-60 group-hover:opacity-80 transition-opacity" />
               <div className="absolute inset-0 bg-black/50 z-10" />
               <div className="absolute top-4 right-4 w-12 h-12 z-20 shadow-xl rounded-full overflow-hidden border-2 border-white/20">
                 <Image src="/ethiopia_flag.png" alt="Ethiopia Flag" fill className="object-cover" />
               </div>
               <div className="absolute bottom-6 left-6 z-20">
                 <div className="font-outfit font-black text-4xl text-white uppercase tracking-tighter">ET</div>
               </div>
            </div>
            <div>
              <h4 className="font-outfit font-bold text-2xl mb-2 text-primary dark:text-secondary">Ethiopia</h4>
              <p className="text-slate-500 text-sm italic dark:text-slate-400">11 Regional States • Addis Ababa, Dire Dawa</p>
            </div>
          </div>
          
          {/* DR Congo */}
          <div className="flex flex-col gap-6 group">
            <div className="h-56 glass bg-primary rounded-3xl overflow-hidden relative group border-secondary/20 shadow-xl group-hover:scale-[1.02] transition-all">
               <Image src="/kinshasa_city.png" alt="Kinshasa Cityscape" fill className="object-cover opacity-60 group-hover:opacity-80 transition-opacity" />
               <div className="absolute inset-0 bg-black/50 z-10" />
               <div className="absolute top-4 right-4 w-12 h-12 z-20 shadow-xl rounded-full overflow-hidden border-2 border-white/20">
                 <Image src="/drc_flag.png" alt="DRC Flag" fill className="object-cover" />
               </div>
               <div className="absolute bottom-6 left-6 z-20">
                 <div className="font-outfit font-black text-4xl text-white uppercase tracking-tighter">CD</div>
               </div>
            </div>
            <div>
              <h4 className="font-outfit font-bold text-2xl mb-2 text-primary dark:text-secondary">DR Congo</h4>
              <p className="text-slate-500 text-sm italic dark:text-slate-400">26 Provinces • Kinshasa, Lubumbashi</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
