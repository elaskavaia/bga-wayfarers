/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * GalacticCruise implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */

/** Game class. Its Call XBody to be last in alphabetical order */
class GameXBody extends GameMachine {
  private scoreSheet: any;
  private inSetup = true;
  readonly gameTemplate = `
<div id="thething">

<div id="round_banner">
</div>
<div id='selection_area' class='selection_area'></div>
<div id="game-score-sheet"></div>
<div id="current_player_panel"></div>
<div id="mainarea">
 <div id="mainboard" class="mainboard"></div>
 <div id="deck_folk" class="deck decl_folk"></div>
 <div id="deck_space" class="deck decl_space"></div>
 <div id="deck_land" class="deck deck_land"></div>
 <div id="deck_water" class="deck deck_water"></div>
 <div id="deck_insp" class="deck deck_insp"></div>
</div>
<div id="players_panels"></div>
<div id="supply">


</div>



`;
  setup(gamedatas) {
    try {
      super.setup(gamedatas);

      placeHtml(this.gameTemplate, this.bga.gameArea.getElement());
      // Setting up player boards
      for (const playerId of gamedatas.playerorder) {
        const playerInfo = gamedatas.players[playerId];
        this.setupPlayer(playerInfo);
      }

      super.setupGame(gamedatas);

      this.setupNotifications();
      this.setupScoreSheet();
      this.updateBanner();

      // document.rootElement?.classList.add("bgaext_cust_back");
    } catch (e) {
      console.error("Exception during game setup", e.stack);
    }

    console.log("Ending game setup");
    this.inSetup = false;
  }
  updateBanner() {}
  setupPlayer(playerInfo: any) {
    console.log("player info " + playerInfo.id, playerInfo);
    const pcolor = playerInfo.color;
    const pp = `player_panel_content_${pcolor}`;
    document.querySelectorAll(`#${pp}>.miniboard`).forEach((node) => node.remove());

    placeHtml(
      `<div id='miniboard_${pcolor}' class='miniboard'>
      </div>`,
      pp
    );
    let parent = this.player_color == pcolor ? "current_player_panel" : "players_panels";
    placeHtml(
      `
      <div id='tableau_${pcolor}' class='tableau' data-player-name='${playerInfo.name}' style='--player-color: #${pcolor}'>
      </div>`,
      parent
    );
  }

  setupScoreSheet() {
    // this.gamedatas.endScores = {};
    // this.gamedatas.endScores[this.player_id] = {
    //   game_vp_card_count: 5,
    //   game_vp_card_sets: 8,
    //   game_vp_trade: 3,
    //   game_vp_action_tiles: 4,
    //   game_vp_cards: 6,
    //   game_vp_food: 2,
    //   game_vp_skaill: 3,
    //   game_vp_midden: -2,
    //   game_vp_slider: -1,
    //   game_vp_tasks: -3,
    //   game_vp_goals: -1,
    //   total: 24
    // };
    const entries = [
      { property: "game_vp_card_sets", label: _("VP for card sets") },
      { property: "game_vp_space_cards", label: _("VP for space cards and inspiration") },
      { property: "game_vp_caravan", label: _("VP from caraval") },
      { property: "game_vp_guilds", label: _("VP from guild majorities") },
      { property: "total", label: _("Total"), scoresClasses: "total", width: 80, height: 40 }
    ];
    if (!this.isSolo()) {
      entries.splice(9, 2);
    }
    this.scoreSheet = new BgaScoreSheet.ScoreSheet(document.getElementById(`game-score-sheet`), {
      animationsActive: () => this.gameAnimationsActive(),
      playerNameWidth: 80,
      playerNameHeight: 30,
      entryLabelWidth: 180,
      entryLabelHeight: 20,
      classes: "score-sheet",
      players: this.gamedatas.players,
      entries,
      scores: this.gamedatas.endScores,
      onScoreDisplayed: (property, playerId, score) => {
        // if (property === "total") {
        //   gameui.scoreCtrl[playerId].setValue(score);
        // }
      }
    });
  }
  onUpdateActionButtons_MultiPlayerTurnPrivate(opInfo: OpInfo) {
    // this.onEnteringState_PlayerTurn(opInfo);
    //console.log("onUpdateActionButtons_MultiPlayerTurnPrivate", opInfo);
  }
  onEnteringState_MultiPlayerTurnPrivate(opInfo: OpInfo) {
    this.onEnteringState_PlayerTurn(opInfo);
  }

