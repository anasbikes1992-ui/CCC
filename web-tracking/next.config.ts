import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  typescript: {
    ignoreBuildErrors: false,
  },
  async rewrites() {
    return [
      {
        source: "/api/v1/:path*",
        destination: "https://ccc-production-30a5.up.railway.app/api/v1/:path*",
      },
    ];
  },
};

export default nextConfig;
