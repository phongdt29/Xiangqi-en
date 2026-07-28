import { PIECE_GLYPHS } from "../lib/pieces";

function formatMove(move) {
  const glyph = PIECE_GLYPHS[move.piece.side][move.piece.type];
  const from = `${move.from.x + 1},${move.from.y + 1}`;
  const to = `${move.to.x + 1},${move.to.y + 1}`;
  const capture = move.captured ? "x" : "-";
  return `${glyph} ${from}${capture}${to}`;
}

export default function MoveHistoryList({ moves }) {
  if (!moves || moves.length === 0) {
    return <p className="text-secondary small mb-0">No moves yet.</p>;
  }

  const pairs = [];
  for (let i = 0; i < moves.length; i += 2) {
    pairs.push({ number: i / 2 + 1, red: moves[i], black: moves[i + 1] });
  }

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
              <td>{formatMove(p.red)}</td>
              <td>{p.black ? formatMove(p.black) : ""}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
