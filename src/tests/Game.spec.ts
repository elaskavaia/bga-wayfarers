import "./setup";
import { expect } from "chai";
import sinon from "sinon";
import { Game } from "../Game";

describe("Game", () => {
  let game: Game;
  let bga: any;

  beforeEach(() => {
    bga = {
      statusBar: { setTitle: sinon.stub() },
      states: { register: sinon.stub() },
      notifications: { setup: sinon.stub() },
      images: { preload: sinon.stub() },
      sounds: { enable: sinon.stub() },
      players: {
        getActivePlayerId: () => "1",
        getList: () => []
      },
      actions: { callAction: sinon.stub() },
      gameArea: { addArea: sinon.stub() },
      playerPanels: { addPanel: sinon.stub() },
      dialogs: {}
    };
    game = new Game(bga);
    game.gamedatas = {
      players: {
        "1": { id: "1", name: "Alice", color: "ff0000" } as any,
        "2": { id: "2", name: "Bob", color: "0000ff" } as any
      },
      tokens: {},
      token_types: {},
      counters: {}
    } as any;
  });

  describe("bgaFormatText", () => {
    it("returns empty log for falsy input", () => {
      const result = game.bgaFormatText("", {});
      expect(result.log).to.equal("");
    });

    it("returns empty log for null input", () => {
      const result = game.bgaFormatText(null as any, {});
      expect(result.log).to.equal("");
    });

    it("returns empty log for undefined input", () => {
      const result = game.bgaFormatText(undefined as any, {});
      expect(result.log).to.equal("");
    });

    it("handles non-string log with .log property (NotificationMessage)", () => {
      const notifMsg = { log: "hello ${player_name}", args: { player_id: "1" } };
      // bgaFormatText should recurse into notifMsg.log
      const result = game.bgaFormatText(notifMsg as any, {});
      expect(result.log).to.equal("hello ${player_name}");
    });

    it("handles non-string log without .log property", () => {
      const result = game.bgaFormatText({ foo: "bar" } as any, {});
      expect(result.log).to.equal("?");
    });

    it("passes through plain text without modification", () => {
      const result = game.bgaFormatText("simple text", {});
      expect(result.log).to.equal("simple text");
    });

    it("replaces square bracket tokens with presentation", () => {
      // Stub getTokenPresentaton to return a known value
      (game as any).getTokenPresentaton = (_key: string, tokenKey: string) => `<div>${tokenKey}</div>`;
      const args: any = {};
      const result = game.bgaFormatText("You gained [coin]", args);
      expect(result.log).to.equal("You gained <div>coin</div>");
    });

    it("replaces multiple square bracket tokens", () => {
      (game as any).getTokenPresentaton = (_key: string, tokenKey: string) => `<i>${tokenKey}</i>`;
      const result = game.bgaFormatText("Got [coin] and [food]", {});
      expect(result.log).to.equal("Got <i>coin</i> and <i>food</i>");
    });

    it("keeps original text if getTokenPresentaton returns null", () => {
      (game as any).getTokenPresentaton = () => null;
      const result = game.bgaFormatText("Got [unknown]", {});
      expect(result.log).to.equal("Got [unknown]");
    });

    it("keeps original text if getTokenPresentaton throws", () => {
      const origError = console.error;
      console.error = () => {};
      (game as any).getTokenPresentaton = () => {
        throw new Error("boom");
      };
      const result = game.bgaFormatText("Got [broken]", {});
      console.error = origError;
      expect(result.log).to.equal("Got [broken]");
    });

    it("sets player_name from active player when player_id missing and $ in log", () => {
      (game as any).getTokenPresentaton = () => null;
      bga.players.getActivePlayerId = () => "2";
      const args: any = {};
      const result = game.bgaFormatText("${player_name} did something", args);
      expect(result.args.player_id).to.equal("2");
      expect(result.args.player_name).to.equal("Bob");
    });

    it("sets AI player name for automa player_id", () => {
      const args: any = { player_id: 1 };
      const result = game.bgaFormatText("${player_name} did something", args);
      expect(result.args.player_name).to.include("Aida");
      expect(result.args.player_name).to.include("982fff");
    });

    it("sets You args from divYou", () => {
      (game as any).divYou = () => "<span>You</span>";
      const args: any = { you: "you" };
      const result = game.bgaFormatText("${you} gained ${You}", args);
      expect(result.args.you).to.equal("<span>You</span>");
      expect(result.args.You).to.equal("<span>You</span>");
    });

    it("processes reason arg through getTokenName", () => {
      (game as any).getTokenName = (id: string) => `Name of ${id}`;
      const args: any = { reason: "some_token" };
      const result = game.bgaFormatText("$did thing ${reason}", args);
      expect(result.args.reason).to.equal("(Name of some_token)");
    });

    it("resolves player_name and bracket icons together", () => {
      (game as any).getTokenPresentaton = (_key: string, tokenKey: string) => `<i>${tokenKey}</i>`;
      const args: any = { player_id: "2" };
      const result = game.bgaFormatText("${player_name} gains [wicon_coin]", args);
      expect(result.args.player_name).to.equal("Bob");
      expect(result.log).to.include("<i>wicon_coin</i>");
      expect(result.log).not.to.include("[wicon_coin]");
    });
  });

  describe("onEnteringState_PlayerTurn", () => {
    const findCall = (label: string) => bga.statusBar.addActionButton.getCalls().find((c: any) => c.args[0] === label);
    let consoleLogStub: sinon.SinonStub;

    beforeEach(() => {
      consoleLogStub = sinon.stub(console, "log");
      bga.players.isCurrentPlayerActive = () => true;
      bga.players.isCurrentPlayerSpectator = () => false;
      // Return a real div so the helpers can set classes/title without crashing.
      bga.statusBar.addActionButton = sinon.stub().callsFake(() => document.createElement("div"));
      document.body.innerHTML = "";
    });

    afterEach(() => {
      consoleLogStub.restore();
    });

    describe("inactive player", () => {
      beforeEach(() => {
        bga.players.isCurrentPlayerActive = () => false;
      });

      it("does not set title when no description", () => {
        game.onEnteringState_PlayerTurn({} as any);
        expect(bga.statusBar.setTitle.notCalled).to.be.true;
      });

      it("sets title from description", () => {
        game.onEnteringState_PlayerTurn({ description: "Waiting for Bob" } as any);
        expect(bga.statusBar.setTitle.calledWith("Waiting for Bob")).to.be.true;
      });

      it("does not add undo button by default", () => {
        game.onEnteringState_PlayerTurn({} as any);
        expect(findCall("Undo")).to.be.undefined;
      });

      it("adds undo button when ui.undo is true", () => {
        game.onEnteringState_PlayerTurn({ ui: { undo: true } } as any);
        expect(findCall("Undo")).to.not.be.undefined;
      });

      it("returns early — does not touch active class on target divs", () => {
        document.body.innerHTML = `<div id='t1'></div>`;
        game.onEnteringState_PlayerTurn({ info: { t1: { q: 0 } }, target: ["t1"] } as any);
        expect(document.getElementById("t1")!.classList.contains("active_slot")).to.be.false;
      });
    });

    describe("title from prompt / err / reason", () => {
      it("does not set title without prompt", () => {
        game.onEnteringState_PlayerTurn({ info: {} } as any);
        expect(bga.statusBar.setTitle.notCalled).to.be.true;
      });

      it("sets title from prompt alone", () => {
        game.onEnteringState_PlayerTurn({ prompt: "Pick a card", info: {} } as any);
        expect(bga.statusBar.setTitle.calledWith("Pick a card")).to.be.true;
      });

      it("prefixes prompt with err", () => {
        game.onEnteringState_PlayerTurn({ prompt: "Pick", err: "Bad choice", info: {} } as any);
        expect(bga.statusBar.setTitle.calledWith("[Error: Bad choice] Pick")).to.be.true;
      });

      it("err takes precedence over data.reason", () => {
        game.onEnteringState_PlayerTurn({
          prompt: "Pick",
          err: "oops",
          data: { reason: "would-be-reason" },
          info: {}
        } as any);
        const arg = bga.statusBar.setTitle.getCall(0).args[0];
        expect(arg).to.include("Error: oops");
        expect(arg).not.to.include("would-be-reason");
      });

      it("does not call setTitle if neither prompt nor (sub-only) is set", () => {
        // subprompt without prompt should not set title (current code path).
        game.onEnteringState_PlayerTurn({ err: "lonely err", info: {} } as any);
        expect(bga.statusBar.setTitle.notCalled).to.be.true;
      });
    });

    describe("active slot styling", () => {
      it("adds active_slot class to target div when q=0", () => {
        document.body.innerHTML = `<div id='t1'></div>`;
        game.onEnteringState_PlayerTurn({ info: { t1: { q: 0 } }, target: ["t1"] } as any);
        expect(document.getElementById("t1")!.classList.contains("active_slot")).to.be.true;
      });

      it("skips active_slot when paramInfo.noactive is true", () => {
        document.body.innerHTML = `<div id='t1'></div>`;
        game.onEnteringState_PlayerTurn({ info: { t1: { q: 0, noactive: true } }, target: ["t1"] } as any);
        expect(document.getElementById("t1")!.classList.contains("active_slot")).to.be.false;
      });

      it("skips active_slot when ui.noactive is true", () => {
        document.body.innerHTML = `<div id='t1'></div>`;
        game.onEnteringState_PlayerTurn({
          info: { t1: { q: 0 } },
          target: ["t1"],
          ui: { noactive: true }
        } as any);
        expect(document.getElementById("t1")!.classList.contains("active_slot")).to.be.false;
      });

      it("skips active_slot for inactive target (q != 0)", () => {
        document.body.innerHTML = `<div id='t1'></div>`;
        game.onEnteringState_PlayerTurn({ info: { t1: { q: 2 } } } as any);
        expect(document.getElementById("t1")!.classList.contains("active_slot")).to.be.false;
      });

      it("tags the active div with targetOpType", () => {
        document.body.innerHTML = `<div id='t1'></div>`;
        game.onEnteringState_PlayerTurn({ type: "pickWorker", info: { t1: { q: 0 } } } as any);
        expect(document.getElementById("t1")!.dataset.targetOpType).to.equal("pickWorker");
      });
    });

    describe("buttons", () => {
      it("creates a default button for active target", () => {
        // No target div in DOM → button is the only way to interact.
        game.onEnteringState_PlayerTurn({ info: { lone_target: { q: 0 } } } as any);
        const calls = bga.statusBar.addActionButton.getCalls();
        const buttonCall = calls.find((c: any) => c.args[2]?.id === "button_lone_target");
        expect(buttonCall).to.not.be.undefined;
      });

      it("does not create a button when paramInfo.buttons === false", () => {
        document.body.innerHTML = `<div id='t1'></div>`;
        game.onEnteringState_PlayerTurn({ info: { t1: { q: 0, buttons: false } }, target: ["t1"] } as any);
        const calls = bga.statusBar.addActionButton.getCalls();
        const buttonCall = calls.find((c: any) => c.args[2]?.id === "button_t1");
        expect(buttonCall).to.be.undefined;
      });

      it("disabled style is added for inactive (q != 0) buttons", () => {
        const stub = bga.statusBar.addActionButton as sinon.SinonStub;
        const el = document.createElement("div");
        stub.callsFake(() => el);
        game.onEnteringState_PlayerTurn({ info: { t1: { q: 2 } }, target: ["t1"] } as any);
        expect(el.classList.contains("disabled")).to.be.true;
      });

      it("paramInfo.color overrides opInfo.ui.color", () => {
        // completeOpInfo will set ui.color to 'primary' by default; per-target color must win.
        game.onEnteringState_PlayerTurn({
          info: { t1: { q: 0, color: "secondary" } },
          target: ["t1"]
        } as any);
        const targetCall = bga.statusBar.addActionButton.getCalls().find((c: any) => c.args[2]?.id === "button_t1");
        expect(targetCall.args[2].color).to.equal("secondary");
      });
    });

    describe("secondary buttons", () => {
      it("renders a secondary button in the second loop, not in the main one", () => {
        game.onEnteringState_PlayerTurn({ info: { sec1: { q: 0, sec: true } }, target: ["sec1"] } as any);
        const calls = bga.statusBar.addActionButton.getCalls();
        const secCall = calls.find((c: any) => c.args[2]?.id === "button_sec1");
        expect(secCall).to.not.be.undefined;
      });

      it("skips secondary buttons when buttons === false", () => {
        game.onEnteringState_PlayerTurn({
          info: { sec1: { q: 0, sec: true, buttons: false } },
          target: ["sec1"]
        } as any);
        const calls = bga.statusBar.addActionButton.getCalls();
        const secCall = calls.find((c: any) => c.args[2]?.id === "button_sec1");
        expect(secCall).to.be.undefined;
      });
    });

    describe("selected highlights", () => {
      it("adds gg_selected class to listed divs", () => {
        document.body.innerHTML = `<div id='hl1'></div><div id='hl2'></div>`;
        game.onEnteringState_PlayerTurn({
          info: {},
          type: "myop",
          ui: { selected: ["hl1", "hl2"] }
        } as any);
        expect(document.getElementById("hl1")!.classList.contains("gg_selected")).to.be.true;
        expect(document.getElementById("hl2")!.classList.contains("gg_selected")).to.be.true;
      });

      it("tolerates missing target divs in selected list", () => {
        // Should not throw.
        expect(() => game.onEnteringState_PlayerTurn({ info: {}, ui: { selected: ["does_not_exist"] } } as any)).to.not.throw();
      });
    });

    describe("subtitle / info button", () => {
      it("adds Info button when subtitle is set", () => {
        game.onEnteringState_PlayerTurn({ info: {}, subtitle: "Hint text" } as any);
        expect(findCall("Info")).to.not.be.undefined;
      });

      it("does not add Info button without subtitle", () => {
        game.onEnteringState_PlayerTurn({ info: {} } as any);
        expect(findCall("Info")).to.be.undefined;
      });
    });

    describe("undo button (active player)", () => {
      it("adds Undo button by default", () => {
        game.onEnteringState_PlayerTurn({ info: {} } as any);
        expect(findCall("Undo")).to.not.be.undefined;
      });

      it("suppresses Undo button when ui.undo is false", () => {
        game.onEnteringState_PlayerTurn({ info: {}, ui: { undo: false } } as any);
        expect(findCall("Undo")).to.be.undefined;
      });
    });

    describe("target order", () => {
      it("sorts targets by ascending `o` priority", () => {
        const order: string[] = [];
        (bga.statusBar.addActionButton as sinon.SinonStub).callsFake((_label: any, _cb: any, opts: any) => {
          const id = opts?.id ?? "";
          if (id.startsWith("button_") && !["button_undo", "button_showme", "button_info"].includes(id)) {
            order.push(id.replace("button_", ""));
          }
          return document.createElement("div");
        });
        game.onEnteringState_PlayerTurn({
          info: { a: { q: 0, o: 3 }, b: { q: 0, o: 1 }, c: { q: 0, o: 2 } },
          target: ["a", "b", "c"]
        } as any);
        expect(order).to.deep.equal(["b", "c", "a"]);
      });
    });
  });

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
  describe("updateCaravanCellTooltips", () => {
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

    afterEach(() => {
      (global as any).gameui.tooltips = {};
      (global as any).gameui.addTooltipHtml = () => {};
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
});
