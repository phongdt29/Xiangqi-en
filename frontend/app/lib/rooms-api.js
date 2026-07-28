const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

async function request(path, { method = "GET", token, body } = {}) {
  const headers = { Accept: "application/json" };
  if (body !== undefined) headers["Content-Type"] = "application/json";
  if (token) headers.Authorization = `Bearer ${token}`;

  const res = await fetch(`${API_BASE}${path}`, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    const error = new Error(data.message || `Request failed (${res.status})`);
    error.status = res.status;
    throw error;
  }

  return data;
}

export function listRooms(token) {
  return request("/api/rooms", { token });
}

export function createRoom(token) {
  return request("/api/rooms", { method: "POST", token });
}

export function getRoom(token, id) {
  return request(`/api/rooms/${id}`, { token });
}

export function joinRoom(token, id) {
  return request(`/api/rooms/${id}/join`, { method: "POST", token });
}

export function makeRoomMove(token, id, from, to) {
  return request(`/api/rooms/${id}/move`, { method: "POST", token, body: { from, to } });
}
