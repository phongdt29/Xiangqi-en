"use client";

import { useCallback, useEffect, useState } from "react";
import MoveHistoryList from "../components/MoveHistoryList";
import RoomClock from "../components/RoomClock";
import SoundToggle from "../components/SoundToggle";
import XiangqiBoard from "../components/XiangqiBoard";
import { playSoundForMove, playGameOverSound } from "../lib/sounds";
import { aiMove, legalMoves, makeMove, newGame } from "../lib/xiangqi-api";

const SIDE_LABEL = { red: "Red", black: "Black" };

function statusMessage(state, timedOutSide) {
  if (!state) return "";

  if (timedOutSide) {
    const winner = timedOutSide === "red" ? "Black" : "Red";
    return `Time's up - ${winner} wins on time!`;
  }

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
  const [timeControl, setTimeControl] = useState("");
  const [clock, setClock] = useState(null);
  const [timedOutSide, setTimedOutSide] = useState(null);
  const [opponent, setOpponent] = useState("human");
  const [difficulty, setDifficulty] = useState("medium");
  const [aiThinking, setAiThinking] = useState(false);

  const startNewGame = useCallback(() => {
    setLoading(true);
    setError(null);
    setSelected(null);
    setTargets([]);
    setTimedOutSide(null);
    const seconds = timeControl ? Number(timeControl) : null;
    newGame()
      .then((s) => {
        setState(s);
        setClock(seconds ? { seconds, redMs: seconds * 1000, blackMs: seconds * 1000, turnStartedAt: Date.now() } : null);
      })
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [timeControl]);

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

  const gameOver = state && (state.status === "checkmate" || state.status === "stalemate" || timedOutSide);

  // A local ticking check so a side loses on time even if nobody clicks
  // again - mirrors the server-side clock used for online rooms.
  useEffect(() => {
    if (!clock || !state || gameOver) return undefined;

    const interval = setInterval(() => {
      const remaining = state.turn === "red" ? clock.redMs : clock.blackMs;
      const elapsed = Date.now() - clock.turnStartedAt;
      if (elapsed >= remaining) {
        setTimedOutSide(state.turn);
        playGameOverSound();
      }
    }, 250);

    return () => clearInterval(interval);
  }, [clock, state, gameOver]);

  // The computer always plays Black. Its "thinking time" is deducted from
  // Black's own clock exactly like a human move would be - same field, same
  // math, no special-casing needed there.
  const triggerAiIfNeeded = useCallback(
    async (next) => {
      if (opponent !== "computer" || next.turn !== "black") return;
      if (next.status === "checkmate" || next.status === "stalemate") return;

      setAiThinking(true);
      try {
        const aiNext = await aiMove(next, difficulty);
        const lastMove = aiNext.moveHistory[aiNext.moveHistory.length - 1];
        playSoundForMove({ captured: lastMove?.captured, status: aiNext.status });
        setState(aiNext);
        setClock((prev) => {
          if (!prev) return prev;
          const elapsed = Date.now() - prev.turnStartedAt;
          return { ...prev, blackMs: Math.max(0, prev.blackMs - elapsed), turnStartedAt: Date.now() };
        });
      } catch (e) {
        setError(e.message);
      } finally {
        setAiThinking(false);
      }
    },
    [opponent, difficulty],
  );

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
      if (!state || gameOver || aiThinking) return;
      if (opponent === "computer" && state.turn === "black") return;
      const piece = state.board[y][x];

      if (selected && selected.x === x && selected.y === y) {
        setSelected(null);
        setTargets([]);
        return;
      }

      const isTarget = targets.some((t) => t.x === x && t.y === y);
      if (selected && isTarget) {
        if (clock) {
          const remaining = state.turn === "red" ? clock.redMs : clock.blackMs;
          if (Date.now() - clock.turnStartedAt >= remaining) {
            setTimedOutSide(state.turn);
            playGameOverSound();
            return;
          }
        }

        try {
          const mover = state.turn;
          const next = await makeMove(state, selected, { x, y });
          const lastMove = next.moveHistory[next.moveHistory.length - 1];
          playSoundForMove({ captured: lastMove?.captured, status: next.status });
          setState(next);
          setSelected(null);
          setTargets([]);
          setError(null);
          setClock((prev) => {
            if (!prev) return prev;
            const field = mover === "red" ? "redMs" : "blackMs";
            const elapsed = Date.now() - prev.turnStartedAt;
            return { ...prev, [field]: Math.max(0, prev[field] - elapsed), turnStartedAt: Date.now() };
          });
          await triggerAiIfNeeded(next);
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
    [state, selected, targets, gameOver, aiThinking, opponent, selectPiece, clock, triggerAiIfNeeded],
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
            <RoomClock
              timeControl={clock?.seconds ?? null}
              redRemainingMs={clock?.redMs}
              blackRemainingMs={clock?.blackMs}
              turn={state.turn}
              turnStartedAt={clock ? new Date(clock.turnStartedAt).toISOString() : null}
              active={!gameOver}
            />

            <div className="d-flex justify-content-center mb-3">
              <span
                className={`badge fs-6 ${gameOver ? "text-bg-dark" : state.status === "check" ? "text-bg-warning" : "text-bg-secondary"}`}
              >
                {aiThinking ? "🤖 Computer is thinking..." : statusMessage(state, timedOutSide)}
              </span>
            </div>

            <div className="d-flex justify-content-center">
              <XiangqiBoard
                board={state.board}
                selected={selected}
                legalTargets={targets}
                onCellClick={handleCellClick}
                disabled={gameOver || aiThinking || (opponent === "computer" && state.turn === "black")}
              />
            </div>

            <div className="d-flex flex-wrap justify-content-center align-items-center gap-2 mt-4">
              <select
                className="form-select"
                style={{ width: "auto" }}
                value={opponent}
                onChange={(e) => setOpponent(e.target.value)}
                aria-label="Opponent"
              >
                <option value="human">🧑‍🤝‍🧑 Human vs Human</option>
                <option value="computer">🤖 vs Computer</option>
              </select>
              {opponent === "computer" && (
                <select
                  className="form-select"
                  style={{ width: "auto" }}
                  value={difficulty}
                  onChange={(e) => setDifficulty(e.target.value)}
                  aria-label="Difficulty"
                >
                  <option value="easy">Easy</option>
                  <option value="medium">Medium</option>
                  <option value="hard">Hard</option>
                </select>
              )}
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
