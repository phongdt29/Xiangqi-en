import Link from "next/link";

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

export const metadata = {
  title: "Leaderboard - Chinesechess Online",
};

async function getLeaderboard() {
  try {
    const res = await fetch(`${API_BASE}/api/leaderboard`, { cache: "no-store" });
    if (!res.ok) return { players: [], error: `Request failed (${res.status})` };
    const data = await res.json();
    return { players: data.players ?? [] };
  } catch {
    return { players: [], error: "Could not reach the server." };
  }
}

export default async function LeaderboardPage() {
  const { players, error } = await getLeaderboard();

  return (
    <div className="container py-5">
      <header className="text-center mb-5">
        <h1 className="fw-bold">🏆 Leaderboard</h1>
        <p className="text-secondary">Top players ranked by rating.</p>
      </header>

      {error && (
        <div className="alert alert-warning" role="alert">
          {error}
        </div>
      )}

      {!error && players.length === 0 && (
        <p className="text-center text-secondary">
          No ranked players yet - <Link href="/register">create an account</Link> and play a match to appear here.
        </p>
      )}

      {players.length > 0 && (
        <div className="table-responsive">
          <table className="table table-striped align-middle">
            <thead className="table-dark">
              <tr>
                <th style={{ width: "1%" }}>#</th>
                <th>Player</th>
                <th className="text-end">Rating</th>
                <th className="text-end">Wins</th>
                <th className="text-end">Losses</th>
                <th className="text-end">Draws</th>
              </tr>
            </thead>
            <tbody>
              {players.map((p, i) => (
                <tr key={p.id}>
                  <td>{i + 1}</td>
                  <td className="fw-semibold">{p.name}</td>
                  <td className="text-end">{p.rating}</td>
                  <td className="text-end">{p.wins}</td>
                  <td className="text-end">{p.losses}</td>
                  <td className="text-end">{p.draws}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
