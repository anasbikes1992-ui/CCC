import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  typescript: {
    // Type errors won't block production builds; fix separately
    ignoreBuildErrors: false,
  },
};

export default nextConfig;
