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

const BgaAnimations = (await globalThis.importEsmLib("bga-animations", "1.x")) as { Manager: typeof AnimationManager };
const BgaScoreSheet = (await globalThis.importEsmLib("bga-score-sheet", "1.x")) as { ScoreSheet: typeof ScoreSheet };

export { BgaAnimations, BgaScoreSheet };
