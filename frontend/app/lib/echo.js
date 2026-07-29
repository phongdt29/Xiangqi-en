"use client";

import Echo from "laravel-echo";
import Pusher from "pusher-js";

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";
const REVERB_KEY = process.env.NEXT_PUBLIC_REVERB_APP_KEY;
const REVERB_HOST = process.env.NEXT_PUBLIC_REVERB_HOST || "127.0.0.1";
const REVERB_PORT = Number(process.env.NEXT_PUBLIC_REVERB_PORT || 8080);
// Defaults to "http" for local dev; production must set this to "https" so
// the browser connects over WSS - a page served over HTTPS refuses to open
// a plain ws:// socket (mixed content), so this can't stay hardcoded false.
const REVERB_SCHEME = process.env.NEXT_PUBLIC_REVERB_SCHEME || "http";

export function createEcho(token) {
  return new Echo({
    broadcaster: "reverb",
    Pusher,
    key: REVERB_KEY,
    wsHost: REVERB_HOST,
    wsPort: REVERB_PORT,
    wssPort: REVERB_PORT,
    forceTLS: REVERB_SCHEME === "https",
    enabledTransports: ["ws", "wss"],
    authEndpoint: `${API_BASE}/broadcasting/auth`,
    auth: {
      headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
    },
  });
}
