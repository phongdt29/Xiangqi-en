const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

async function getJson(path) {
  const res = await fetch(`${API_BASE}${path}`, { headers: { Accept: "application/json" }, cache: "no-store" });
  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    throw new Error(data.message || `Request failed (${res.status})`);
  }

  return data;
}

export function listPuzzles() {
  return getJson("/api/puzzles");
}

export function getPuzzle(id) {
  return getJson(`/api/puzzles/${id}`);
}
