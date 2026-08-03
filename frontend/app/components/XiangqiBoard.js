"use client";

import { useEffect, useRef, useState } from "react";
import { PIECE_GLYPHS, PIECE_NAMES } from "../lib/pieces";

const CELL = 56;
const PAD = 32;
const COLS = 9; // x: 0-8
const ROWS = 10; // y: 0-9
const BOARD_W = PAD * 2 + (COLS - 1) * CELL;
const BOARD_H = PAD * 2 + (ROWS - 1) * CELL;

// Data y=0 is Red's back rank; render it at the bottom of the screen.
function toScreenTop(y) {
  return PAD + (ROWS - 1 - y) * CELL;
}

function toScreenLeft(x) {
  return PAD + x * CELL;
}

function hasTarget(targets, x, y) {
  return targets.some((t) => t.x === x && t.y === y);
}

export default function XiangqiBoard({ board, selected, legalTargets, onCellClick, disabled }) {
  // The board itself is laid out in fixed pixels (matches the coordinate
  // math used by toScreenLeft/Top above) - on a narrow viewport it's shrunk
  // to fit via a CSS transform instead, so every position/line/piece scales
  // together without duplicating the layout math in percentages.
  const wrapperRef = useRef(null);
  const [scale, setScale] = useState(1);

  useEffect(() => {
    const el = wrapperRef.current;
    if (!el) return undefined;

    const update = () => {
      const available = el.clientWidth;
      setScale(available > 0 ? Math.min(1, available / BOARD_W) : 1);
    };

    update();
    const observer = new ResizeObserver(update);
    observer.observe(el);
    return () => observer.disconnect();
  }, []);

  const horizontalLines = Array.from({ length: ROWS }, (_, r) => (
    <line
      key={`h-${r}`}
      x1={PAD}
      y1={PAD + r * CELL}
      x2={PAD + (COLS - 1) * CELL}
      y2={PAD + r * CELL}
      stroke="#5b3a1e"
      strokeWidth={1}
    />
  ));

  const verticalLines = Array.from({ length: COLS }, (_, c) => {
    const x = PAD + c * CELL;
    if (c === 0 || c === COLS - 1) {
      return (
        <line key={`v-${c}`} x1={x} y1={PAD} x2={x} y2={PAD + (ROWS - 1) * CELL} stroke="#5b3a1e" strokeWidth={1} />
      );
    }
    // Interior columns break across the river (between rows 4 and 5).
    return (
      <g key={`v-${c}`}>
        <line x1={x} y1={PAD} x2={x} y2={PAD + 4 * CELL} stroke="#5b3a1e" strokeWidth={1} />
        <line x1={x} y1={PAD + 5 * CELL} x2={x} y2={PAD + (ROWS - 1) * CELL} stroke="#5b3a1e" strokeWidth={1} />
      </g>
    );
  });

  const palaceDiagonals = [0, 7].map((topRow) => (
    <g key={`palace-${topRow}`}>
      <line
        x1={PAD + 3 * CELL}
        y1={PAD + topRow * CELL}
        x2={PAD + 5 * CELL}
        y2={PAD + (topRow + 2) * CELL}
        stroke="#5b3a1e"
        strokeWidth={1}
      />
      <line
        x1={PAD + 5 * CELL}
        y1={PAD + topRow * CELL}
        x2={PAD + 3 * CELL}
        y2={PAD + (topRow + 2) * CELL}
        stroke="#5b3a1e"
        strokeWidth={1}
      />
    </g>
  ));

  const cells = [];
  for (let y = 0; y < ROWS; y++) {
    for (let x = 0; x < COLS; x++) {
      const piece = board?.[y]?.[x] ?? null;
      // `revealed === false` only ever appears on masked Hidden Pieces cells - every
      // other caller's pieces have no `revealed` field at all, so this never
      // triggers for standard Xiangqi boards.
      const faceDown = piece?.revealed === false;
      const isSelected = selected && selected.x === x && selected.y === y;
      const isTarget = hasTarget(legalTargets, x, y);

      cells.push(
        <button
          key={`${x}-${y}`}
          type="button"
          disabled={disabled}
          onClick={() => onCellClick(x, y)}
          className="xq-cell"
          style={{ left: toScreenLeft(x), top: toScreenTop(y) }}
          aria-label={
            piece
              ? faceDown
                ? `${piece.side} unrevealed piece at column ${x + 1}, row ${y + 1}`
                : `${piece.side} ${PIECE_NAMES[piece.type]} at column ${x + 1}, row ${y + 1}`
              : `Empty square at column ${x + 1}, row ${y + 1}`
          }
        >
          {isTarget && <span className={`xq-target ${piece ? "xq-target-capture" : ""}`} />}
          {piece && (
            <span className={`xq-piece xq-piece-${piece.side} ${isSelected ? "xq-piece-selected" : ""}`}>
              {faceDown ? "☐" : PIECE_GLYPHS[piece.side][piece.type]}
            </span>
          )}
        </button>,
      );
    }
  }

  return (
    <div ref={wrapperRef} className="d-flex justify-content-center" style={{ width: "100%" }}>
      <div style={{ width: BOARD_W * scale, height: BOARD_H * scale, overflow: "hidden" }}>
        <div
          className="xq-board"
          style={{ width: BOARD_W, height: BOARD_H, transform: `scale(${scale})`, transformOrigin: "top left" }}
        >
          <svg width={BOARD_W} height={BOARD_H} className="xq-board-svg">
            {horizontalLines}
            {verticalLines}
            {palaceDiagonals}
          </svg>
          <div className="xq-river" style={{ top: PAD + 4 * CELL, height: CELL, left: PAD, width: (COLS - 1) * CELL }}>
            <span>Chu River</span>
            <span>Han Border</span>
          </div>
          {cells}
        </div>
      </div>
    </div>
  );
}
