const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

async function request(path, { method = "GET", body } = {}) {
  const headers = { Accept: "application/json" };
  if (body !== undefined) headers["Content-Type"] = "application/json";

  const res = await fetch(`${API_BASE}${path}`, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    throw new Error(data.message || `Request failed (${res.status})`);
  }

  return data;
}

export function createGame() {
  return request("/api/co-up-games", { method: "POST" });
}

export function getGame(id) {
  return request(`/api/co-up-games/${id}`);
}

export function legalMoves(id, from) {
  return request(`/api/co-up-games/${id}/legal-moves`, { method: "POST", body: { from } });
}

export function makeMove(id, from, to) {
  return request(`/api/co-up-games/${id}/move`, { method: "POST", body: { from, to } });
}
