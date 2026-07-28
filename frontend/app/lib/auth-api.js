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
    error.errors = data.errors;
    error.status = res.status;
    throw error;
  }

  return data;
}

export function register({ name, email, password, password_confirmation }) {
  return request("/api/auth/register", {
    method: "POST",
    body: { name, email, password, password_confirmation },
  });
}

export function login({ email, password }) {
  return request("/api/auth/login", { method: "POST", body: { email, password } });
}

export function logout(token) {
  return request("/api/auth/logout", { method: "POST", token });
}

export function me(token) {
  return request("/api/auth/me", { token });
}
