import { PIECE_GLYPHS } from "../lib/pieces";

function formatMove(move) {
  const glyph = PIECE_GLYPHS[move.piece.side][move.piece.type];
  const from = `${move.from.x + 1},${move.from.y + 1}`;
  const to = `${move.to.x + 1},${move.to.y + 1}`;
  const capture = move.captured ? "x" : "-";
  return `${glyph} ${from}${capture}${to}`;
}

/**
 * `onSelectMove(plyIndex)` is optional - pass it to let the user click a
 * move to preview the board as of that point (used by /play and puzzles,
 * not /rooms, where reviewing history mid-match doesn't make sense).
 */
export default function MoveHistoryList({ moves, onSelectMove, currentPly }) {
  if (!moves || moves.length === 0) {
    return <p className="text-secondary small mb-0">No moves yet.</p>;
  }

  const pairs = [];
  for (let i = 0; i < moves.length; i += 2) {
    pairs.push({ number: i / 2 + 1, redPly: i, red: moves[i], blackPly: i + 1, black: moves[i + 1] });
  }

  const cellProps = (ply) =>
    onSelectMove
      ? {
          role: "button",
          tabIndex: 0,
          className: currentPly === ply ? "fw-bold text-primary" : "",
          onClick: () => onSelectMove(ply),
          onKeyDown: (e) => e.key === "Enter" && onSelectMove(ply),
        }
      : {};

  return (
    <div className="table-responsive" style={{ maxHeight: 260, overflowY: "auto" }}>
      <table className="table table-sm mb-0">
        <thead>
          <tr>
            <th style={{ width: "15%" }}>#</th>
            <th>Red</th>
            <th>Black</th>
          </tr>
        </thead>
        <tbody>
          {pairs.map((p) => (
            <tr key={p.number}>
              <td className="text-secondary">{p.number}</td>
              <td {...cellProps(p.redPly)}>{formatMove(p.red)}</td>
              <td {...(p.black ? cellProps(p.blackPly) : {})}>{p.black ? formatMove(p.black) : ""}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
