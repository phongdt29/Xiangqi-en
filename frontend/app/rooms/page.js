"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { useAuth } from "../lib/AuthContext";
import { createRoom, listRooms } from "../lib/rooms-api";

export default function RoomsLobbyPage() {
  const { user, token, loading: authLoading } = useAuth();
  const router = useRouter();
  const [rooms, setRooms] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [creating, setCreating] = useState(false);
  const [timeControl, setTimeControl] = useState("");
  const [stake, setStake] = useState("");

  const refresh = useCallback(() => {
    if (!token) return;
    setLoading(true);
    setError(null);
    listRooms(token)
      .then((res) => setRooms(res.rooms))
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [token]);

  // Fetch on mount directly (not via `refresh`, which also performs
  // synchronous state resets that the set-state-in-effect lint flags).
  useEffect(() => {
    if (!token) return undefined;
    let cancelled = false;
    listRooms(token)
      .then((res) => {
        if (!cancelled) setRooms(res.rooms);
      })
      .catch((e) => {
        if (!cancelled) setError(e.message);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [token]);

  const handleCreate = async () => {
    setCreating(true);
    setError(null);
    try {
      const room = await createRoom(token, timeControl ? Number(timeControl) : null, stake ? Number(stake) : 0);
      router.push(`/rooms/view?id=${room.id}`);
    } catch (e) {
      setError(e.message);
      setCreating(false);
    }
  };

  if (!authLoading && !user) {
    return (
      <div className="container py-5 text-center">
        <h1 className="h3 fw-bold mb-3">🌐 Online Matches</h1>
        <p className="text-secondary mb-4">You need an account to create or join an online room.</p>
        <div className="d-flex justify-content-center gap-2">
          <Link href="/login" className="btn btn-primary">
            Login
          </Link>
          <Link href="/register" className="btn btn-outline-primary">
            Sign Up
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="container py-5">
      <header className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
          <h1 className="h3 fw-bold mb-1">🌐 Online Matches</h1>
          <p className="text-secondary mb-0">
            Create a room and share the code, or join someone waiting. Your balance:{" "}
            <Link href="/points" className="fw-semibold text-decoration-none">
              💰 {user?.points ?? 0}
            </Link>
          </p>
        </div>
        <div className="d-flex flex-wrap gap-2">
          <select
            className="form-select"
            style={{ width: "auto" }}
            value={timeControl}
            onChange={(e) => setTimeControl(e.target.value)}
            aria-label="Clock"
          >
            <option value="">⏱️ No clock</option>
            <option value="300">⏱️ 5 min</option>
            <option value="600">⏱️ 10 min</option>
            <option value="900">⏱️ 15 min</option>
          </select>
          <input
            type="number"
            min="0"
            className="form-control"
            style={{ width: 140 }}
            placeholder="💰 Stake (points)"
            value={stake}
            onChange={(e) => setStake(e.target.value)}
            aria-label="Stake"
          />
          <button type="button" className="btn btn-outline-secondary" onClick={refresh} disabled={loading}>
            Refresh
          </button>
          <button type="button" className="btn btn-primary" onClick={handleCreate} disabled={creating}>
            {creating ? "Creating..." : "Create Room"}
          </button>
        </div>
      </header>

      {error && (
        <div className="alert alert-danger" role="alert">
          {error}
        </div>
      )}

      {loading && <p>Loading rooms...</p>}

      {!loading && rooms.length === 0 && (
        <p className="text-secondary">No open rooms right now. Create one and share the link with a friend.</p>
      )}

      {rooms.length > 0 && (
        <div className="table-responsive">
          <table className="table align-middle">
            <thead className="table-light">
              <tr>
                <th>Code</th>
                <th>Host</th>
                <th>Clock</th>
                <th>Stake</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {rooms.map((r) => (
                <tr key={r.id}>
                  <td className="fw-semibold">{r.code}</td>
                  <td>{r.host?.name}</td>
                  <td>{r.time_control ? `${r.time_control / 60} min` : "No clock"}</td>
                  <td>{r.stake > 0 ? `💰 ${r.stake}` : "-"}</td>
                  <td className="text-end">
                    <Link href={`/rooms/view?id=${r.id}`} className="btn btn-sm btn-outline-primary">
                      {r.host_id === user?.id ? "View" : "Join"}
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
