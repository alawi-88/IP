import type { NextConfig } from "next";
import createNextIntlPlugin from "next-intl/plugin";

const withNextIntl = createNextIntlPlugin();

// Backend URL for proxying storage files (images, uploads, etc.)
const internalBackendUrl =
  process.env.INTERNAL_API_ENDPOINT?.replace("/api", "") ||
  "http://localhost:8080";

const nextConfig: NextConfig = {
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "**",
      },
      {
        protocol: "http",
        hostname: "**",
      },
    ],
  },
  async rewrites() {
    return [
      {
        source: "/storage/:path*",
        destination: `${internalBackendUrl}/storage/:path*`,
      },
    ];
  },
  // async headers() {
  //   if (process.env.NODE_ENV === "development") {
  //     // No caching in dev
  //     return [
  //       {
  //         source: "/(.*)",
  //         headers: [
  //           {
  //             key: "Cache-Control",
  //             value: "no-store",
  //           },
  //         ],
  //       },
  //     ];
  //   }
  //   // Cache in production
  //   return [
  //     {
  //       source: "/(.*)",
  //       headers: [
  //         {
  //           key: "Cache-Control",
  //           value: "public, max-age=2628000",
  //         },
  //       ],
  //     },
  //   ];
  // },
};

export default withNextIntl(nextConfig);