  onEnteringState_MultiPlayerMaster(opInfo: OpInfo) {
    this.onEnteringState_PlayerTurn(opInfo);
  }
  onEnteringState_PlayerTurn(opInfo: OpInfo) {
    super.onEnteringState_PlayerTurn(opInfo);
    switch (opInfo.type) {
      case "turn":
        // $("selection_area").insertAdjacentElement("afterend", $("mainarea"));

        const firstTarget = document.querySelector("." + this.classActiveSlot);
        if (!firstTarget) return;
        $(firstTarget).scrollIntoView({
          behavior: "smooth",
          block: "nearest" // Scrolls the minimum amount to bring the element into view vertically
        });
        break;
      case "act":
        //if ((opInfo as any).turn == 3) this.bga.gameArea.addLastTurnBanner(_("This is the last turn before you need to feed the settlers"));
        break;
    }
  }

  onLeavingState(stateName: string): void {
    super.onLeavingState(stateName);
    const opInfo = this.opInfo;

    if (opInfo?.ui?.replicate) {
      $("selection_area")
        .querySelectorAll("& > *")
        .forEach((element) => {
          element.remove();
        });
    }
  }

  showHelp(id: string) {
    return false;
  }

  hideCard(tokenId: ElementOrId) {
    $("limbo")?.appendChild($(tokenId));
  }

  getPlaceRedirect(tokenInfo: Token, args: AnimArgs = {}): TokenMoveInfo {
    const location = tokenInfo.location ?? "limbo";
    const tokenId = tokenInfo.key;
    const result: TokenMoveInfo = {
      location: location,
      key: tokenId,
      state: tokenInfo.state
    };
    if (args.place_from) result.place_from = args.place_from;
    if (args.inc) result.inc = args.inc;
    if (!this.gameAnimationsActive()) {
      result.animtime = 0;
    }

    if (tokenId.startsWith("card")) {
      // cards
      result.onClick = (x) => this.onToken(x);
      if (tokenId.startsWith("card_card") && location.startsWith("tableau")) {
        const color = getPart(location, 1);
        const t = this.getRulesFor(tokenId, "t");
        result.location = `settlers_col_${color}_${t}`;
        result.onEnd = () => {
          const counter = $(`counter_card_${color}`);
          const count = $(location).querySelectorAll(".card.card").length;
          counter.dataset.state = `${count}`;

          // sort
          const parentNode = $(result.location);
          const children = Array.from(parentNode.children);
          children.sort((a: HTMLElement, b: HTMLElement) => Number(a.dataset.state) - Number(b.dataset.state));
          children.forEach((node: HTMLElement) => {
            parentNode.appendChild(node);
          });
        };
      } else if (location.startsWith("hand")) {
        const color = getPart(location, 1);
        if (color != this.player_color) result.nop = true;
        else {
          result.location = `selection_area`;
          result.onClick = (x) => this.onToken(x);
        }
      } else if (tokenId.startsWith("card") && location.startsWith("tableau")) {
        const color = getPart(location, 1);
        result.location = `cards_area_${color}`;
        const mid = getPart(tokenId, 1);
        if (mid.startsWith("roof")) {
          result.onEnd = () => {
            const counter = $(`counter_roof_${color}`);
            const count = $(location).querySelectorAll(".card.roof,.card.roofi").length;
            counter.dataset.state = `${count}`;
          };
        }
      } else if (location.startsWith("discard")) {
        result.onEnd = (node) => this.hideCard(node);
      } else if (location.startsWith("deck")) {
        result.onEnd = (node) => this.hideCard(node);
      }
    } else if (tokenId.startsWith("tableau")) {
      result.nop = true;
    } else if (tokenId.startsWith("hand")) {
      result.nop = true;
    } else if (tokenId.startsWith("deck") || tokenId.startsWith("discard")) {
      result.nop = true;
    } else if (tokenId.startsWith("slot") || tokenId == "round_banner") {
      result.nop = true; // do not move slots
    } else if (tokenId.startsWith("tracker")) {
      result.nop = true;
    } else if (location.startsWith("miniboard") && $(tokenId)) {
      result.nop = true; // do not move
    } else if (tokenId.startsWith("worker") && location.startsWith("tableau")) {
      const color = getPart(location, 1);
      //result.location = `breakroom_${color}`;
    }
    return result;
  }

