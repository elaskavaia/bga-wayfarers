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
 * BGA's tooltip framework delegates on mouseover, which bubbles from the
 * hovered child up to its ancestors; the outer (container) node's handler runs
 * last and wins, so the cell's tooltip used to be what the user saw when
 * hovering the tile. Game.updateCaravanCellTooltips now drops the tooltip of a
 * covered cell (and restores it once the tile leaves).
 */
describe("BGA #229079 placed upgrade tile tooltip", () => {
  let game: Game;

  const CARAVAN_ID = "caravan_ff0000";
  const CELL_ID = "ccell_5_ff0000";
  const EMPTY_CELL_ID = "ccell_6_ff0000";
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
      <div id='${CARAVAN_ID}' class='caravan'>
        <div id='${CELL_ID}' class='ccell' data-r='coin'>
          <div class='wicon wicon_coin'></div>
          <div id='${TILE_ID}' class='upg upg_yellow'></div>
        </div>
        <div id='${EMPTY_CELL_ID}' class='ccell' data-r='food'></div>
      </div>`;

    // setupPlayer registers a tooltip on every bonus cell before any tile is placed.
    registry[CELL_ID] = "<div>Caravan Cell (registered before the tile arrived)</div>";
    registry[EMPTY_CELL_ID] = "<div>Caravan Cell (registered before the tile arrived)</div>";

    // The tile registers its own tooltip through the real updateTooltip path.
    // (Stub display-info so we don't need the full Material rule chain.)
    (game as any).getTokenDisplayInfo = (id: string) => ({
      tokenId: id,
      key: id,
      name: "Upgrade Tile #12",
      tooltip: "<p><b>Assets</b>: yellow upgrade</p>",
      imageTypes: "upg upg_yellow _nottimage",
      showtooltip: true
    });
    game.updateTooltip(TILE_ID);
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

  it("hovering a placed tile shows the tile's own tooltip, not the cell's", () => {
    game.updateCaravanCellTooltips(CARAVAN_ID);

    const tileTooltip = registry[TILE_ID];
    expect(tileTooltip, "tile registered its own tooltip").to.be.a("string");
    expect(tileTooltip).to.contain("Upgrade Tile #12");

    expect(resolveHoverTooltip(TILE_ID)).to.equal(tileTooltip);
  });

  it("a covered cell loses the tooltip it was registered with, an uncovered one keeps it", () => {
    game.updateCaravanCellTooltips(CARAVAN_ID);

    expect(registry[CELL_ID], "covered cell tooltip is dropped").to.be.undefined;
    expect(registry[EMPTY_CELL_ID], "uncovered cell keeps its tooltip").to.contain("registered before the tile arrived");
  });

  it("the cell tooltip comes back once the tile leaves", () => {
    game.updateCaravanCellTooltips(CARAVAN_ID);
    expect(registry[CELL_ID]).to.be.undefined;

    document.getElementById(TILE_ID)!.remove();
    game.updateCaravanCellTooltips(CARAVAN_ID);

    expect(registry[CELL_ID], "uncovered again").to.contain("Caravan Cell");
  });
});
