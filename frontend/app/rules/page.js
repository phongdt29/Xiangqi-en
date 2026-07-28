import Link from "next/link";
import { PIECE_GLYPHS, PIECE_NAMES } from "../lib/pieces";

export const metadata = {
  title: "Rules - Xiangqi Online",
};

const MOVEMENT = {
  general: "Moves one point orthogonally, and must stay inside its own palace (the 3x3 box).",
  advisor: "Moves one point diagonally, and must stay inside its own palace.",
  elephant:
    'Moves exactly two points diagonally ("flies" over the midpoint, called the eye). Cannot cross the river, and cannot move if the eye is occupied.',
  horse:
    'Moves in an L-shape (one point orthogonally, then one point diagonally outward). Cannot jump if the adjacent orthogonal point ("the horse\'s leg") is occupied.',
  chariot: "Moves any distance horizontally or vertically, like a rook in international chess. Cannot jump over pieces.",
  cannon:
    "Moves like the Chariot when not capturing. To capture, it must jump over exactly one piece (of either side) in a straight line and land on the enemy piece beyond it.",
  soldier:
    "Moves one point forward only, before crossing the river. After crossing the river, it may also move one point sideways (left or right), but never backward.",
};

const SPECIAL_RULES = [
  {
    emoji: "🚫",
    title: "Flying General",
    body: "The two Generals may never face each other directly on the same open column with no piece between them. A move that would create this position is illegal.",
  },
  {
    emoji: "💣",
    title: "Cannon captures need a screen",
    body: 'The Cannon can only capture by jumping over exactly one piece - friend or foe - anywhere between it and the target. It cannot capture without a "screen" piece in between, and cannot capture the screen piece itself.',
  },
  {
    emoji: "⚠️",
    title: "No draws by stalemate",
    body: "Unlike international chess, a player with no legal move who is not in check still loses the game - it is not a draw.",
  },
  {
    emoji: "🏁",
    title: "Checkmate ends the game",
    body: "A player who is in check with no legal move that escapes it is checkmated and loses immediately.",
  },
];

export default function RulesPage() {
  return (
    <div className="container py-5">
      <header className="text-center mb-5">
        <h1 className="fw-bold">📜 How to Play Xiangqi</h1>
        <p className="text-secondary">A quick guide to the board, the pieces, and the special rules.</p>
      </header>

      <section className="mb-5">
        <h2 className="h4">🀄 The Board</h2>
        <p>
          Xiangqi is played on a grid of 9 vertical lines and 10 horizontal lines - pieces sit on the{" "}
          <strong>intersections</strong>, not inside squares. A river runs across the middle of the board, and each
          side has a 3x3 <strong>palace</strong> at the back where the General and Advisors must stay.
        </p>
      </section>

      <section className="mb-5">
        <h2 className="h4">🎯 Objective</h2>
        <p>
          Checkmate the opponent&apos;s General: trap it so it has no legal move left to escape check. There are no
          draws by stalemate in Xiangqi - see the special rules below.
        </p>
      </section>

      <section className="mb-5">
        <h2 className="h4 mb-3">♟️ The Pieces</h2>
        <div className="table-responsive">
          <table className="table table-bordered align-middle">
            <thead className="table-light">
              <tr>
                <th style={{ width: "1%" }}>Red</th>
                <th style={{ width: "1%" }}>Black</th>
                <th style={{ width: "12%" }}>Name</th>
                <th>How it moves</th>
              </tr>
            </thead>
            <tbody>
              {Object.keys(PIECE_NAMES).map((type) => (
                <tr key={type}>
                  <td className="text-center">
                    <span className="xq-piece xq-piece-red" style={{ position: "static" }}>
                      {PIECE_GLYPHS.red[type]}
                    </span>
                  </td>
                  <td className="text-center">
                    <span className="xq-piece xq-piece-black" style={{ position: "static" }}>
                      {PIECE_GLYPHS.black[type]}
                    </span>
                  </td>
                  <td className="fw-semibold">{PIECE_NAMES[type]}</td>
                  <td>{MOVEMENT[type]}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      <section className="mb-5">
        <h2 className="h4 mb-3">✨ Special Rules</h2>
        <div className="row g-3">
          {SPECIAL_RULES.map((rule) => (
            <div className="col-12 col-md-6" key={rule.title}>
              <div className="card h-100">
                <div className="card-body">
                  <h3 className="h6 card-title">
                    <span aria-hidden="true">{rule.emoji}</span> {rule.title}
                  </h3>
                  <p className="card-text text-secondary mb-0">{rule.body}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </section>

      <div className="text-center">
        <Link href="/play" className="btn btn-primary btn-lg">
          🎮 Try it out
        </Link>
      </div>
    </div>
  );
}
