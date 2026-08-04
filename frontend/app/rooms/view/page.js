"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Suspense, useCallback, useEffect, useRef, useState } from "react";
import AdBanner from "../../components/AdBanner";
import MoveHistoryList from "../../components/MoveHistoryList";
import RoomClock from "../../components/RoomClock";
import SoundToggle from "../../components/SoundToggle";
import XiangqiBoard from "../../components/XiangqiBoard";
import { useAuth } from "../../lib/AuthContext";
import { cancelRoom, claimTimeout, getRoom, joinRoom, makeRoomMove } from "../../lib/rooms-api";
import { playSoundForMove } from "../../lib/sounds";
import { legalMoves } from "../../lib/xiangqi-api";

const SIDE_LABEL = { red: "Red", black: "Black" };

function statusMessage(room) {
  if (!room) return "";
  if (room.status === "finished") {
    const winnerSide = room.result === "red_win" ? "Red" : room.result === "black_win" ? "Black" : null;
    if (!winnerSide) return "Game over.";
    return `${winnerSide} wins ${room.gameStatus === "timeout" ? "on time" : "by checkmate"}!`;
  }

  const mover = SIDE_LABEL[room.turn];
  if (room.gameStatus === "check") return `${mover} is in check. ${mover} to move.`;
  return `${mover} to move.`;
}

