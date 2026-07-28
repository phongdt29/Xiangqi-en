"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useAuth } from "../lib/AuthContext";
import { myRooms } from "../lib/rooms-api";

function opponentName(room, myId) {
  const opponent = room.host_id === myId ? room.guest : room.host;
  return opponent?.name ?? (room.status === "waiting" ? "(waiting for opponent)" : "-");
}

function resultBadge(room, myId) {
  if (room.status !== "finished") {
    return <span className="badge text-bg-secondary">{room.status === "waiting" ? "Waiting" : "In progress"}</span>;
  }
  if (room.winner_id === myId) return <span className="badge text-bg-success">Win</span>;
  if (room.winner_id) return <span className="badge text-bg-danger">Loss</span>;
  return <span className="badge text-bg-secondary">Ended</span>;
}

export default function ProfilePage() {
  const { user, token, loading: authLoading } = useAuth();
  const [rooms, setRooms] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!token) return undefined;
    let cancelled = false;
    myRooms(token)
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

  if (!authLoading && !user) {
    return (
      <div className="container py-5 text-center">
        <h1 className="h3 fw-bold mb-3">👤 Profile</h1>
        <p className="text-secondary mb-4">Login to see your stats and match history.</p>
        <Link href="/login" className="btn btn-primary">
          Login
        </Link>
      </div>
    );
  }

  if (!user) return null;

  const totalGames = user.wins + user.losses + user.draws;
  const winRate = totalGames > 0 ? Math.round((user.wins / totalGames) * 100) : 0;

  return (
    <div className="container py-5">
      <header className="text-center mb-5">
        <h1 className="fw-bold">👤 {user.name}</h1>
        <p className="text-secondary mb-0">{user.email}</p>
      </header>

      <div className="row row-cols-2 row-cols-md-4 g-3 mb-5 text-center">
        <div className="col">
          <div className="card h-100">
            <div className="card-body">
              <div className="display-6 fw-bold text-primary">{user.rating}</div>
              <div className="text-secondary small">Rating</div>
            </div>
          </div>
        </div>
        <div className="col">
          <div className="card h-100">
            <div className="card-body">
              <div className="display-6 fw-bold text-success">{user.wins}</div>
              <div className="text-secondary small">Wins</div>
            </div>
          </div>
        </div>
        <div className="col">
          <div className="card h-100">
            <div className="card-body">
              <div className="display-6 fw-bold text-danger">{user.losses}</div>
              <div className="text-secondary small">Losses</div>
            </div>
          </div>
        </div>
        <div className="col">
          <div className="card h-100">
            <div className="card-body">
              <div className="display-6 fw-bold">{winRate}%</div>
              <div className="text-secondary small">Win Rate</div>
            </div>
          </div>
        </div>
      </div>

      <h2 className="h5 mb-3">Match History</h2>

      {error && (
        <div className="alert alert-danger" role="alert">
          {error}
        </div>
      )}

      {loading && <p className="text-secondary">Loading matches...</p>}

      {!loading && rooms.length === 0 && (
        <p className="text-secondary">
          No online matches yet - <Link href="/rooms">find one</Link> to start building your record.
        </p>
      )}

      {rooms.length > 0 && (
        <div className="table-responsive">
          <table className="table align-middle">
            <thead className="table-light">
              <tr>
                <th>Opponent</th>
                <th>Side</th>
                <th>Result</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {rooms.map((r) => (
                <tr key={r.id}>
                  <td>{opponentName(r, user.id)}</td>
                  <td>{r.host_id === user.id ? "Red" : "Black"}</td>
                  <td>{resultBadge(r, user.id)}</td>
                  <td className="text-end">
                    <Link href={`/rooms/${r.id}`} className="btn btn-sm btn-outline-primary">
                      View
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
