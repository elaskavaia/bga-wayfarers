import { makeBga } from "./setup";
import { expect } from "chai";
import { Game } from "../Game";
import { LaAnimations } from "../LaAnimations";

/**
 * BGA #242371 - "Number of upgrades remaining didn't update".
 *
 * The upgrade tile tooltip ends with a "Remaining Tiles" line whose value is counted live from
 * gamedatas.tokens (Game.getRemainingUpgradeTileCount). The whole TokenDisplayInfo, tooltip HTML
 * included, is memoized in Game1Tokens.tokenInfoCache and registered with the framework once at
 * setup. When a tile left the supply, placeTokenServer force-refreshed only the moved token and
 * its destination, so every remaining copy in the stack kept the tooltip HTML built at setup and
 * still advertised the pre-move count until the page was reloaded.
 *
 * Fixed by Game.placeTokenServer, which force-refreshes every other copy of a moved upgrade tile.
 */
describe("Game upgrade tile remaining-count tooltip (BGA #242371)", () => {
  let game: Game;
  let registry: Record<string, string>;

  const TILE_TYPE = "upg_green_31";
  const COPIES = ["upg_green_31_1", "upg_green_31_2", "upg_green_31_3"];

  const remainingLine = (tokenId: string) => {
    const html = registry[tokenId] ?? "";
    const match = html.match(/<b>Remaining Tiles<\/b>: (\d+)/);
    return match ? match[1] : undefined;
  };

  beforeEach(() => {
    game = new Game(makeBga());
    game.animationLa = new LaAnimations(); // normally built by setupGame, which we bypass

    const tokens: Record<string, any> = {};
    for (const copy of COPIES) {
      tokens[copy] = { key: copy, location: "mainarea", state: 0 };
    }

    game.gamedatas = {
      players: { "1": { id: "1", name: "Alice", color: "ff0000" } as any },
      tokens,
      token_types: {
        upg_green: { name: "Basic Upgrade Tile" },
        [TILE_TYPE]: {
          type: "upg upg_green",
          w: 1,
          h: 1,
          num: 31,
          t: "green",
          vp: 1,
          name: "Basic Upgrade Camel",
          p: 4
        }
      },
      counters: {}
    } as any;

    registry = {};
    (global as any).gameui.tooltips = registry;
    (global as any).gameui.addTooltipHtml = (nodeId: string, html: string) => {
      registry[nodeId] = html;
    };

    // Supply stack as rendered by getPlaceRedirect: non-pink upgrades in "mainarea" land in mainboard_2.
    document.body.innerHTML = `
      <div id='limbo'></div>
      <div id='mainboard_2'>
        ${COPIES.map((copy) => `<div id='${copy}' class='upg upg_green' data-location='mainarea'></div>`).join("")}
      </div>
      <div id='tableau_ff0000'></div>`;

    // What setupTokens does: register a tooltip for every token once.
    for (const copy of COPIES) {
      game.updateTooltip(copy);
    }
  });

  afterEach(() => {
    (global as any).gameui.tooltips = {};
    (global as any).gameui.addTooltipHtml = () => {};
    document.body.innerHTML = "";
  });

  it("counts every copy still in the supply when the tooltip is first built", () => {
    expect(remainingLine("upg_green_31_1")).to.equal("3");
    expect(remainingLine("upg_green_31_2")).to.equal("3");
    expect(remainingLine("upg_green_31_3")).to.equal("3");
  });

  it("refreshes sibling tiles when one is taken (BGA #242371)", async () => {
    await game.notif_tokenMoved({ token_id: "upg_green_31_1", place_id: "tableau_ff0000", new_state: 0 });

    // The notification really was processed end to end: the node left the supply stack.
    expect(document.getElementById("upg_green_31_1")?.parentElement?.id).to.equal("tableau_ff0000");

    expect(game.getRemainingUpgradeTileCount("upg_green_31_2")).to.equal(2);

    // The whole stack agrees, not just the tile that moved.
    expect(remainingLine("upg_green_31_1")).to.equal("2");
    expect(remainingLine("upg_green_31_2")).to.equal("2");
    expect(remainingLine("upg_green_31_3")).to.equal("2");
  });
});
