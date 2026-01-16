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
 <div id="mainboardall" class="mainboardall">
    <div id="mainboard_1">
         <div id="deck_folk" class="deck decl_folk"></div>
          <div id="deck_land" class="deck deck_land"></div>
    </div>
    <div id="mainboard_2">
    </div>
    <div id="mainboard_3">
     <div id="deck_water" class="deck deck_water"></div>
     <div id="deck_space" class="deck decl_space"></div>
     <div id="deck_insp" class="deck deck_insp"></div>

      <div id="guild_yellow" class="guild guild_yellow"></div>
      <div id="guild_blue" class="guild guild_blue"></div>
      <div id="guild_black" class="guild guild_black"></div>
    </div>
 </div>


</div>
<div id="players_panels"></div>
<div id="test_stuff">
</div>
<div id="supply"></div>


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
      $("mainboard_3").appendChild($("supply"));

      this.setupNotifications();
      this.setupScoreSheet();
      this.updateBanner();

      // document.rootElement?.classList.add("bgaext_cust_back");

      var parent = document.querySelector(".debug_section"); // studio only
      if (parent) this.addActionButton("button_rcss", "Reload CSS", () => this.reloadCss(), "topbar_content");
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

         <div id='pboard_${pcolor}' class='pboard'> 
         <div id='breakroom_${pcolor}' class='breakroom'></div>
         </div>
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
      const cardType = getPart(tokenId, 1);
      const state = Number(tokenInfo.state);
      if (location.startsWith("mainarea")) {
        if (cardType == "folk" && state >= 3) result.location = "mainboard_1";
        else if (cardType == "folk") result.location = "mainboard_2";
        else if (cardType == "land" && state >= 3) result.location = "mainboard_1";
        else if (cardType == "land") result.location = "mainboard_2";
        else if (cardType == "water" && state >= 3) result.location = "mainboard_3";
        else if (cardType == "water") result.location = "mainboard_2";
        else if (cardType == "space" && state >= 3) result.location = "mainboard_3";
        else if (cardType == "space") result.location = "mainboard_2";
        else if (cardType == "insp") result.location = "mainboard_3";
      } else if (location.startsWith("hand")) {
        const color = getPart(location, 1);
        if (color != this.player_color) result.nop = true;
        else {
          result.location = `selection_area`;
          result.onClick = (x) => this.onToken(x);
        }
      } else if (location.startsWith("tableau")) {
        const color = getPart(location, 1);
        if (cardType == "home" || tokenId.startsWith("card_folk_1")) {
          result.location = `pboard_${color}`;
        } else {
          const x = tokenInfo.state;
          result.location = `pboard_column_${x}_${color}`;
          if (!$(result.location)) {
            placeHtml(`<div id='${result.location}' class='column'></div>`, `pboard_${color}`, "afterend");
          }
        }
      } else if (location.startsWith("discard")) {
        result.onEnd = (node) => this.hideCard(node);
      } else if (location.startsWith("deck")) {
        result.onEnd = (node) => this.hideCard(node);
      } else if (location.startsWith("card")) {
        result.onEnd = (node: HTMLElement) => {
          const grand = node.parentElement.parentElement;
          grand.appendChild(node);
          node.dataset[`${getPart(location, 1)}Pos`] = getPart(location, 2);
        };
      }
    } else if (tokenId.startsWith("tableau")) {
      result.nop = true;
    } else if (tokenId.startsWith("mainboard_")) {
      result.location = `mainboardall`;
    } else if (tokenId.startsWith("marker")) {
      result.location = `mainboardall`;
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
    } else if ((tokenId.startsWith("worker") || tokenId.startsWith("dice")) && location.startsWith("tableau")) {
      const color = getPart(location, 1);
      result.location = `breakroom_${color}`;
      result.onClick = (x) => this.onToken(x);
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
    const tokenId = tokenInfo.tokenId;
    switch (mainType) {
      case "worker":
        return;
      case "card": {
        tokenInfo.name = this.getTr(_("Card ${name} #${num}"), {
          name: this.getTr(this.getRulesFor(tokenId, "name") ?? tokenInfo.t),
          num: getPart(tokenId, 2)
        });

        return;
      }
    }
  }

  ttSection(prefix: string, text: string) {
    if (prefix) return `<p><b>${prefix}</b>: ${text}</p>`;
    else return `<p>${text}</p>`;
  }

  getColorName(color: string) {
    switch (color) {
      case "ff0000":
      case "red":
        return _("Red");
      case "ffcc02":
      case "yellow":
        return _("Yellow");
      case "982fff":
      case "purple":
        return _("Purple");
      case "6cd0f6":
      case "blue":
        return _("Blue");
      case "green":
        return _("Green");
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
