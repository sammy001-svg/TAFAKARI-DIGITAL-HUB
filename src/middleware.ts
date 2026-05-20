import { withAuth } from "next-auth/middleware";
import { NextResponse } from "next/server";

export default withAuth(
  function middleware(req) {
    const token = req.nextauth.token;
    const { pathname } = req.nextUrl;

    // Stamp the current path onto every response so server layouts can read it
    const res = NextResponse.next();
    res.headers.set("x-pathname", pathname);

    // Block non-super-admins from super-admin routes
    if (pathname.startsWith("/admin/super") && token?.role !== "SUPER_ADMIN") {
      return NextResponse.redirect(new URL("/admin/dashboard", req.url));
    }

    return res;
  },
  {
    callbacks: {
      // NextAuth redirects to pages.signIn when this returns false
      authorized: ({ token }) => !!token,
    },
  }
);

export const config = {
  // Protect every /admin/* route EXCEPT the login page itself
  matcher: [
    "/admin/dashboard/:path*",
    "/admin/content/:path*",
    "/admin/profile/:path*",
    "/admin/super/:path*",
    "/admin/media/:path*",
  ],
};
