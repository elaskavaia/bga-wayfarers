/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Wayfarers implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */

interface CustomPlayer extends Player {}

interface GameGuiInternal extends GameGui {
  addMoveToLog(log_id: number, move_id: number): void;
  originalAddMoveToLog?: (log_id: number, move_id: number) => void;
  log_to_move_id?: { [log_id: string]: number };
}

interface Token {
  key: string;
  location: string;
  state: number;
}

interface CustomGamedatas extends Gamedatas<CustomPlayer> {
  tokens: { [key: string]: Token };
  token_types: { [key: string]: any };
  counters: { [key: string]: { value: number } };
  lastTurn?: boolean;
  endScores?: any;
  aiEndScores?: any;
  gameEnded?: boolean;
  playerswithbots?: { [playerId: number]: any };
}
