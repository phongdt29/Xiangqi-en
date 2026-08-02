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

export function listPackages(token) {
  return request("/api/points/packages", { token });
}

export function createPointsOrder(token, packageKey) {
  return request("/api/points/orders", { method: "POST", token, body: { package: packageKey } });
}

export function capturePointsOrder(token, orderId) {
  return request(`/api/points/orders/${orderId}/capture`, { method: "POST", token });
}
