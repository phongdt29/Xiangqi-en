/** @type {import('next').NextConfig} */
const nextConfig = {
  // cPanel's "Setup Node.js App" (Passenger) runs a single entry file -
  // standalone output produces a self-contained server.js for that.
  output: "standalone",
};

export default nextConfig;
