import type { NextConfig } from 'next'

const nextConfig: NextConfig = {
  output: 'export',       // Static export for Netlify
  trailingSlash: true,    // Netlify needs this for clean URLs with static export
  images: {
    unoptimized: true,    // Required for static export
  },
}

export default nextConfig
