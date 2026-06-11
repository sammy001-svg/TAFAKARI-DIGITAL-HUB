import { withAuth } from "next-auth/middleware";
import { NextResponse } from "next/server";

export default withAuth(
  function middleware(req) {
    const token = req.nextauth.token;
    const { pathname } = req.nextUrl;
    const res = NextResponse.next();

    // Stamp x-pathname on every /admin response so layouts can detect the current route
    res.headers.set("x-pathname", pathname);

    // Redirect already-authenticated users away from the login page
    if (pathname === "/admin/login" && token) {
      return NextResponse.redirect(new URL("/admin/dashboard", req.url));
    }

    // Guard super-admin-only routes
    if (pathname.startsWith("/admin/super") && token?.role !== "SUPER_ADMIN") {
      return NextResponse.redirect(new URL("/admin/dashboard", req.url));
    }

    return res;
  },
  {
    callbacks: {
      authorized: ({ token, req }) => {
        const { pathname } = req.nextUrl;
        // Always allow the login page — prevents the redirect loop
        if (pathname === "/admin/login") return true;
        return !!token;
      },
    },
  }
);

export const config = {
  // Cover ALL /admin routes so x-pathname is always set for the admin layout
  matcher: ["/admin/:path*"],
};
