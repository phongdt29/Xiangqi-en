"use client";

import { useCallback, useEffect, useState } from "react";
import AdBanner from "../components/AdBanner";
import MoveHistoryList from "../components/MoveHistoryList";
import RoomClock from "../components/RoomClock";
import SoundToggle from "../components/SoundToggle";
import XiangqiBoard from "../components/XiangqiBoard";
import { playGameOverSound, playSoundForMove } from "../lib/sounds";
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

  // history[0] is the starting position, history[k] is the position after k
  // plies - Undo pops the tail, and clicking a move in MoveHistoryList shows
  // (without discarding) an earlier entry via viewingPly.
  const [history, setHistory] = useState([]);
  const [viewingPly, setViewingPly] = useState(null);

  const startNewGame = useCallback(() => {
    setLoading(true);
    setError(null);
    setSelected(null);
    setTargets([]);
    setTimedOutSide(null);
    setViewingPly(null);
    const seconds = timeControl ? Number(timeControl) : null;
    newGame()
      .then((s) => {
        const initialClock = seconds ? { seconds, redMs: seconds * 1000, blackMs: seconds * 1000, turnStartedAt: Date.now() } : null;
        setState(s);
        setClock(initialClock);
        setHistory([{ state: s, clock: initialClock }]);
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
        if (cancelled) return;
        setState(s);
        setHistory([{ state: s, clock: null }]);
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
  const viewingPast = viewingPly !== null;

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
      if (aiThinking) return;

      setAiThinking(true);
      try {
        const aiNext = await aiMove(next, difficulty);
        const lastMove = aiNext.moveHistory[aiNext.moveHistory.length - 1];
        playSoundForMove({ captured: lastMove?.captured, status: aiNext.status });
        setState(aiNext);

        let nextClock = null;
        setClock((prev) => {
          if (!prev) return prev;
          const elapsed = Date.now() - prev.turnStartedAt;
          nextClock = { ...prev, blackMs: Math.max(0, prev.blackMs - elapsed), turnStartedAt: Date.now() };
          return nextClock;
        });
        setHistory((h) => [...h, { state: aiNext, clock: nextClock }]);
      } catch (e) {
        setError(e.message);
      } finally {
        setAiThinking(false);
      }
    },
    [opponent, difficulty, aiThinking],
  );

  // Reacts to the game state itself (not just "a move was just made"), so
  // switching the opponent dropdown to "vs Computer" while it's already
  // Black's turn (e.g. mid-game, or after an Undo) still gets the computer
  // to move instead of leaving the board stuck with nothing to trigger it.
  useEffect(() => {
    if (!state) return undefined;
    const id = setTimeout(() => triggerAiIfNeeded(state), 0);
    return () => clearTimeout(id);
  }, [state, triggerAiIfNeeded]);

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
      if (!state || gameOver || aiThinking || viewingPast) return;
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

          let nextClock = null;
          setClock((prev) => {
            if (!prev) return prev;
            const field = mover === "red" ? "redMs" : "blackMs";
            const elapsed = Date.now() - prev.turnStartedAt;
            nextClock = { ...prev, [field]: Math.max(0, prev[field] - elapsed), turnStartedAt: Date.now() };
            return nextClock;
          });
          setHistory((h) => [...h, { state: next, clock: nextClock }]);
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
    [state, selected, targets, gameOver, aiThinking, viewingPast, opponent, selectPiece, clock],
  );

  const handleUndo = useCallback(() => {
    setHistory((prev) => {
      if (prev.length <= 1) return prev;
      // Undo a full round-trip against the computer, so it's the human's
      // turn again immediately instead of leaving it "the computer's turn"
      // with nothing to trigger its reply.
      const popCount = opponent === "computer" && prev.length > 2 ? 2 : 1;
      const next = prev.slice(0, prev.length - popCount);
      const last = next[next.length - 1];
      setState(last.state);
      setClock(last.clock ? { ...last.clock, turnStartedAt: Date.now() } : null);
      return next;
    });
    setSelected(null);
    setTargets([]);
    setTimedOutSide(null);
    setViewingPly(null);
  }, [opponent]);

  const displayState = viewingPast ? history[viewingPly].state : state;

  return (
    <div className="container py-4">
      <header className="mb-4">
        <div className="d-flex justify-content-end mb-2">
          <SoundToggle />
        </div>
        <div className="text-center">
          <h1 className="fw-bold h3">Local Hot-Seat Game</h1>
          <p className="text-secondary mb-0">Two players, one browser - take turns tapping your pieces.</p>
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
              active={!gameOver && !viewingPast}
            />

            {viewingPast ? (
              <div className="d-flex justify-content-center align-items-center gap-2 mb-3">
                <span className="badge fs-6 text-bg-info">
                  Viewing move {viewingPly} of {history.length - 1}
                </span>
                <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => setViewingPly(null)}>
                  Back to Live
                </button>
              </div>
            ) : (
              <div className="d-flex justify-content-center mb-3">
                <span
                  className={`badge fs-6 ${gameOver ? "text-bg-dark" : state.status === "check" ? "text-bg-warning" : "text-bg-secondary"}`}
                >
                  {aiThinking ? "🤖 Computer is thinking..." : statusMessage(state, timedOutSide)}
                </span>
              </div>
            )}

            <div className="d-flex justify-content-center">
              <XiangqiBoard
                board={displayState.board}
                selected={viewingPast ? null : selected}
                legalTargets={viewingPast ? [] : targets}
                onCellClick={handleCellClick}
                disabled={gameOver || aiThinking || viewingPast || (opponent === "computer" && state.turn === "black")}
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
              <button
                type="button"
                className="btn btn-outline-secondary"
                onClick={handleUndo}
                disabled={history.length <= 1 || aiThinking}
              >
                ↩️ Undo
              </button>
              <button type="button" className="btn btn-primary" onClick={startNewGame}>
                New Game
              </button>
            </div>
          </div>

          <div className="col-12 col-lg-3">
            <h2 className="h6 text-secondary">Move History</h2>
            <MoveHistoryList
              moves={state.moveHistory}
              onSelectMove={(ply) => setViewingPly(ply + 1)}
              currentPly={viewingPly !== null ? viewingPly - 1 : null}
            />
          </div>
        </div>
      )}

      <AdBanner slot="ingame" />
    </div>
  );
}
