import Link from "next/link";

export default function Footer() {
  return (
    <footer className="bg-dark text-white-50 mt-auto py-4">
      <div className="container">
        <div className="row gy-3">
          <div className="col-12 col-md-4">
            <h2 className="h6 text-white">象棋 Xiangqi Online</h2>
            <p className="small mb-0">
              Play Xiangqi (Chinese Chess) in your browser - learn the rules, play a friend locally, or challenge
              someone online in real time.
            </p>
          </div>
          <div className="col-6 col-md-4">
            <h3 className="h6 text-white">Play</h3>
            <ul className="list-unstyled small">
              <li>
                <Link className="link-light link-opacity-75" href="/play">
                  Local Game
                </Link>
              </li>
              <li>
                <Link className="link-light link-opacity-75" href="/rooms">
                  Online Match
                </Link>
              </li>
              <li>
                <Link className="link-light link-opacity-75" href="/puzzles">
                  Puzzles
                </Link>
              </li>
              <li>
                <Link className="link-light link-opacity-75" href="/hidden-pieces">
                  Hidden Pieces
                </Link>
              </li>
              <li>
                <Link className="link-light link-opacity-75" href="/rules">
                  Rules
                </Link>
              </li>
              <li>
                <Link className="link-light link-opacity-75" href="/leaderboard">
                  Leaderboard
                </Link>
              </li>
            </ul>
          </div>
          <div className="col-6 col-md-4">
            <h3 className="h6 text-white">Account</h3>
            <ul className="list-unstyled small">
              <li>
                <Link className="link-light link-opacity-75" href="/profile">
                  Profile
                </Link>
              </li>
              <li>
                <Link className="link-light link-opacity-75" href="/login">
                  Login
                </Link>
              </li>
              <li>
                <Link className="link-light link-opacity-75" href="/register">
                  Sign Up
                </Link>
              </li>
            </ul>
          </div>
        </div>
        <hr className="border-secondary" />
        <p className="small text-center mb-0">&copy; {new Date().getFullYear()} Xiangqi Online. A hobby project.</p>
      </div>
    </footer>
  );
}
