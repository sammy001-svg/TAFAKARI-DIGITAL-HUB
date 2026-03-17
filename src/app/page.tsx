import Image from "next/image";
import HomeCarousel from "@/components/ui/HomeCarousel";

export default function Home() {
  return (
    <div className="flex flex-col gap-20 pb-20">
      {/* Immersive Full-Width Hero */}
      <section className="relative w-full">
        <HomeCarousel />
      </section>

      {/* Main Content Sections */}
      <section className="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-8 pt-10">
        <div className="glass p-8 rounded-3xl border-emerald-400/30 hover:border-white/40 transition-all hover:translate-y-[-5px] text-white">
          <div className="w-12 h-12 bg-white/10 text-emerald-200 rounded-2xl flex items-center justify-center text-2xl mb-6 font-bold shadow-sm border border-white/5">
            M
          </div>
          <h3 className="font-outfit font-bold text-xl mb-3 text-white">Media Broadcasting</h3>
          <p className="text-emerald-50/70 text-sm leading-relaxed">
            Galleries, podcasts, and video libraries documenting field activities and regional oral reports.
          </p>
        </div>
        
        <div className="glass p-8 rounded-3xl border-emerald-400/30 hover:border-white/40 transition-all hover:translate-y-[-5px] text-white">
          <div className="w-12 h-12 bg-white/20 text-white rounded-2xl flex items-center justify-center text-2xl mb-6 font-bold shadow-sm border border-white/10">
            K
          </div>
          <h3 className="font-outfit font-bold text-xl mb-3 text-white">Knowledge Repository</h3>
          <p className="text-emerald-50/70 text-sm leading-relaxed">
            A structured library of research reports, policy briefs, and actionable datasets for public use.
          </p>
        </div>
        
        <div className="glass p-8 rounded-3xl border-emerald-400/30 hover:border-white/40 transition-all hover:translate-y-[-5px] text-white">
          <div className="w-12 h-12 bg-emerald-500 text-white rounded-2xl flex items-center justify-center text-2xl mb-6 font-bold shadow-sm border border-white/20">
            C
          </div>
          <h3 className="font-outfit font-bold text-xl mb-3 text-white">Community Engagement</h3>
          <p className="text-emerald-50/70 text-sm leading-relaxed">
            Moderated comments and contact submissions ensuring safe and constructive dialogue.
          </p>
        </div>
      </section>
      
      {/* Countries Section */}
      <section className="bg-slate-50 dark:bg-slate-900/50 py-24 px-6 border-y border-emerald-100/10">
        <div className="max-w-7xl mx-auto text-center mb-16">
          <h2 className="font-outfit text-4xl font-bold mb-4 text-emerald-900 dark:text-emerald-400">Target Geographies</h2>
          <p className="text-slate-500 max-w-2xl mx-auto dark:text-slate-400">
            Providing structured information sharing and visualization across three strategic nations.
          </p>
        </div>
        
        <div className="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-12">
          {/* Kenya */}
          <div className="flex flex-col gap-6 group">
            <div className="h-56 glass bg-emerald-600 rounded-3xl overflow-hidden relative group border-emerald-400/30 shadow-xl group-hover:scale-[1.02] transition-all">
               <Image src="/nairobi_city.png" alt="Nairobi Cityscape" fill className="object-cover opacity-60 group-hover:opacity-80 transition-opacity" />
               <div className="absolute inset-0 bg-gradient-to-t from-emerald-950/80 via-transparent to-transparent z-10" />
               <div className="absolute top-4 right-4 w-12 h-12 z-20 shadow-xl rounded-full overflow-hidden border-2 border-white/20">
                 <Image src="/kenya_flag.png" alt="Kenya Flag" fill className="object-cover" />
               </div>
               <div className="absolute bottom-6 left-6 z-20">
                 <div className="font-outfit font-black text-4xl text-white uppercase tracking-tighter">KE</div>
               </div>
            </div>
            <div>
              <h4 className="font-outfit font-bold text-2xl mb-2 text-emerald-800 dark:text-emerald-400">Kenya</h4>
              <p className="text-slate-500 text-sm italic dark:text-emerald-100/50">47 Counties • Nairobi, Mombasa, Kisumu</p>
            </div>
          </div>
          
          {/* Ethiopia */}
          <div className="flex flex-col gap-6 group">
            <div className="h-56 glass bg-emerald-600 rounded-3xl overflow-hidden relative group border-emerald-400/30 shadow-xl group-hover:scale-[1.02] transition-all">
               <Image src="/addis_ababa_city.png" alt="Addis Ababa Cityscape" fill className="object-cover opacity-60 group-hover:opacity-80 transition-opacity" />
               <div className="absolute inset-0 bg-gradient-to-t from-emerald-950/80 via-transparent to-transparent z-10" />
               <div className="absolute top-4 right-4 w-12 h-12 z-20 shadow-xl rounded-full overflow-hidden border-2 border-white/20">
                 <Image src="/ethiopia_flag.png" alt="Ethiopia Flag" fill className="object-cover" />
               </div>
               <div className="absolute bottom-6 left-6 z-20">
                 <div className="font-outfit font-black text-4xl text-white uppercase tracking-tighter">ET</div>
               </div>
            </div>
            <div>
              <h4 className="font-outfit font-bold text-2xl mb-2 text-emerald-800 dark:text-emerald-400">Ethiopia</h4>
              <p className="text-slate-500 text-sm italic dark:text-emerald-100/50">11 Regional States • Addis Ababa, Dire Dawa</p>
            </div>
          </div>
          
          {/* DR Congo */}
          <div className="flex flex-col gap-6 group">
            <div className="h-56 glass bg-emerald-600 rounded-3xl overflow-hidden relative group border-emerald-400/30 shadow-xl group-hover:scale-[1.02] transition-all">
               <Image src="/kinshasa_city.png" alt="Kinshasa Cityscape" fill className="object-cover opacity-60 group-hover:opacity-80 transition-opacity" />
               <div className="absolute inset-0 bg-gradient-to-t from-emerald-950/80 via-transparent to-transparent z-10" />
               <div className="absolute top-4 right-4 w-12 h-12 z-20 shadow-xl rounded-full overflow-hidden border-2 border-white/20">
                 <Image src="/drc_flag.png" alt="DRC Flag" fill className="object-cover" />
               </div>
               <div className="absolute bottom-6 left-6 z-20">
                 <div className="font-outfit font-black text-4xl text-white uppercase tracking-tighter">CD</div>
               </div>
            </div>
            <div>
              <h4 className="font-outfit font-bold text-2xl mb-2 text-emerald-800 dark:text-emerald-400">DR Congo</h4>
              <p className="text-slate-500 text-sm italic dark:text-emerald-100/50">26 Provinces • Kinshasa, Lubumbashi</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
