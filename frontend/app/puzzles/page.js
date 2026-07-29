import Link from "next/link";

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

export const metadata = {
  title: "Puzzles - Xiangqi Online",
};

const DIFFICULTY_BADGE = {
  easy: "text-bg-success",
  medium: "text-bg-warning",
  hard: "text-bg-danger",
};

async function getPuzzles() {
  try {
    const res = await fetch(`${API_BASE}/api/puzzles`, { cache: "no-store" });
    if (!res.ok) return { puzzles: [], error: `Request failed (${res.status})` };
    const data = await res.json();
    return { puzzles: data.puzzles ?? [] };
  } catch {
    return { puzzles: [], error: "Could not reach the server." };
  }
}

export default async function PuzzlesPage() {
  const { puzzles, error } = await getPuzzles();

  return (
    <div className="container py-5">
      <header className="text-center mb-5">
        <h1 className="fw-bold">🧩 Puzzles</h1>
        <p className="text-secondary">Find the move (or moves) that deliver checkmate. No account needed.</p>
      </header>

      {error && (
        <div className="alert alert-warning" role="alert">
          {error}
        </div>
      )}

      {!error && puzzles.length === 0 && <p className="text-center text-secondary">No puzzles available yet.</p>}

      <div className="row g-4">
        {puzzles.map((p) => (
          <div className="col-12 col-md-6 col-lg-4" key={p.id}>
            <div className="card h-100 shadow-sm hover-lift">
              <div className="card-body d-flex flex-column">
                <div className="d-flex justify-content-between align-items-start mb-2">
                  <span className={`badge rounded-pill ${DIFFICULTY_BADGE[p.difficulty] ?? "text-bg-secondary"}`}>
                    {p.difficulty}
                  </span>
                  <span className="badge text-bg-light border">Mate in {p.mateIn}</span>
                </div>
                <h2 className="h5 card-title">{p.title}</h2>
                <Link href={`/puzzles/${p.id}`} className="btn btn-outline-primary mt-auto align-self-start">
                  Solve it
                </Link>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
