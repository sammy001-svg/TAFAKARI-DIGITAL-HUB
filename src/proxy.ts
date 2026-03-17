import { withAuth } from "next-auth/middleware";
import { NextResponse } from "next/server";

export default withAuth(
  function middleware(req) {
    const token = req.nextauth.token;
    const isAuth = !!token;
    const isAdminRoute = req.nextUrl.pathname.startsWith("/admin");
    const isSuperAdminRoute = req.nextUrl.pathname.startsWith("/admin/super");

    if (isAdminRoute && !isAuth) {
      return NextResponse.redirect(new URL("/admin/login", req.url));
    }

    if (isSuperAdminRoute && token?.role !== "SUPER_ADMIN") {
      return NextResponse.redirect(new URL("/admin/dashboard", req.url));
    }

    return NextResponse.next();
  },
  {
    callbacks: {
      authorized: ({ token }) => !!token,
    },
  }
);

export const config = {
  matcher: ["/admin/dashboard/:path*", "/admin/super/:path*", "/admin/content/:path*"],
};
