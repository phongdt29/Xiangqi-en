import Link from "next/link";
import { PIECE_GLYPHS, PIECE_NAMES } from "./lib/pieces";

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

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
  {
    emoji: "🤖",
    title: "Play vs Computer",
    status: "Available now",
    statusClass: "text-bg-success",
    description: "No opponent around? Play Red against an AI on Easy, Medium, or Hard, right from the local game screen.",
    href: "/play",
    cta: "Challenge the AI",
  },
  {
    emoji: "🧩",
    title: "Puzzles",
    status: "Available now",
    statusClass: "text-bg-success",
    description: "Find the checkmate. Short, focused positions that sharpen your tactics - no account required.",
    href: "/puzzles",
    cta: "Solve a puzzle",
  },
  {
    emoji: "🎴",
    title: "Hidden Pieces (Jiéqí)",
    status: "Available now",
    statusClass: "text-bg-success",
    description: "Every piece except the Generals starts face-down and shuffled. Bluff, deduce, and reveal your way to checkmate.",
    href: "/hidden-pieces",
    cta: "Play Hidden Pieces",
  },
];

const STEPS = [
  { emoji: "📝", title: "Sign Up", body: "Create a free account in seconds - just a name, email, and password." },
  { emoji: "🚪", title: "Create or Join a Room", body: "Pick a clock (or none), then share your room code with a friend." },
  { emoji: "⚔️", title: "Play in Real Time", body: "Moves sync instantly over WebSockets. Win to climb the leaderboard." },
];

function SectionTitle({ children, light = false }) {
  return (
    <div className="text-center mb-4">
      <h2 className={`h3 fw-bold mb-2 ${light ? "text-white" : ""}`}>{children}</h2>
      <div
        className="mx-auto"
        style={{ width: 56, height: 3, background: light ? "#fff" : "#1a56db", borderRadius: 2, opacity: light ? 0.8 : 1 }}
      />
    </div>
  );
}

async function getPlayerCount() {
  try {
    const res = await fetch(`${API_BASE}/api/leaderboard`, { cache: "no-store" });
    if (!res.ok) return null;
    const data = await res.json();
    return data.players?.length ?? null;
  } catch {
    return null;
  }
}

export default async function HomePage() {
  const playerCount = await getPlayerCount();

  return (
    <div>
      <section
        className="text-white text-center py-5 position-relative overflow-hidden border-bottom border-4"
        style={{
          background: "linear-gradient(135deg, #1c1c1c 0%, #12233d 55%, #1c1c1c 100%)",
          borderColor: "#1a56db",
        }}
      >
        <div
          className="position-absolute top-0 start-0 w-100 h-100"
          style={{
            backgroundImage: "radial-gradient(circle, rgba(26,86,219,0.25) 1.5px, transparent 1.5px)",
            backgroundSize: "28px 28px",
            pointerEvents: "none",
          }}
          aria-hidden="true"
        />
        <div className="container py-5 position-relative">
          <h1 className="display-4 fw-bold mb-3" style={{ color: "#8fb8f0", letterSpacing: "0.02em" }}>
            象棋 Xiangqi Online
          </h1>
          <p className="lead text-white-50 mb-4 mx-auto" style={{ maxWidth: 620 }}>
            Chinese Chess, right in your browser. Learn the rules, play a friend locally, or challenge someone online
            in real time.
          </p>
          <div className="d-flex justify-content-center flex-wrap gap-3 mb-4">
            <Link href="/play" className="btn btn-primary btn-lg fw-semibold hover-lift">
              🎮 Play Now
            </Link>
            <Link href="/rooms" className="btn btn-light btn-lg fw-semibold hover-lift">
              🌐 Play Online
            </Link>
            <Link href="/rules" className="btn btn-outline-light btn-lg hover-lift">
              📜 Learn the Rules
            </Link>
          </div>
          {playerCount !== null && playerCount > 0 && (
            <p className="text-white-50 small mb-0">
              🏆 {playerCount} player{playerCount === 1 ? "" : "s"} already on the leaderboard - free to play, forever.
            </p>
          )}
        </div>
      </section>

      <section className="container py-5">
        <SectionTitle>Ways to Play</SectionTitle>
        <div className="row g-4">
          {FEATURES.map((f) => (
            <div className="col-12 col-md-4" key={f.title}>
              <div className="card h-100 shadow-sm border-0 hover-lift">
                <div className="card-body d-flex flex-column">
                  <div className="d-flex justify-content-between align-items-start mb-3">
                    <span
                      className="d-inline-flex align-items-center justify-content-center rounded-circle fs-3"
                      style={{ width: 56, height: 56, background: "var(--bs-primary-bg-subtle)" }}
                      aria-hidden="true"
                    >
                      {f.emoji}
                    </span>
                    <span className={`badge rounded-pill ${f.statusClass}`}>{f.status}</span>
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

      <section className="bg-light py-5">
        <div className="container">
          <SectionTitle>How It Works</SectionTitle>
          <div className="row g-4">
            {STEPS.map((step, i) => (
              <div className="col-12 col-md-4 text-center" key={step.title}>
                <div
                  className="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold mb-3 mx-auto"
                  style={{ width: 48, height: 48, background: "#1a56db", color: "#fff" }}
                >
                  {i + 1}
                </div>
                <div className="fs-2 mb-2" aria-hidden="true">
                  {step.emoji}
                </div>
                <h3 className="h6 fw-bold">{step.title}</h3>
                <p className="text-secondary small mb-0">{step.body}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="bg-light py-5">
        <div className="container">
          <SectionTitle>The Pieces</SectionTitle>
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
        </div>
      </section>

      <section
        className="text-center py-5"
        style={{ background: "linear-gradient(135deg, #0f2d6e 0%, #1a56db 55%, #123785 100%)" }}
      >
        <div className="container">
          <SectionTitle light>Ready to test your skills?</SectionTitle>
          <Link href="/play" className="btn btn-lg btn-warning fw-semibold hover-lift">
            🎮 Play Now - It&apos;s Free
          </Link>
        </div>
      </section>
    </div>
  );
}
