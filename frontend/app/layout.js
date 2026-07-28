import { Geist, Geist_Mono } from "next/font/google";
import "bootstrap/dist/css/bootstrap.min.css";
import "./globals.css";
import Footer from "./components/Footer";
import Navbar from "./components/Navbar";
import { AuthProvider } from "./lib/AuthContext";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata = {
  title: "Xiangqi Online",
  description: "Play Xiangqi (Chinese Chess) online, two players, real-time.",
};

export default function RootLayout({ children }) {
  return (
    <html lang="en" className={`${geistSans.variable} ${geistMono.variable}`}>
      <body>
        <AuthProvider>
          <Navbar />
          <main className="flex-grow-1">{children}</main>
          <Footer />
        </AuthProvider>
      </body>
    </html>
  );
}
