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
      (game as any).getTokenPresentaton = () => {
        throw new Error("boom");
      };
      const result = game.bgaFormatText("Got [broken]", {});
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
});
