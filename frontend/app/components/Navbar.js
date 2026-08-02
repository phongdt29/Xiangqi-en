"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect } from "react";
import { useAuth } from "../lib/AuthContext";

const LINKS = [
  { href: "/", label: "Home" },
  { href: "/play", label: "Play Locally" },
  { href: "/rooms", label: "Online" },
  { href: "/puzzles", label: "Puzzles" },
  { href: "/hidden-pieces", label: "Hidden Pieces" },
  { href: "/rules", label: "Rules" },
  { href: "/leaderboard", label: "Leaderboard" },
];

export default function Navbar() {
  const pathname = usePathname();
  const router = useRouter();
  const { user, loading, logout } = useAuth();

  // Bootstrap's JS touches `document` at import time, so it must only ever
  // load in the browser - a static top-level import breaks SSR/prerendering.
  useEffect(() => {
    import("bootstrap/dist/js/bootstrap.bundle.min.js");
  }, []);

  const handleLogout = async () => {
    await logout();
    router.push("/");
  };

  return (
    <nav className="navbar navbar-expand-md navbar-dark bg-dark sticky-top">
      <div className="container">
        <Link className="navbar-brand fw-bold" href="/">
          象棋 Chinesechess Online
        </Link>
        <button
          className="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#mainNav"
          aria-controls="mainNav"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span className="navbar-toggler-icon" />
        </button>
        <div className="collapse navbar-collapse" id="mainNav">
          <ul className="navbar-nav me-auto mb-2 mb-md-0">
            {LINKS.map((link) => {
              const isActive = link.href === "/" ? pathname === "/" : pathname.startsWith(link.href);
              return (
                <li className="nav-item" key={link.href}>
                  <Link
                    className={`nav-link ${isActive ? "active fw-semibold" : ""}`}
                    href={link.href}
                    aria-current={isActive ? "page" : undefined}
                  >
                    {link.label}
                  </Link>
                </li>
              );
            })}
          </ul>
          <div className="d-flex align-items-center gap-2">
            {!loading && user && (
              <>
                <Link href="/points" className="text-white-50 small text-decoration-none">
                  💰 <span className="text-white fw-semibold">{user.points}</span>
                </Link>
                <Link href="/profile" className="text-white-50 small text-decoration-none">
                  👤 <span className="text-white fw-semibold">{user.name}</span>
                </Link>
                <button type="button" className="btn btn-outline-light btn-sm" onClick={handleLogout}>
                  Logout
                </button>
              </>
            )}
            {!loading && !user && (
              <>
                <Link href="/login" className="btn btn-outline-light btn-sm">
                  Login
                </Link>
                <Link href="/register" className="btn btn-primary btn-sm fw-semibold">
                  Sign Up
                </Link>
              </>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
}
