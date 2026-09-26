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

/**
 * Journal ink splotches: one hover div per connector, built from side A material, whose tooltip
 * reads the requirement of whichever board side is in play (data-state on the board div).
 */
describe("Game journal splotch tooltips", () => {
  let game: Game;
  let registry: Record<string, string>;

  const tag = (name: string, icon: string) => ({ type: `wicon_${icon}`, name });
  const payOp = (color: string) => ({
    type: `n_inf${color}`,
    name: `Pay ${color} Influence`,
    wicon: `wicon_inf_${color.toLowerCase()}_pay`
  });

  beforeEach(() => {
    game = new Game(makeBga());
    game.animationLa = new LaAnimations();
    game.gamedatas = {
      players: { "1": { id: "1", name: "Alice", color: "ff0000" } as any },
      tokens: {},
      token_types: {
        jconn_0_10_0: { location: "mainboard_1", r: "true", gw: 1 },
        jconn_0_10_1: { location: "mainboard_1", r: "true", gw: 1 },
        jconn_10_20_0: { location: "mainboard_1", r: "tag_City", gw: 2 },
        jconn_10_20_1: { location: "mainboard_1", r: "tag_card_folk", gw: 2 },
        jconn_67_87_0: { location: "mainboard_2", r: "tag_Stars", gw: 4 },
        jconn_67_87_1: { location: "mainboard_2", r: "max(tag_Sun,tag_Moon)", gw: 1 },
        jconn_40_50_0: { location: "mainboard_2", r: "Op(n_infBlack)" },
        jconn_40_50_1: { location: "mainboard_2", r: "Op(n_infBlue,n_infYellow)" },
        tag_City: tag("City", "city"),
        tag_card_folk: tag("Townsfolk Card", "card_folk"),
        tag_Stars: tag("Stars", "stars"),
        tag_Sun: tag("Sun", "sun"),
        tag_Moon: tag("Moon", "moon"),
        Op_n_infBlack: payOp("Black"),
        Op_n_infBlue: payOp("Blue"),
        Op_n_infYellow: payOp("Yellow")
      },
      counters: {}
    } as any;

    registry = {};
    (global as any).gameui.tooltips = registry;
    (global as any).gameui.addTooltipHtml = (nodeId: string, html: string) => {
      registry[nodeId] = html;
    };

    document.body.innerHTML = `
      <div id='limbo'></div>
      <div id='mainboard_1' data-state='0'></div>
      <div id='mainboard_2' data-state='1'></div>`;

    game.setupJournalConnectors();
  });

  afterEach(() => {
    (global as any).gameui.tooltips = {};
    (global as any).gameui.addTooltipHtml = () => {};
    document.body.innerHTML = "";
  });

  const text = (id: string) => (registry[id] ?? "").replace(/<[^>]+>/g, " ").replace(/\s+/g, " ");

  it("creates a div per splotch on its board, skipping the free starting paths", () => {
    expect(document.getElementById("jconn_0_10")).to.equal(null);
    expect(document.getElementById("jconn_10_20")?.parentElement?.id).to.equal("mainboard_1");
    expect(document.getElementById("jconn_67_87")?.parentElement?.id).to.equal("mainboard_2");
    expect(document.getElementById("jconn_10_20")?.classList.contains("withtooltip")).to.equal(true);
  });

  it("shows the tag count, icon and name for side A", () => {
    expect(text("jconn_10_20")).to.include("2 City");
    expect(registry["jconn_10_20"]).to.include("wicon_city");
    expect(text("jconn_10_20")).to.not.include("Townsfolk");
  });

  it("reads side B requirements when the board is flipped", () => {
    expect(text("jconn_67_87")).to.include("1 Sun or Moon");
    expect(text("jconn_67_87")).to.not.include("Stars");
  });

  it("names every influence to pay for an Op requirement", () => {
    expect(text("jconn_40_50")).to.include("Pay Blue Influence + Pay Yellow Influence");
    expect(registry["jconn_40_50"]).to.include("wicon_inf_blue_pay");
  });
});
