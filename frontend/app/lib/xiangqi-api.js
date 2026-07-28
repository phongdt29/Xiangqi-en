const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

async function postJson(path, body) {
  const res = await fetch(`${API_BASE}${path}`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify(body ?? {}),
  });

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    throw new Error(data.message || `Request failed (${res.status})`);
  }

  return data;
}

export function newGame() {
  return postJson("/api/xiangqi/new");
}

export function makeMove(state, from, to) {
  return postJson("/api/xiangqi/move", { ...state, from, to });
}

export function legalMoves(board, from) {
  return postJson("/api/xiangqi/legal-moves", { board, from });
}
