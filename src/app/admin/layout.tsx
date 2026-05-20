export const dynamic = "force-dynamic";

import { headers } from "next/headers";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import { redirect } from "next/navigation";
import AdminSidebar from "@/components/layout/AdminSidebar";

export default async function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const headersList = await headers();
  const pathname = headersList.get("x-pathname") ?? "";
  const isLoginPage = pathname === "/admin/login";

  // Skip auth check and sidebar for the login page to avoid infinite redirect loop
  if (isLoginPage) {
    return <>{children}</>;
  }

  const session = await getServerSession(authOptions);
  if (!session) redirect("/admin/login");

  return (
    // Fixed overlay covers the public Navbar and Footer from the root layout
    <div className="fixed inset-0 z-50 flex bg-slate-50 overflow-hidden">
      <AdminSidebar />
      <main className="grow h-full overflow-y-auto p-10">
        {children}
      </main>
    </div>
  );
}
