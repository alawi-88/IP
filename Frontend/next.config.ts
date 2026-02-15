import type { NextConfig } from "next";
import createNextIntlPlugin from "next-intl/plugin";

const withNextIntl = createNextIntlPlugin();

const nextConfig: NextConfig = {
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "**",
      },
    ],
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