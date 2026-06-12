import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
  reactStrictMode: true,

  // Keep Prisma outside the webpack bundle so its native query-engine
  // binary is preserved in the standalone output rather than being
  // tree-shaken or incorrectly bundled.
  serverExternalPackages: ["@prisma/client", "prisma"],

  // Explicitly trace the generated Prisma client binary into the
  // standalone directory. Required for cPanel / Node.js deployments
  // where the .next/standalone folder must be fully self-contained.
  outputFileTracingIncludes: {
    "/**": [
      "./node_modules/.prisma/client/**",
      "./node_modules/@prisma/client/**",
    ],
  },

  images: {
    remotePatterns: [
      { protocol: "https", hostname: "**" },
      { protocol: "http", hostname: "**" },
    ],
  },
};

export default nextConfig;
