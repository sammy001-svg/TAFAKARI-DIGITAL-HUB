// This layout intentionally has no sidebar or shared chrome.
// It wraps only unauthenticated routes like /login.
export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
