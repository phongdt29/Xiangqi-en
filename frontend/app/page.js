import Link from "next/link";
import { PIECE_GLYPHS, PIECE_NAMES } from "./lib/pieces";

const FEATURES = [
  {
    emoji: "🎮",
    title: "Local Hot-Seat Play",
    status: "Available now",
    statusClass: "text-bg-success",
    description: "Two players take turns on the same device - no account needed. The rules engine validates every move.",
    href: "/play",
    cta: "Play now",
  },
  {
    emoji: "🌐",
    title: "Real-time Online Matches",
    status: "Available now",
    statusClass: "text-bg-success",
    description: "Create a room, share the code, and play live against a friend from two different browsers over WebSockets.",
    href: "/rooms",
    cta: "Find a match",
  },
  {
    emoji: "🏆",
    title: "Leaderboard & Ranking",
    status: "Available now",
    statusClass: "text-bg-success",
    description: "Every online match updates your rating. Climb the leaderboard as you win more games.",
    href: "/leaderboard",
    cta: "View leaderboard",
  },
];

const COMING_SOON = [
  { emoji: "🤖", label: "Play vs AI" },
  { emoji: "🧩", label: "Puzzles" },
  { emoji: "⏱️", label: "Game Clock" },
  { emoji: "🔊", label: "Sound Effects" },
  { emoji: "👤", label: "Player Profiles" },
];

export default function HomePage() {
  return (
    <div>
      <section
        className="text-white text-center py-5 border-bottom border-4"
        style={{
          background: "linear-gradient(135deg, #1c1c1c 0%, #2b2410 55%, #1c1c1c 100%)",
          borderColor: "#c9971f",
        }}
      >
        <div className="container py-4">
          <h1 className="display-5 fw-bold mb-3" style={{ color: "#e8c366" }}>
            象棋 Xiangqi Online
          </h1>
          <p className="lead text-white-50 mb-4">
            Chinese Chess, right in your browser. Learn the rules, play a friend locally, or challenge someone online
            in real time.
          </p>
          <div className="d-flex justify-content-center flex-wrap gap-3">
            <Link href="/play" className="btn btn-primary btn-lg fw-semibold">
              🎮 Play Now
            </Link>
            <Link href="/rooms" className="btn btn-light btn-lg fw-semibold">
              🌐 Play Online
            </Link>
            <Link href="/rules" className="btn btn-outline-light btn-lg">
              📜 Learn the Rules
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
                  <div className="d-flex justify-content-between align-items-start mb-2">
                    <span className="fs-1" aria-hidden="true">
                      {f.emoji}
                    </span>
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
        <h2 className="h5 text-center text-secondary mb-3">Coming Soon</h2>
        <div className="d-flex flex-wrap justify-content-center gap-2">
          {COMING_SOON.map((c) => (
            <span key={c.label} className="badge rounded-pill text-bg-light border fs-6 fw-normal px-3 py-2">
              {c.emoji} {c.label}
            </span>
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
