"use client";

import Link from "next/link";
import { use, useCallback, useEffect, useState } from "react";
import MoveHistoryList from "../../components/MoveHistoryList";
import SoundToggle from "../../components/SoundToggle";
import XiangqiBoard from "../../components/XiangqiBoard";
import { getPuzzle } from "../../lib/puzzles-api";
import { playGameOverSound, playSoundForMove } from "../../lib/sounds";
import { aiMove, legalMoves, makeMove } from "../../lib/xiangqi-api";

const SIDE_LABEL = { red: "Red", black: "Black" };

export default function PuzzlePage({ params }) {
  const { id } = use(params);

  const [puzzle, setPuzzle] = useState(null);
  const [state, setState] = useState(null);
  const [movesUsed, setMovesUsed] = useState(0);
  const [solved, setSolved] = useState(false);
  const [failed, setFailed] = useState(false);
  const [aiThinking, setAiThinking] = useState(false);
  const [selected, setSelected] = useState(null);
  const [targets, setTargets] = useState([]);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);
  const [boardHistory, setBoardHistory] = useState([]);
  const [viewingPly, setViewingPly] = useState(null);

  const load = useCallback(() => {
    setLoading(true);
    setError(null);
    setSelected(null);
    setTargets([]);
    setMovesUsed(0);
    setSolved(false);
    setFailed(false);
    setViewingPly(null);
    getPuzzle(id)
      .then((p) => {
        setPuzzle(p);
        setState(p.initial);
        setBoardHistory([p.initial.board]);
      })
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [id]);

  useEffect(() => {
    let cancelled = false;
    getPuzzle(id)
      .then((p) => {
        if (cancelled) return;
        setPuzzle(p);
        setState(p.initial);
        setBoardHistory([p.initial.board]);
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
  }, [id]);

  const solvedOrFailed = solved || failed;
  const viewingPast = viewingPly !== null;

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
      if (!state || solvedOrFailed || aiThinking || viewingPast) return;
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
          setBoardHistory((h) => [...h, next.board]);
          setSelected(null);
          setTargets([]);
          setError(null);

          const used = movesUsed + 1;
          setMovesUsed(used);

          if (next.status === "checkmate") {
            setSolved(true);
            return;
          }

          if (used >= puzzle.mateIn) {
            setFailed(true);
            playGameOverSound();
            return;
          }

          setAiThinking(true);
          try {
            const aiNext = await aiMove(next, "hard");
            const aiLastMove = aiNext.moveHistory[aiNext.moveHistory.length - 1];
            playSoundForMove({ captured: aiLastMove?.captured, status: aiNext.status });
            setState(aiNext);
            setBoardHistory((h) => [...h, aiNext.board]);
          } finally {
            setAiThinking(false);
          }
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
    [state, selected, targets, solvedOrFailed, aiThinking, viewingPast, movesUsed, puzzle, selectPiece],
  );

  return (
    <div className="container py-4">
      <header className="mb-4 text-center position-relative">
        <h1 className="fw-bold h3">🧩 {puzzle?.title ?? "Puzzle"}</h1>
        {puzzle && (
          <p className="text-secondary mb-0">
            Playing {SIDE_LABEL[puzzle.initial.turn]} - mate in {puzzle.mateIn} move{puzzle.mateIn === 1 ? "" : "s"}.
          </p>
        )}
        <div className="position-absolute top-0 end-0">
          <SoundToggle />
        </div>
      </header>

      {error && (
        <div className="alert alert-danger py-2" role="alert">
          {error}
        </div>
      )}

      {loading && <p className="text-center">Loading puzzle...</p>}

      {state && (
        <div className="row justify-content-center g-4">
          <div className="col-12 col-lg-auto">
            {viewingPast ? (
              <div className="d-flex justify-content-center align-items-center gap-2 mb-3">
                <span className="badge fs-6 text-bg-info">
                  Viewing move {viewingPly} of {boardHistory.length - 1}
                </span>
                <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => setViewingPly(null)}>
                  Back to Live
                </button>
              </div>
            ) : (
              <div className="d-flex justify-content-center mb-3">
                {solved && <span className="badge fs-6 text-bg-success">✅ Solved!</span>}
                {failed && <span className="badge fs-6 text-bg-danger">Not quite - try again.</span>}
                {!solvedOrFailed && (
                  <span className="badge fs-6 text-bg-secondary">
                    {aiThinking ? "🤖 Opponent replying..." : `${SIDE_LABEL[state.turn]} to move`}
                  </span>
                )}
              </div>
            )}

            <div className="d-flex justify-content-center">
              <XiangqiBoard
                board={viewingPast ? boardHistory[viewingPly] : state.board}
                selected={viewingPast ? null : selected}
                legalTargets={viewingPast ? [] : targets}
                onCellClick={handleCellClick}
                disabled={solvedOrFailed || aiThinking || viewingPast}
              />
            </div>

            <div className="d-flex justify-content-center align-items-center gap-3 mt-4">
              <button type="button" className="btn btn-primary" onClick={load}>
                {solvedOrFailed ? "Try Again" : "Restart"}
              </button>
              <Link href="/puzzles" className="btn btn-link">
                All puzzles
              </Link>
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
    </div>
  );
}
