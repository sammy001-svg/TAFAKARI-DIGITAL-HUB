import Link from "next/link";

export default function AboutPage() {
  return (
    <div className="max-w-7xl mx-auto px-6 py-20">
      {/* Header */}
      <div className="flex flex-col gap-4 mb-16">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/10 text-secondary text-sm font-semibold w-fit border border-secondary/20">
          <span className="flex h-2 w-2 rounded-full bg-secondary animate-pulse" />
          Our Story
        </div>
        <h1 className="font-outfit text-4xl md:text-6xl font-bold leading-tight">
          About{" "}
          <span className="text-primary">
            Tafakari Hub
          </span>
        </h1>
        <p className="text-slate-500 max-w-2xl text-lg leading-relaxed">
          Tafakari &mdash; meaning &ldquo;to reflect&rdquo; in Swahili &mdash; is a multi-user digital platform
          serving as a centralized knowledge repository, media broadcasting center, and community
          engagement tool across Kenya, Ethiopia, and the Democratic Republic of Congo.
        </p>
      </div>

      {/* Mission Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20">
        {[
          {
            icon: "📡",
            title: "Media Broadcasting",
            text: "Galleries, podcasts, and video libraries documenting field activities and regional oral reports.",
          },
          {
            icon: "📚",
            title: "Knowledge Repository",
            text: "A structured library of research reports, policy briefs, and actionable datasets for public use.",
          },
          {
            icon: "🤝",
            title: "Community Engagement",
            text: "Moderated discussions and contact submissions ensuring safe and constructive regional dialogue.",
          },
        ].map((card) => (
          <div
            key={card.title}
            className="glass p-8 rounded-3xl border-secondary/20 text-white flex flex-col gap-4"
          >
            <div className="text-4xl">{card.icon}</div>
            <h3 className="font-outfit font-bold text-xl">{card.title}</h3>
            <p className="text-white/70 text-sm leading-relaxed">{card.text}</p>
          </div>
        ))}
      </div>

      {/* Countries Section */}
      <div className="rounded-[3rem] bg-[#3B0708] p-12 md:p-16 text-white text-center">
        <h2 className="font-outfit text-3xl md:text-4xl font-bold mb-4">Our Geographic Focus</h2>
        <p className="text-slate-300 max-w-2xl mx-auto mb-12 leading-relaxed">
          We operate across three strategic nations in East and Central Africa, providing structured
          information sharing and data visualization.
        </p>
        <div className="flex flex-wrap justify-center gap-6">
          {["🇰🇪 Kenya", "🇪🇹 Ethiopia", "🇨🇩 DR Congo"].map((country) => (
            <div
              key={country}
              className="px-8 py-4 bg-primary/20 rounded-2xl font-outfit font-bold text-lg border border-secondary/20"
            >
              {country}
            </div>
          ))}
        </div>
        <div className="mt-12">
          <Link
            href="/contact"
            className="inline-block bg-primary text-white px-10 py-4 rounded-full font-bold hover:bg-primary/90 transition-all shadow-xl border border-secondary/20"
          >
            Connect with Us →
          </Link>
        </div>
      </div>
    </div>
  );
}
