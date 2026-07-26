import "./setup";
import { expect } from "chai";
import { Game } from "../Game";

/**
 * BGA #229079 - "Tooltip on placed upgrade tile is wrong".
 *
 * When an upgrade tile is placed into the caravan, its DOM node (`upg_*`) is
 * moved INTO the caravan cell node (`ccell_*`) as a child (see
 * Game.getPlaceRedirect -> result.location = `ccell_${pos}_${color}`, then
 * slideAndPlace appendChilds the tile into that cell).
 *
 * Both the cell and the tile register their own tooltip, keyed by node id:
 *   - cell:  makePlayerBoard registers "Caravan Cell" via game.addTooltipHtml(cellId, ...)
 *   - tile:  updateTooltip registers the tile's own tooltip on the tile node id
 *
 * BGA's tooltip framework delegates on mouseover, which bubbles from the
 * hovered child up to its ancestors; the outer (container) node's handler runs
 * last and wins, so the cell's tooltip is what the user actually sees when
 * hovering the tile. The cell tooltip is never cleared when a tile covers it.
 */
describe("BGA #229079 placed upgrade tile tooltip", () => {
  let game: Game;

  const CELL_ID = "ccell_5_ff0000";
  const TILE_ID = "upg_yellow_12";

  // Registry mirroring the real gameui.tooltips map (keyed by node id).
  let registry: Record<string, string>;

  beforeEach(() => {
    registry = {};
    (global as any).gameui.tooltips = registry;
    (global as any).gameui.addTooltipHtml = (nodeId: string, html: string) => {
      registry[nodeId] = html;
    };

    const bga: any = {
      statusBar: { setTitle: () => {} },
      states: { register: () => {} },
      notifications: { setup: () => {} },
      images: { preload: () => {} },
      sounds: { enable: () => {} },
      players: { getActivePlayerId: () => "1", getList: () => [] },
      actions: { callAction: () => {} },
      gameArea: { addArea: () => {} },
      playerPanels: { addPanel: () => {} },
      dialogs: {}
    };
    game = new Game(bga);
    game.gamedatas = { players: {}, tokens: {}, token_types: {}, counters: {} } as any;

    // Build the DOM exactly as the placed-tile case produces it: the tile node
    // is a child of the caravan cell, which itself holds the pre-printed icon.
    document.body.innerHTML = `
      <div id='caravan_ff0000' class='caravan'>
        <div id='${CELL_ID}' class='ccell'>
          <div class='wicon wicon_coin'></div>
          <div id='${TILE_ID}' class='upg upg_yellow'></div>
        </div>
      </div>`;
  });

  // Model of BGA's ancestor-precedence tooltip delegation: hovering a node,
  // mouseover bubbles up the parent chain and the OUTERMOST registered
  // ancestor-or-self wins (its handler fires last).
  function resolveHoverTooltip(nodeId: string): string | undefined {
    let node: HTMLElement | null = document.getElementById(nodeId);
    let winner: string | undefined = undefined;
    while (node) {
      if (node.id && registry[node.id] !== undefined) winner = registry[node.id];
      node = node.parentElement;
    }
    return winner;
  }

  it("documents buggy behavior: placed tile resolves to the cell's tooltip, not its own", () => {
    // 1. Cell registers "Caravan Cell" tooltip (as makePlayerBoard does).
    const cellTooltip = game.getTooltipHtml(_("Caravan Cell"), _("When placing upgrade that covers this cell:") + " coin");
    game.addTooltipHtml(CELL_ID, cellTooltip, game.defaultTooltipDelay);

    // 2. Tile registers its OWN tooltip through the real updateTooltip path.
    //    (Stub display-info so we don't need the full Material rule chain.)
    (game as any).getTokenDisplayInfo = (id: string) => ({
      tokenId: id,
      key: id,
      name: "Upgrade Tile #12",
      tooltip: "<p><b>Assets</b>: yellow upgrade</p>",
      imageTypes: "upg upg_yellow _nottimage",
      showtooltip: true
    });
    game.updateTooltip(TILE_ID);

    const tileTooltip = registry[TILE_ID];

    // Sanity: the tile really did register its own, distinct tooltip - so this
    // is a resolution/precedence defect, not a missing-registration one.
    expect(tileTooltip, "tile registered its own tooltip").to.be.a("string");
    expect(tileTooltip).to.contain("Upgrade Tile #12");
    expect(cellTooltip).to.contain("Caravan Cell");
    expect(tileTooltip).to.not.equal(cellTooltip);

    // Documents buggy behavior for BGA #229079; flip once fixed
    // (placed tile should show its own tooltip).
    const resolved = resolveHoverTooltip(TILE_ID);
    expect(resolved).to.equal(cellTooltip); // BUG: shows the background cell
    expect(resolved).to.not.equal(tileTooltip); // should be the tile's own once fixed
  });

  it("documents buggy behavior: cell tooltip is never cleared when a tile covers it", () => {
    const cellTooltip = game.getTooltipHtml(_("Caravan Cell"), _("bonus"));
    game.addTooltipHtml(CELL_ID, cellTooltip, game.defaultTooltipDelay);

    (game as any).getTokenDisplayInfo = (id: string) => ({
      tokenId: id,
      key: id,
      name: "Upgrade Tile #12",
      tooltip: "<p>tile</p>",
      imageTypes: "upg upg_yellow _nottimage",
      showtooltip: true
    });
    game.updateTooltip(TILE_ID);

    // A covered cell still keeps its own tooltip registered. Documents buggy
    // behavior for BGA #229079; flip once fixed (placed tile should show its
    // own tooltip) - the fix should clear/suppress the covered cell's tooltip.
    expect(registry[CELL_ID], "covered cell still has its tooltip").to.equal(cellTooltip);
  });
});
