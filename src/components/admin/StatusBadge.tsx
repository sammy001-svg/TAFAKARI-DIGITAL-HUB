type Status = "DRAFT" | "PENDING" | "PUBLISHED" | "REJECTED" | "ARCHIVED";

const config: Record<Status, { label: string; classes: string }> = {
  DRAFT:     { label: "Draft",     classes: "bg-slate-100 text-slate-600" },
  PENDING:   { label: "Pending",   classes: "bg-amber-50 text-amber-600 ring-1 ring-amber-200" },
  PUBLISHED: { label: "Published", classes: "bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200" },
  REJECTED:  { label: "Rejected",  classes: "bg-rose-50 text-rose-600 ring-1 ring-rose-200" },
  ARCHIVED:  { label: "Archived",  classes: "bg-slate-200 text-slate-500" },
};

export default function StatusBadge({ status }: { status: Status }) {
  const { label, classes } = config[status] ?? config.DRAFT;
  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest ${classes}`}>
      {label}
    </span>
  );
}