function RoomPageInner() {
  const id = useSearchParams().get("id");
  const { user, token, loading: authLoading, refreshUser } = useAuth();

  const [room, setRoom] = useState(null);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selected, setSelected] = useState(null);
  const [targets, setTargets] = useState([]);
  const [joining, setJoining] = useState(false);
  const [cancelling, setCancelling] = useState(false);

  // Tracks how many moves we've already played a sound for, so a move made
  // by "me" doesn't sound twice: once from the direct API response and once
  // from the next poll picking up the same move.
  const soundedCountRef = useRef(0);

  const maybePlaySound = useCallback((payload) => {
    if (!payload?.moveHistory || payload.moveHistory.length <= soundedCountRef.current) return;
    soundedCountRef.current = payload.moveHistory.length;
    const lastMove = payload.moveHistory[payload.moveHistory.length - 1];
    playSoundForMove({ captured: lastMove?.captured, status: payload.gameStatus });
  }, []);

  // A finished game may have just settled a stake - re-fetch so the navbar
  // and profile balance reflect the payout without needing a full reload.
  const finishedRef = useRef(false);
  const maybeSyncBalance = useCallback(
    (payload) => {
      if (payload?.status === "finished" && !finishedRef.current) {
        finishedRef.current = true;
        refreshUser();
      }
    },
    [refreshUser],
  );

  useEffect(() => {
    if (!token || !id) return undefined;
    let cancelled = false;
    getRoom(token, id)
      .then((r) => {
        if (cancelled) return;
        soundedCountRef.current = r.moveHistory?.length ?? 0;
        setRoom(r);
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
  }, [token, id]);

  // Poll for the opponent's moves instead of a realtime push subscription
  // (no self-hosted WebSocket server on this hosting plan).
  const pollingRef = useRef(false);
  useEffect(() => {
    if (!token || !id || !room || room.status === "finished") return undefined;

    const interval = setInterval(() => {
      if (pollingRef.current) return;
      pollingRef.current = true;
      getRoom(token, id)
        .then((next) => {
          const prevCount = soundedCountRef.current;
          maybePlaySound(next);
          maybeSyncBalance(next);
          setRoom(next);
          if ((next.moveHistory?.length ?? 0) !== prevCount) {
            setSelected(null);
            setTargets([]);
          }
        })
        .catch(() => {})
        .finally(() => {
          pollingRef.current = false;
        });
    }, 2500);

    return () => clearInterval(interval);
  }, [token, id, room?.status, maybePlaySound, maybeSyncBalance]);

  // With a clock running, someone has to notice a side ran out of time even
  // if the opponent never submits another move - poll locally and ask the
  // server to end the game once the side to move's clock would read zero.
  const claimingRef = useRef(false);
  useEffect(() => {
    if (!token || !id || !room || room.status !== "active" || !room.timeControl) return undefined;

    const interval = setInterval(() => {
      const remaining = room.turn === "red" ? room.redRemainingMs : room.blackRemainingMs;
      const elapsed = room.turnStartedAt ? Date.now() - new Date(room.turnStartedAt).getTime() : 0;
      if (remaining - elapsed > 0 || claimingRef.current) return;

      claimingRef.current = true;
      claimTimeout(token, id)
        .then((next) => {
          maybePlaySound(next);
          maybeSyncBalance(next);
          setRoom(next);
        })
        .catch(() => {})
        .finally(() => {
          claimingRef.current = false;
        });
    }, 1000);

    return () => clearInterval(interval);
  }, [token, id, room, maybePlaySound, maybeSyncBalance]);

  const myRole = !room || !user ? null : user.id === room.host?.id ? "red" : user.id === room.guest?.id ? "black" : null;

  const myTurn = room && room.status === "active" && myRole && room.turn === myRole;

  const handleJoin = async () => {
    if (room?.stake > 0 && !window.confirm(`Joining will stake 💰 ${room.stake} points. Continue?`)) return;
    setJoining(true);
    setError(null);
    try {
      const next = await joinRoom(token, id);
      setRoom(next);
      await refreshUser();
    } catch (e) {
      setError(e.message);
    } finally {
      setJoining(false);
    }
  };

  const handleCancel = async () => {
    setCancelling(true);
    setError(null);
    try {
      const next = await cancelRoom(token, id);
      setRoom(next);
      await refreshUser();
    } catch (e) {
      setError(e.message);
    } finally {
      setCancelling(false);
    }
  };

  const selectPiece = useCallback(
    async (x, y) => {
      setSelected({ x, y });
      setError(null);
      try {
        const res = await legalMoves(room.board, { x, y });
        setTargets(res.moves);
      } catch (e) {
        setTargets([]);
        setError(e.message);
      }
    },
    [room],
  );

  const handleCellClick = useCallback(
    async (x, y) => {
      if (!room || !myTurn) return;
      const piece = room.board[y][x];

      if (selected && selected.x === x && selected.y === y) {
        setSelected(null);
        setTargets([]);
        return;
      }

      const isTarget = targets.some((t) => t.x === x && t.y === y);
      if (selected && isTarget) {
        try {
          const next = await makeRoomMove(token, id, selected, { x, y });
          maybePlaySound(next);
          maybeSyncBalance(next);
          setRoom(next);
          setSelected(null);
          setTargets([]);
          setError(null);
        } catch (e) {
          setError(e.message);
        }
        return;
      }

      if (piece && piece.side === myRole) {
        await selectPiece(x, y);
        return;
      }

      setSelected(null);
      setTargets([]);
    },
    [room, myTurn, selected, targets, myRole, token, id, selectPiece, maybePlaySound, maybeSyncBalance],
  );

  if (!id) {
    return (
      <div className="container py-5 text-center">
        <h1 className="h3 fw-bold mb-3">Room not found</h1>
        <Link href="/rooms" className="btn btn-primary">
          Back to lobby
        </Link>
      </div>
    );
  }

  if (!authLoading && !user) {
    return (
      <div className="container py-5 text-center">
        <h1 className="h3 fw-bold mb-3">Online Match</h1>
        <p className="text-secondary mb-4">You need an account to view or play this room.</p>
        <Link href="/login" className="btn btn-primary">
          Login
        </Link>
      </div>
    );
  }

  return (
    <div className="container py-4">
      <header className="mb-4">
        <div className="d-flex justify-content-end mb-2">
          <SoundToggle />
        </div>
        <div className="text-center">
          <h1 className="h3 fw-bold">Room {room?.code ?? ""}</h1>
          <p className="text-secondary mb-0">
            <span className="fw-semibold">Red:</span> {room?.host?.name ?? "?"} &nbsp;vs&nbsp;
            <span className="fw-semibold">Black:</span> {room?.guest?.name ?? "waiting..."}
          </p>
          {room?.stake > 0 && (
            <p className="text-secondary mb-0">
              💰 Staked: <span className="fw-semibold">{room.stake}</span> points each - winner takes {room.winnerPayout}{" "}
              <span className="text-secondary">(20% platform fee)</span>
            </p>
          )}
        </div>
      </header>

      {error && (
        <div className="alert alert-danger py-2" role="alert">
          {error}
        </div>
      )}

      {loading && <p className="text-center">Loading room...</p>}

      {!loading && room && room.status === "waiting" && (
        <div className="text-center">
          {myRole === "red" ? (
            <>
              <p className="text-secondary">
                Waiting for an opponent to join. Share code <strong>{room.code}</strong>.
              </p>
              <button type="button" className="btn btn-outline-danger" onClick={handleCancel} disabled={cancelling}>
                {cancelling ? "Cancelling..." : "Cancel Room"}
              </button>
            </>
          ) : (
            <button type="button" className="btn btn-primary btn-lg" onClick={handleJoin} disabled={joining}>
              {joining ? "Joining..." : room.stake > 0 ? `Join & Stake 💰 ${room.stake}` : "Join This Game"}
            </button>
          )}
        </div>
      )}

      {!loading && room && room.status === "abandoned" && (
        <div className="text-center">
          <p className="text-secondary">This room was cancelled by the host.</p>
        </div>
      )}

      {!loading && room && room.board && (
        <div className="row justify-content-center g-4">
          <div className="col-12 col-lg-auto">
            <RoomClock
              timeControl={room.timeControl}
              redRemainingMs={room.redRemainingMs}
              blackRemainingMs={room.blackRemainingMs}
              turn={room.turn}
              turnStartedAt={room.turnStartedAt}
              active={room.status === "active"}
            />

            <div className="d-flex justify-content-center mb-3">
              <span
                className={`badge fs-6 ${room.status === "finished" ? "text-bg-dark" : room.gameStatus === "check" ? "text-bg-warning" : "text-bg-secondary"}`}
              >
                {statusMessage(room)}
              </span>
            </div>

            {myRole && room.status === "active" && (
              <p className="text-center text-secondary">
                You are playing <strong>{SIDE_LABEL[myRole]}</strong>
                {myTurn ? " - your move." : " - waiting for the opponent."}
              </p>
            )}
            {!myRole && <p className="text-center text-secondary">You are spectating this match.</p>}

            <div className="d-flex justify-content-center">
              <XiangqiBoard
                board={room.board}
                selected={selected}
                legalTargets={targets}
                onCellClick={handleCellClick}
                disabled={!myTurn}
              />
            </div>
          </div>

          <div className="col-12 col-lg-3">
            <h2 className="h6 text-secondary">Move History</h2>
            <MoveHistoryList moves={room.moveHistory} />
          </div>
        </div>
      )}

      <AdBanner slot="ingame" />

      <div className="text-center mt-4">
        <Link href="/rooms" className="btn btn-link">
          Back to lobby
        </Link>
      </div>
    </div>
  );
}

export default function RoomPage() {
  return (
    <Suspense fallback={<p className="text-center py-5">Loading room...</p>}>
      <RoomPageInner />
    </Suspense>
  );
}
