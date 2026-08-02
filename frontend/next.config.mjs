/** @type {import('next').NextConfig} */
const nextConfig = {
  // No Node.js on the host - export plain HTML/CSS/JS servable by Apache.
  output: "export",
  // Apache's default DirectoryIndex serves `/rooms/index.html` for a
  // `/rooms/` request with zero extra rewrite rules; without this, clean
  // URLs like `/rooms` (no trailing slash, no .html) would 404.
  trailingSlash: true,
};

export default nextConfig;
