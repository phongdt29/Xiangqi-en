const ROWS = 10;
const COLS = 9;

// row 0 = Black back rank (top of board), row 9 = Red back rank (bottom).
// River sits between row 4 and row 5. Palace = cols 3-5.
const PALACE_COLS = [3, 4, 5];
const BLACK_PALACE_ROWS = [0, 1, 2];
const RED_PALACE_ROWS = [7, 8, 9];

const PIECE_TYPES = {
  KING: 'K',
  ADVISOR: 'A',
  ELEPHANT: 'E',
  HORSE: 'H',
  CHARIOT: 'R',
  CANNON: 'C',
  SOLDIER: 'S',
};

const COLORS = { RED: 'red', BLACK: 'black' };

function inBounds(row, col) {
  return row >= 0 && row < ROWS && col >= 0 && col < COLS;
}

function createInitialBoard() {
  const board = Array.from({ length: ROWS }, () => Array(COLS).fill(null));

  const backRank = [
    PIECE_TYPES.CHARIOT,
    PIECE_TYPES.HORSE,
    PIECE_TYPES.ELEPHANT,
    PIECE_TYPES.ADVISOR,
    PIECE_TYPES.KING,
    PIECE_TYPES.ADVISOR,
    PIECE_TYPES.ELEPHANT,
    PIECE_TYPES.HORSE,
    PIECE_TYPES.CHARIOT,
  ];

  backRank.forEach((type, col) => {
    board[0][col] = { type, color: COLORS.BLACK };
    board[9][col] = { type, color: COLORS.RED };
  });

  [1, 7].forEach((col) => {
    board[2][col] = { type: PIECE_TYPES.CANNON, color: COLORS.BLACK };
    board[7][col] = { type: PIECE_TYPES.CANNON, color: COLORS.RED };
  });

  [0, 2, 4, 6, 8].forEach((col) => {
    board[3][col] = { type: PIECE_TYPES.SOLDIER, color: COLORS.BLACK };
    board[6][col] = { type: PIECE_TYPES.SOLDIER, color: COLORS.RED };
  });

  return board;
}

module.exports = {
  ROWS,
  COLS,
  PALACE_COLS,
  BLACK_PALACE_ROWS,
  RED_PALACE_ROWS,
  PIECE_TYPES,
  COLORS,
  inBounds,
  createInitialBoard,
};
