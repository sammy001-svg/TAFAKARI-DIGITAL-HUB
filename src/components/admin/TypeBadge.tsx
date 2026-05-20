type ContentType = "ARTICLE" | "GALLERY_IMAGE" | "PODCAST" | "VIDEO" | "DOCUMENT";

const config: Record<ContentType, { label: string; icon: string }> = {
  ARTICLE:       { label: "Article",  icon: "📄" },
  GALLERY_IMAGE: { label: "Gallery",  icon: "🖼️" },
  PODCAST:       { label: "Podcast",  icon: "🎧" },
  VIDEO:         { label: "Video",    icon: "🎬" },
  DOCUMENT:      { label: "Document", icon: "📁" },
};

export default function TypeBadge({ type }: { type: ContentType }) {
  const { label, icon } = config[type] ?? config.ARTICLE;
  return (
    <span className="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-widest text-secondary">
      <span>{icon}</span>
      <span>{label}</span>
    </span>
  );
}