  gameAnimationsActive() {
    return gameui.bgaAnimationsActive() && !this.inSetup;
  }

  updateTokenDisplayInfo(tokenInfo: TokenDisplayInfo) {
    // override to generate dynamic tooltips and such
    const mainType = tokenInfo.mainType;
    const token = $(tokenInfo.tokenId);
    const parentId = token?.parentElement?.id;
    const state = parseInt(token?.dataset.state);
    switch (mainType) {
      case "worker":
        {
          const tokenId = tokenInfo.key;
          const name = tokenInfo.name;
          tokenInfo.tooltip = {
            log: "${name} (${color_name})",
            args: {
              name: this.getTr(name),
              color_name: this.getTr(this.getColorName(getPart(tokenId, 2)))
            }
          };
        }
        return;
    }
  }

  ttSection(prefix: string, text: string) {
    if (prefix) return `<p><b>${prefix}</b>: ${text}</p>`;
    else return `<p>${text}</p>`;
  }

  getColorName(color: string) {
    switch (color) {
      case "ff0000":
        return _("Red");
      case "ffcc02":
        return _("Yellow");
      case "982fff":
        return _("Purple");
      case "6cd0f6":
        return _("Blue");
      default:
        return _("Black");
    }
  }

  setupNotifications() {
    console.log("notifications subscriptions setup");

    // automatically listen to the notifications, based on the `notif_xxx` function on this class.
    this.bga.notifications.setupPromiseNotifications({
      minDuration: 1,
      minDurationNoText: 1,

      logger: console.log, // show notif debug informations on console. Could be console.warn or any custom debug function (default null = no logs)
      //handlers: [this, this.tokens],
      onStart: (notifName, msg, args) => {
        if (msg) this.setSubPrompt(msg, args);
      }
      // onEnd: (notifName, msg, args) => this.setSubPrompt("", args)
    });
  }
  async notif_message(args: any) {
    //console.log("notif", args);
    return this.wait(1);
  }

  async notif_undoMove(args: any) {
    console.log("notif", args);
    return this.wait(1);
  }

  async notif_endScores(args: any) {
    // setting scores will make the score sheet visible if it isn't already
    if (args.final) {
      $("round_banner").innerHTML = _("Game Over");
    }
    await this.scoreSheet.setScores(args.endScores, {
      startBy: this.bga.players.getCurrentPlayerId()
    });
  }
  /** @Override */
  bgaFormatText(log: string, args: any) {
    try {
      if (log && args && !args.processed) {
        args.processed = true;

        if (!args.player_id) {
          args.player_id = this.bga.players.getActivePlayerId();
        }
        if (args.player_id && !args.player_name) {
          args.player_name = this.gamedatas.players[args.player_id].name;
        }

        if (args.you) args.you = this.divYou(); // will replace ${you} with colored version
        args.You = this.divYou(); // will replace ${You} with colored version

        if (args.reason) {
          args.reason = "(" + this.getTokenName(args.reason) + ")";
        }
        const res = super.bgaFormatText(log, args);
        log = res.log;
        args = res.args;
      }
    } catch (e) {
      console.error(log, args, "Exception thrown", e.stack);
    }
    return { log, args };
  }
}
