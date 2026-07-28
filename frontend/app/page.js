import Link from "next/link";
import { PIECE_GLYPHS, PIECE_NAMES } from "./lib/pieces";

const FEATURES = [
  {
    title: "Local Hot-Seat Play",
    status: "Available now",
    statusClass: "text-bg-success",
    description: "Two players take turns on the same device - no account needed. The rules engine validates every move.",
    href: "/play",
    cta: "Play now",
  },
  {
    title: "Real-time Online Matches",
    status: "Coming soon",
    statusClass: "text-bg-secondary",
    description: "Create a room, invite a friend, and play live from two different browsers over WebSockets.",
    href: "/register",
    cta: "Get notified",
  },
  {
    title: "Leaderboard & Ranking",
    status: "Coming soon",
    statusClass: "text-bg-secondary",
    description: "Every match updates your rating. Climb the leaderboard as you win more games.",
    href: "/leaderboard",
    cta: "View leaderboard",
  },
];

export default function HomePage() {
  return (
    <div>
      <section className="bg-dark text-white text-center py-5">
        <div className="container py-4">
          <h1 className="display-5 fw-bold mb-3">Xiangqi Online</h1>
          <p className="lead text-white-50 mb-4">
            Chinese Chess (象棋), right in your browser. Learn the rules, play a friend locally today, and challenge
            players online soon.
          </p>
          <div className="d-flex justify-content-center gap-3">
            <Link href="/play" className="btn btn-warning btn-lg fw-semibold">
              Play Now
            </Link>
            <Link href="/rules" className="btn btn-outline-light btn-lg">
              Learn the Rules
            </Link>
          </div>
        </div>
      </section>

      <section className="container py-5">
        <div className="row g-4">
          {FEATURES.map((f) => (
            <div className="col-12 col-md-4" key={f.title}>
              <div className="card h-100 shadow-sm">
                <div className="card-body d-flex flex-column">
                  <div className="mb-2">
                    <span className={`badge ${f.statusClass}`}>{f.status}</span>
                  </div>
                  <h2 className="h5 card-title">{f.title}</h2>
                  <p className="card-text text-secondary flex-grow-1">{f.description}</p>
                  <Link href={f.href} className="btn btn-outline-primary mt-2 align-self-start">
                    {f.cta}
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>
      </section>

      <section className="container pb-5">
        <h2 className="h4 text-center mb-4">The Pieces</h2>
        <p className="text-center text-secondary mb-4">
          Piece names are shown in English throughout the app, but pieces themselves keep their traditional Chinese
          characters on the board.
        </p>
        <div className="row row-cols-2 row-cols-sm-4 row-cols-md-7 g-3 justify-content-center text-center">
          {Object.keys(PIECE_NAMES).map((type) => (
            <div className="col" key={type}>
              <div className="d-flex flex-column align-items-center gap-2">
                <div className="d-flex gap-2">
                  <span className="xq-piece xq-piece-red" style={{ position: "static" }}>
                    {PIECE_GLYPHS.red[type]}
                  </span>
                  <span className="xq-piece xq-piece-black" style={{ position: "static" }}>
                    {PIECE_GLYPHS.black[type]}
                  </span>
                </div>
                <small className="text-secondary">{PIECE_NAMES[type]}</small>
              </div>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}
