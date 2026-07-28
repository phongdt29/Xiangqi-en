"use client";

import { useEffect, useState } from "react";
import { isSoundEnabled, setSoundEnabled } from "../lib/sounds";

export default function SoundToggle() {
  const [enabled, setEnabled] = useState(true);

  useEffect(() => {
    // Deferred to a microtask so the initial (SSR-safe) `true` default is
    // what gets server-rendered, avoiding a hydration mismatch, while still
    // picking up the real localStorage value right after mount.
    Promise.resolve().then(() => setEnabled(isSoundEnabled()));
  }, []);

  const toggle = () => {
    const next = !enabled;
    setEnabled(next);
    setSoundEnabled(next);
  };

  return (
    <button
      type="button"
      className="btn btn-outline-secondary btn-sm"
      onClick={toggle}
      aria-pressed={enabled}
      title={enabled ? "Mute sound effects" : "Unmute sound effects"}
    >
      {enabled ? "🔊 Sound On" : "🔇 Sound Off"}
    </button>
  );
}
