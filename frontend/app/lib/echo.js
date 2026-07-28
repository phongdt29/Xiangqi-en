"use client";

import Echo from "laravel-echo";
import Pusher from "pusher-js";

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";
const REVERB_KEY = process.env.NEXT_PUBLIC_REVERB_APP_KEY;
const REVERB_HOST = process.env.NEXT_PUBLIC_REVERB_HOST || "127.0.0.1";
const REVERB_PORT = Number(process.env.NEXT_PUBLIC_REVERB_PORT || 8080);

export function createEcho(token) {
  return new Echo({
    broadcaster: "reverb",
    Pusher,
    key: REVERB_KEY,
    wsHost: REVERB_HOST,
    wsPort: REVERB_PORT,
    wssPort: REVERB_PORT,
    forceTLS: false,
    enabledTransports: ["ws", "wss"],
    authEndpoint: `${API_BASE}/broadcasting/auth`,
    auth: {
      headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
    },
  });
}
