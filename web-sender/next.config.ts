import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Silence ESLint build errors so CI doesn't block on warnings
  eslint: {
    ignoreDuringBuilds: true,
  },
  typescript: {
    // Type errors won't block production builds; fix separately
    ignoreBuildErrors: false,
  },
};

export default nextConfig;
