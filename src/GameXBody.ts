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
  private boardLayout: string = "scale";

  readonly gameTemplate = `
<div id="thething">

<div id="round_banner">
</div>
<div id='selection_area' class='selection_area'></div>
<div id="game-score-sheet"></div>
<div id="current_player_panel"></div>
<div id="mainarea_wrap">
 <div id="board_layout_controls" class="board_layout_controls">
   <button id="layout_scale" class="layout_btn active">⤢</button>
   <button id="layout_scroll" class="layout_btn">↔</button>
 </div>
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
</div>
<div id="players_panels"></div>
<div id="test_stuff">
</div>
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
      $("mainboard_3").appendChild($("supply"));

      this.setupNotifications();
      this.setupScoreSheet();
      this.updateBanner();

      document.querySelectorAll(".caravan_cell").forEach((node: HTMLElement) => this.addListenerWithGuard(node, (e) => this.onToken(e)));

      // document.rootElement?.classList.add("bgaext_cust_back");

      var parent = document.querySelector(".debug_section"); // studio only
      if (parent) this.addActionButton("button_rcss", "Reload CSS", () => this.reloadCss(), "topbar_content");

      this.setupLayoutControls();
    } catch (e) {
      console.error("Exception during game setup", e.stack);
    }

    console.log("Ending game setup");
    this.inSetup = false;
  }
  setupPlayer(playerInfo: any) {
    console.log("player info " + playerInfo.id, playerInfo);
    const pcolor = playerInfo.color;
    const pp = `player_panel_content_${pcolor}`;
    document.querySelectorAll(`#${pp}>.miniboard`).forEach((node) => node.remove());

    document.querySelectorAll(`.guild`).forEach((guild) => {
      placeHtml(`<div id='${guild.id}_${pcolor}' class='${guild.id}_${pcolor} infsupply'></div>`, guild);
    });
    placeHtml(
      `<div id='miniboard_${pcolor}' class='miniboard'>
      </div>`,
      pp
    );
    let parent = this.player_color == pcolor ? "current_player_panel" : "players_panels";
    // Generate caravan grid cells (6x3)
    let caravanCells = "";
    for (let y = 0; y < 3; y++) {
      for (let x = 0; x < 6; x++) {
        const pos = x + y * 6 + 1; // pos_1 to pos_18
        caravanCells += `<div id='caravan_${pos}_${pcolor}' class='caravan_cell' data-pos='${pos}' data-x='${x}' data-y='${y}'></div>`;
      }
    }

    placeHtml(
      `
      <div id='tableau_${pcolor}' class='tableau' data-player-name='${playerInfo.name}' style='--player-color: #${pcolor}'>

         <div id='pboard_${pcolor}' class='pboard'>
           <div id='breakroom_${pcolor}' class='breakroom'></div>
           <div id='infsupply_${pcolor}' class='infsupply'></div>
           <div id='caravan_${pcolor}' class='caravan'>
             ${caravanCells}
           </div>
         </div>
      </div>`,
      parent
    );
  }
  setupLayoutControls() {
    // Load saved preferences from localStorage
    const savedLayout = localStorage.getItem("wayfarers_board_layout") || "scale";

    this.boardLayout = savedLayout;

    // Apply saved settings
    this.applyBoardLayout();

    // Add event listeners
    $("layout_scale").addEventListener("click", () => this.setBoardLayout("scale"));
    $("layout_scroll").addEventListener("click", () => this.setBoardLayout("scroll"));

    $("layout_scale").title = _("Board Layout: Scale to fit");
    $("layout_scroll").title = _("Board Layout: Horizontal scroll");
  }

  setBoardLayout(layout: string) {
    this.boardLayout = layout;
    localStorage.setItem("wayfarers_board_layout", layout);
    this.applyBoardLayout();
  }

  applyBoardLayout() {
    const mainboardall = $("mainboardall") as HTMLElement;
    const mainarea = $("mainarea") as HTMLElement;

    // Remove all layout classes

    mainarea.classList.remove("layout_scale", "layout_scroll");

    // Reset any inline transform from previous scale mode
    mainboardall.style.transform = "";
    mainboardall.style.height = "";

    // Add active layout class
    mainarea.classList.add(`layout_${this.boardLayout}`);

    // Update button active states
    document.querySelectorAll(".layout_btn").forEach((btn) => btn.classList.remove("active"));
    $(`layout_${this.boardLayout}`)?.classList.add("active");

    // Handle scale mode with dynamic calculation
    if (this.boardLayout === "scale") {
      this.updateBoardScale();
      // Add resize listener for scale mode
      window.addEventListener("resize", this.boundUpdateBoardScale);
    } else {
      window.removeEventListener("resize", this.boundUpdateBoardScale);
    }
  }

  private boundUpdateBoardScale = () => this.updateBoardScale();

  updateBoardScale() {
    if (this.boardLayout !== "scale") return;

    const mainboardall = $("mainboardall") as HTMLElement;
    const mainarea = $("mainarea") as HTMLElement;

    // Temporarily reset transform to measure
    mainboardall.style.transform = "none";
    mainboardall.style.width = "";
    mainboardall.style.height = "";
    mainboardall.style.transformOrigin = "";

    const naturalWidth = mainboardall.scrollWidth;
    const naturalHeight = mainboardall.scrollHeight;
    const availableWidth = mainarea.clientWidth - 20; // 20px for padding

    let scale = 1;
    if (naturalWidth > availableWidth) {
      scale = availableWidth / naturalWidth;
    }

    mainboardall.style.transform = `scale(${scale})`;
    mainboardall.style.transformOrigin = "top center";
    // Set container height to scaled height so content below doesn't overlap
    mainboardall.style.height = `${naturalHeight * scale}px`;
  }

  updateBanner() {}

  setupScoreSheet() {
    const entries = [
      { property: "game_vp_tags", label: _("VP from Primary Tags") },
      { property: "game_vp_sets", label: _("VP from Tag Sets") },
      { property: "game_vp_space", label: _("VP from Space Cards") },
      { property: "game_vp_inspiration", label: _("VP from Inspiration Cards") },
      { property: "game_vp_caravan", label: _("VP from Caravan") },
      { property: "game_vp_guilds", label: _("VP from Guild Majorities") },
      { property: "total", label: _("Total"), scoresClasses: "total", width: 80, height: 40 }
    ];
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
        const x = tokenInfo.state;
        if (cardType == "home" || tokenId.startsWith("card_folk_1_")) {
          result.location = `pboard_${color}`;
          return result;
        }
        result.location = `pboard_column_${x}_${color}`;
        if (!$(result.location)) {
          // if (x < 0) placeHtml(`<div id='${result.location}' class='column' data-state='${x}' ></div>`, `tableau_${color}`, "afterbegin");
          // else
          placeHtml(
            `<div id='${result.location}' class='column' data-state='${x}' style='order: ${x};'></div>`,
            `pboard_${color}`,
            "afterend"
          );
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
    } else if (tokenId.startsWith("dice") && location.startsWith("card")) {
      result.onClick = (x) => this.onToken(x);
    } else if (tokenId.startsWith("inf") && location.startsWith("tableau")) {
      const color = getPart(location, 1);
      result.location = `infsupply_${color}`;
    } else if (tokenId.startsWith("inf") && location.startsWith("guild")) {
      const color = getPart(tokenId, 1);
      result.location = `${location}_${color}`;
    } else if (tokenId.startsWith("upg")) {
      if (location.startsWith("tableau")) {
        // Upgrade tiles in caravan - state encodes position: pos = x + y * 6 + 1
        const color = getPart(location, 1);
        const pos = Number(tokenInfo.state);
        result.location = `caravan_${pos}_${color}`;
      } else if (location.startsWith("mainarea")) {
        const cardType = getPart(tokenId, 1);
        result.onClick = (x) => this.onToken(x);
        switch (cardType) {
          case "pink":
            result.location = "mainboard_1";
            break;
          default:
            result.location = "mainboard_2";
            break;
        }
      }
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
        const t = getPart(tokenId, 1);
        const num = getPart(tokenId, 2);
        if (!num) return;

        const name = this.getTr(this.getRulesFor(tokenId, "name")) ?? this.getTokenName(`card_${t}`) ?? "?";

        tokenInfo.name = this.getTr(_("Card ${name} #${num}"), { name, num });
        tokenInfo.tooltip ??= "";

        switch (t) {
          case "land":
            tokenInfo.tooltip += this.ttSection(_("Tags"), this.getTagsListTr(tokenInfo.tags));
            if (tokenInfo.r) tokenInfo.tooltip += this.ttSection(_("Instant"), this.getTr(tokenInfo.tor));
            if (tokenInfo.d) tokenInfo.tooltip += this.ttSection(_("Die Slot"), this.getTr(tokenInfo.todr));
            if (tokenInfo.trig) {
              tokenInfo.tooltip += this.ttSection(_("Triggers on"), this.getTagsListTr(tokenInfo.trig));
              tokenInfo.tooltip += this.ttSection(_("Trigger Effect"), this.getTr(tokenInfo.todr));
            }
            break;
          case "water":
            tokenInfo.tooltip += this.ttSection(_("Tags"), this.getTagsListTr(tokenInfo.tags));
            if (tokenInfo.r) tokenInfo.tooltip += this.ttSection(_("Instant"), this.getTr(tokenInfo.tor));
            if (tokenInfo.dr) tokenInfo.tooltip += this.ttSection(_("Die Slot"), this.getTr(tokenInfo.todr));
            break;

          case "space":
            tokenInfo.tooltip += this.ttSection(_("Tags"), this.getTagsListTr(tokenInfo.tags));
            if (tokenInfo.r) tokenInfo.tooltip += this.ttSection(_("Instant"), this.getTr(tokenInfo.tor));
            tokenInfo.tooltip += this.ttSection(_("VP"), this.getTr(tokenInfo.tovp));
            break;
          case "insp":
            tokenInfo.tooltip += this.ttSection(
              undefined,
              _("If this goal is achieved at end of game the Inspiration Card will double their Star's scoring")
            );

            tokenInfo.tooltip += this.ttSection(
              undefined,
              _("Instead of gaining, card maybe discarded for the effect of the Worker Placement spot that the Card is adjacent to")
            );

            break;
        }

        return;
      }
      case "upg": {
        //num|t|r|r2|tags|vp
        const num = getPart(tokenId, 2) ?? "";
        if (!num) return;
        const color = getPart(tokenId, 1);
        const name = this.getTokenName(`upg_${color}`);
        tokenInfo.name = this.getTr(_("${name} #${num}"), {
          name,
          num
        });
        tokenInfo.tooltip = "";
        if (tokenInfo.tags) tokenInfo.tooltip += this.ttSection(_("Tags"), _(tokenInfo.tags));
        if (tokenInfo.vp) tokenInfo.tooltip += this.ttSection(_("VP"), _(tokenInfo.vp));
        return;
      }
    }
  }

  ttSection(prefix: string, text: string) {
    if (prefix) return `<p><b>${prefix}</b>: ${text}</p>`;
    else return `<p>${text}</p>`;
  }

  getTagsListTr(tags: string) {
    // get translated tags
    const tagList = tags.split(/[, \/]/);
    const trTags: string[] = [];
    for (const tag of tagList) {
      if (!tag) continue;
      trTags.push(this.getTr(this.getRulesFor(`tag_${tag}`, "name")) ?? tag);
    }
    return trTags.join(", ");
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
