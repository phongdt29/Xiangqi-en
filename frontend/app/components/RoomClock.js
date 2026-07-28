"use client";

import { useEffect, useState } from "react";

function formatMs(ms) {
  const totalSeconds = Math.max(0, Math.ceil(ms / 1000));
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${minutes}:${String(seconds).padStart(2, "0")}`;
}

/**
 * Purely presentational ticking clock. The parent room page owns polling
 * `claim-timeout` when a side actually reaches zero - this component only
 * re-renders once a second so the countdown looks live between server updates.
 */
export default function RoomClock({ timeControl, redRemainingMs, blackRemainingMs, turn, turnStartedAt, active }) {
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    if (!active) return undefined;
    const interval = setInterval(() => setNow(Date.now()), 250);
    return () => clearInterval(interval);
  }, [active]);

  if (!timeControl) return null;

  const elapsed = active && turnStartedAt ? now - new Date(turnStartedAt).getTime() : 0;
  const redDisplay = turn === "red" ? redRemainingMs - elapsed : redRemainingMs;
  const blackDisplay = turn === "black" ? blackRemainingMs - elapsed : blackRemainingMs;

  return (
    <div className="d-flex justify-content-center gap-4 mb-3">
      <div className={`text-center px-3 py-1 rounded ${turn === "red" && active ? "bg-danger-subtle" : ""}`}>
        <div className="small text-secondary">Red</div>
        <div className={`fs-5 fw-bold font-monospace ${redDisplay <= 30000 ? "text-danger" : ""}`}>
          {formatMs(redDisplay)}
        </div>
      </div>
      <div className={`text-center px-3 py-1 rounded ${turn === "black" && active ? "bg-danger-subtle" : ""}`}>
        <div className="small text-secondary">Black</div>
        <div className={`fs-5 fw-bold font-monospace ${blackDisplay <= 30000 ? "text-danger" : ""}`}>
          {formatMs(blackDisplay)}
        </div>
      </div>
    </div>
  );
}
