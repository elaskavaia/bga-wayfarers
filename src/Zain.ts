/**
 * This is only code that has to use dojo
 * Note: this only works when targeting ES5
 */
define([
  "dojo",
  "dojo/_base/declare",
  "ebg/core/gamegui",
  // libs
  getLibUrl("bga-animations", "1.x"),
  getLibUrl("bga-cards", "1.x"),
  getLibUrl("bga-score-sheet", "1.x")
], function (dojo, declare, gamegui, BgaAnimations, BgaCards, BgaScoreSheet) {
  (window as any).BgaAnimations = BgaAnimations; //trick
  (window as any).BgaCards = BgaCards;
  (window as any).BgaScoreSheet = BgaScoreSheet;

  declare("bgagame.wayfarers", ebg.core.gamegui, new GameXBody());
});
