// Empty layout for admin auth pages (login, etc.)
// This prevents the AdminSidebar from wrapping unauthenticated pages.
export default function AdminAuthLayout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
