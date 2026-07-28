"use client";

import { useCallback, useEffect, useState } from "react";
import MoveHistoryList from "../components/MoveHistoryList";
import SoundToggle from "../components/SoundToggle";
import XiangqiBoard from "../components/XiangqiBoard";
import { playSoundForMove } from "../lib/sounds";
import { legalMoves, makeMove, newGame } from "../lib/xiangqi-api";

const SIDE_LABEL = { red: "Red", black: "Black" };

function statusMessage(state) {
  if (!state) return "";
  const mover = SIDE_LABEL[state.turn];
  const opponent = state.turn === "red" ? "Black" : "Red";

  switch (state.status) {
    case "checkmate":
      return `Checkmate - ${opponent} wins!`;
    case "stalemate":
      return `Stalemate - ${mover} has no legal move and loses!`;
    case "check":
      return `${mover} is in check. ${mover} to move.`;
    default:
      return `${mover} to move.`;
  }
}

export default function PlayPage() {
  const [state, setState] = useState(null);
  const [selected, setSelected] = useState(null);
  const [targets, setTargets] = useState([]);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);

  const startNewGame = useCallback(() => {
    setLoading(true);
    setError(null);
    setSelected(null);
    setTargets([]);
    newGame()
      .then(setState)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  // Fetch the initial game on mount directly (rather than calling
  // startNewGame, which also performs synchronous state resets that
  // React's rules-of-hooks lint flags when run inside an effect body).
  useEffect(() => {
    let cancelled = false;
    newGame()
      .then((s) => {
        if (!cancelled) setState(s);
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
  }, []);

  const gameOver = state && (state.status === "checkmate" || state.status === "stalemate");

  const selectPiece = useCallback(
    async (x, y) => {
      setSelected({ x, y });
      setError(null);
      try {
        const res = await legalMoves(state.board, { x, y });
        setTargets(res.moves);
      } catch (e) {
        setTargets([]);
        setError(e.message);
      }
    },
    [state],
  );

  const handleCellClick = useCallback(
    async (x, y) => {
      if (!state || gameOver) return;
      const piece = state.board[y][x];

      if (selected && selected.x === x && selected.y === y) {
        setSelected(null);
        setTargets([]);
        return;
      }

      const isTarget = targets.some((t) => t.x === x && t.y === y);
      if (selected && isTarget) {
        try {
          const next = await makeMove(state, selected, { x, y });
          const lastMove = next.moveHistory[next.moveHistory.length - 1];
          playSoundForMove({ captured: lastMove?.captured, status: next.status });
          setState(next);
          setSelected(null);
          setTargets([]);
          setError(null);
        } catch (e) {
          setError(e.message);
        }
        return;
      }

      if (piece && piece.side === state.turn) {
        await selectPiece(x, y);
        return;
      }

      setSelected(null);
      setTargets([]);
    },
    [state, selected, targets, gameOver, selectPiece],
  );

  return (
    <div className="container py-4">
      <header className="mb-4 text-center position-relative">
        <h1 className="fw-bold h3">Local Hot-Seat Game</h1>
        <p className="text-secondary mb-0">Two players, one browser - take turns tapping your pieces.</p>
        <div className="position-absolute top-0 end-0">
          <SoundToggle />
        </div>
      </header>

      {error && (
        <div className="alert alert-danger py-2" role="alert">
          {error}
        </div>
      )}

      {loading && <p>Loading game...</p>}

      {state && (
        <div className="row justify-content-center g-4">
          <div className="col-12 col-lg-auto">
            <div className="d-flex justify-content-center mb-3">
              <span
                className={`badge fs-6 ${gameOver ? "text-bg-dark" : state.status === "check" ? "text-bg-warning" : "text-bg-secondary"}`}
              >
                {statusMessage(state)}
              </span>
            </div>

            <div className="d-flex justify-content-center">
              <XiangqiBoard
                board={state.board}
                selected={selected}
                legalTargets={targets}
                onCellClick={handleCellClick}
                disabled={gameOver}
              />
            </div>

            <div className="d-flex justify-content-center align-items-center gap-3 mt-4">
              <button type="button" className="btn btn-primary" onClick={startNewGame}>
                New Game
              </button>
            </div>
          </div>

          <div className="col-12 col-lg-3">
            <h2 className="h6 text-secondary">Move History</h2>
            <MoveHistoryList moves={state.moveHistory} />
          </div>
        </div>
      )}
    </div>
  );
}
