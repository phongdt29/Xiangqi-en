"use client";

import { usePathname, useRouter } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import { useAuth } from "../lib/AuthContext";
import { listRooms } from "../lib/rooms-api";

/**
 * Mounted once in the root layout (not the /rooms page itself) so a logged
 * -in user gets popped up about a new open room no matter what page they're
 * currently on - not just while they happen to be sitting on the lobby.
 */
export default function RoomNotifications() {
  const { token, user } = useAuth();
  const router = useRouter();
  const pathname = usePathname();
  const [notifications, setNotifications] = useState([]);

  // Tracks room IDs already accounted for, so the first poll after login
  // doesn't fire a notification for every room that was already waiting.
  const seenRoomIdsRef = useRef(null);

  useEffect(() => {
    if (!token || !user) return undefined;

    const poll = () => {
      listRooms(token)
        .then((res) => {
          const seen = seenRoomIdsRef.current;
          if (seen) {
            const fresh = res.rooms.filter((r) => !seen.has(r.id) && r.host_id !== user.id);
            if (fresh.length > 0) {
              setNotifications((prev) => [...prev, ...fresh]);
              fresh.forEach((r) => {
                setTimeout(() => {
                  setNotifications((prev) => prev.filter((n) => n.id !== r.id));
                }, 15000);
              });
            }
          }
          seenRoomIdsRef.current = new Set(res.rooms.map((r) => r.id));
        })
        .catch(() => {});
    };

    poll();
    const interval = setInterval(poll, 5000);
    return () => clearInterval(interval);
  }, [token, user]);

  // Already looking at the lobby (which lists every open room directly) -
  // a popup on top of that view would just be noise.
  if (pathname === "/rooms" || pathname === "/rooms/" || notifications.length === 0) return null;

  return (
    <div className="position-fixed top-0 end-0 p-3" style={{ zIndex: 1080 }} aria-live="polite" aria-atomic="true">
      {notifications.map((n) => (
        <div key={n.id} className="toast show shadow mb-2" role="alert">
          <div className="toast-header">
            <strong className="me-auto">🌐 New room available</strong>
            <button
              type="button"
              className="btn-close"
              aria-label="Dismiss"
              onClick={() => setNotifications((prev) => prev.filter((x) => x.id !== n.id))}
            />
          </div>
          <div className="toast-body">
            <div className="mb-2">
              <strong>{n.host?.name}</strong>
              {n.host?.rating != null ? <span className="text-secondary"> ({n.host.rating} elo)</span> : null} is waiting.
            </div>
            <div className="mb-2 small text-secondary">
              🔑 {n.code} &nbsp;·&nbsp; ⏱️ {n.time_control ? `${n.time_control / 60} min` : "No clock"}
              {n.stake > 0 ? (
                <>
                  {" "}
                  &nbsp;·&nbsp; 💰 {n.stake} stake
                </>
              ) : null}
            </div>
            <button type="button" className="btn btn-primary btn-sm" onClick={() => router.push(`/rooms/view?id=${n.id}`)}>
              Join Now
            </button>
          </div>
        </div>
      ))}
    </div>
  );
}
