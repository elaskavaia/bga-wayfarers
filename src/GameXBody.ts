/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Wayfarers implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */

/** Game class. Its Call XBody to be last in alphabetical order */
class GameXBody extends GameMachine {
  private scoreSheet: any;
  private scoreSheetAI: any;
  private inSetup = true;
  private boardLayout: string = "scale";
  private AI_PLAYER_ID = 1;
  private AI_COLOR_OVERRIDE = "982fff";

  readonly gameTemplate = `
<div id="thething">

<div id="round_banner">
</div>
<div id='selection_area' class='selection_area'></div>
<div id="game-score-sheet"></div>
  <div id="game-score-sheet-ai"></div>
<div id="current_player_panel"></div>
<div id="mainarea_wrap">
 <div id="board_layout_controls" class="board_layout_controls">
   <button id="layout_scale" class="layout_button active">⤢</button>
   <button id="layout_scroll" class="layout_button">↔</button>
 </div>
 <div id="mainarea">
  <div id="mainboardall" class="mainboardall">
    <div id="carddisplay_folk" class="carddisplay carddisplay_folk">
      <div id="deck_folk" class="deck deck_folk"></div>
    </div>
    <div id="carddisplay_land" class="carddisplay carddisplay_land">
      <div id="deck_land" class="deck deck_land"></div>
    </div>
    <div id="carddisplay_space" class="carddisplay carddisplay_space">
      <div id="deck_space" class="deck deck_space"></div>
    </div>
    <div id="carddisplay_water" class="carddisplay carddisplay_water">
      <div id="deck_water" class="deck deck_water"></div>
    </div>
    <div id="carddisplay_insp" class="carddisplay carddisplay_insp">
      <div id="deck_insp" class="deck deck_insp"></div>
    </div>
    <div id="mainboard_1" class="mainboard_x">
        <div id="jpos_0" class="jpos jpos_0"></div>
        <div id="jpos_10" class="jpos jpos_10"></div>
        <div id="jpos_15" class="jpos jpos_15"></div>
        <div id="jpos_20" class="jpos jpos_20"></div>
        <div id="jpos_23" class="jpos jpos_23"></div>
        <div id="jpos_27" class="jpos jpos_27"></div>
        <div id="jpos_32" class="jpos jpos_32"></div>
        <div id="jpos_36" class="jpos jpos_36"></div>

    </div>
    <div id="mainboard_2" class="mainboard_x">
            <div id="jpos_40" class="jpos jpos_40"></div>
        <div id="jpos_43" class="jpos jpos_43"></div>
        <div id="jpos_47" class="jpos jpos_47"></div>
        <div id="jpos_50" class="jpos jpos_50"></div>
        <div id="jpos_55" class="jpos jpos_55"></div>
        <div id="jpos_60" class="jpos jpos_60"></div>
        <div id="jpos_63" class="jpos jpos_63"></div>
        <div id="jpos_67" class="jpos jpos_67"></div>
        <div id="jpos_72" class="jpos jpos_72"></div>
        <div id="jpos_76" class="jpos jpos_76"></div>
        <div id="jpos_80" class="jpos jpos_80"></div>
        <div id="jpos_83" class="jpos jpos_83"></div>
        <div id="jpos_87" class="jpos jpos_87"></div>
        <div id="jpos_90" class="jpos jpos_90"></div>
        <div id="jpos_95" class="jpos jpos_95"></div>
    </div>
    <div id="mainboard_3" class="mainboard_x">
        <div id="jpos_100" class="jpos jpos_100"></div>
        <div id="jpos_102" class="jpos jpos_102"></div>
        <div id="jpos_103" class="jpos jpos_103"></div>
        <div id="jpos_106" class="jpos jpos_106"></div>
        <div id="jpos_107" class="jpos jpos_107"></div>


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

      if (this.isSolo()) {
        this.setupAutoma(gamedatas.playerswithbots[this.AI_PLAYER_ID]);
      }

      super.setupGame(gamedatas);
      for (const playerId of gamedatas.playerorder) {
        this.updateGuildCounters(gamedatas.players[playerId].color);
      }
      if (this.isSolo()) {
        this.updateGuildCounters(gamedatas.playerswithbots[this.AI_PLAYER_ID].color);
      }
      $("mainboard_3").appendChild($("supply"));
      this.addListenerWithGuard($("guild_black"), (e) => this.onToken(e));
      this.addListenerWithGuard($("guild_yellow"), (e) => this.onToken(e));
      this.addListenerWithGuard($("guild_blue"), (e) => this.onToken(e));
      this.addListenerWithGuard($("deck_land"), (e) => this.onToken(e));
      this.addListenerWithGuard($("deck_water"), (e) => this.onToken(e));
      document.querySelectorAll(".jpos").forEach((node: HTMLElement) => {
        this.addListenerWithGuard(node, (e) => this.onToken(e));
      });

      this.setupNotifications();
      this.setupScoreSheet();
      this.updateBanner();

      // document.rootElement?.classList.add("bgaext_cust_back");

      var parent = document.querySelector(".debug_section"); // studio only
      if (parent)
        this.bga.statusBar.addActionButton("Reload CSS", () => this.reloadCss(), { id: "button_rcss", destination: $("topbar_content") });

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
    this.createMiniboard(pcolor, pp);
    let parent = this.player_color == pcolor ? "current_player_panel" : "players_panels";
    // Generate caravan grid cells (6x3)
    let caravanCells = "";
    for (let y = 0; y < 3; y++) {
      for (let x = 0; x < 6; x++) {
        const pos = x + y * 6 + 1; // pos_1 to pos_18
        caravanCells += `<div id='ccell_${pos}_${pcolor}' class='ccell' data-pos='${pos}' data-x='${x}' data-y='${y}'></div>`;
      }
    }

    placeHtml(
      `
      <div id='tableau_${pcolor}' class='tableau' data-player-name='${playerInfo.name}' style='--player-color: #${pcolor}'>

         <div id='pboard_${pcolor}' class='pboard' data-player-name='${playerInfo.name}'>
           <div id='breakroom_${pcolor}' class='breakroom'></div>
           <div id='infsupply_${pcolor}' class='infsupply'></div>
           <div id='caravan_${pcolor}' class='caravan'>
             ${caravanCells}
           </div>
         </div>
      </div>`,
      parent
    );

    const boardNum = parseInt(this.gamedatas.tokens[`pboard_${pcolor}`]?.state ?? "1");
    $(`caravan_${pcolor}`)
      .querySelectorAll(".ccell")
      .forEach((node: HTMLElement) => {
        this.addListenerWithGuard(node, (e) => this.onToken(e));
        const num = Number(getPart(node.id, 1)) - 1;
        const r = this.getRulesFor(`pbonus_${boardNum}_${num}`, "r", "");
        node.dataset.r = r;
        if (r) {
          placeHtml(`<div class='wicon_${r} wicon'></div>`, node);
          const html = this.getTooltipHtml(_("Caravan Cell"), _("When placing upgrade that covers this cell:") + " " + this.getOpListTr(r));
          this.game.addTooltipHtml(node.id, html, this.game.defaultTooltipDelay);
        }
      });
  }

  setupAutoma(playerInfo: any) {
    console.log("player info " + playerInfo.id, playerInfo);
    const pcolor = playerInfo.color;
    const realcolor = this.AI_COLOR_OVERRIDE;

    // const op = this.bga.playerPanels.getElement(playerInfo.id);// this does not work
    const op = "overall_player" + "_board_" + playerInfo.id;
    $(op)?.remove();

    this.bga.playerPanels.addAutomataPlayerPanel(playerInfo.id, playerInfo.name, {
      iconClass: "aida-avatar",
      score: playerInfo.score,
      color: realcolor
    });

    document.querySelectorAll(`.guild`).forEach((guild) => {
      placeHtml(`<div id='${guild.id}_${pcolor}' class='${guild.id}_${pcolor} infsupply'></div>`, guild);
    });
    placeHtml(
      `<div id='player_panel_content_${pcolor}' class='player_panel_content'></div>`,
      this.bga.playerPanels.getElement(playerInfo.id)
    );
    this.createMiniboard(pcolor, `player_panel_content_${pcolor}`);
    let parent = "players_panels";
    // Generate caravan grid cells (7x3)
    let caravanCells = "";
    for (let y = 0; y < 3; y++) {
      for (let x = 0; x < 7; x++) {
        const pos = x + y * 7 + 1;
        caravanCells += `<div id='ccell_${pos}_${pcolor}' class='ccell' data-pos='${pos}' data-x='${x}' data-y='${y}'></div>`;
      }
    }
    const boardNum = -parseInt(this.gamedatas.tokens[`pboard_${pcolor}`]?.state ?? "-1");
    placeHtml(
      `
      <div id='tableau_${pcolor}' class='tableau' data-player-name='${playerInfo.name}' style='--player-color: #${realcolor}'>

         <div id='pboard_${pcolor}' class='pboard' data-player-name='${playerInfo.name}'>
           <div id='breakroom_${pcolor}' class='breakroom'></div>
           <div id='restrack_${pcolor}' class='restrack'></div>
           <div id='comettrack_${pcolor}' class='comettrack'></div>
           <div id='infsupply_${pcolor}' class='infsupply'></div>
           <div id='caravan_${pcolor}' class='caravan'>
             ${caravanCells}
           </div>
         </div>
      </div>`,
      parent
    );

    $(`caravan_${pcolor}`)
      .querySelectorAll(".ccell")
      .forEach((node: HTMLElement) => {
        const num = Number(getPart(node.id, 1)) - 1;
        const r = this.getRulesFor(`aibonus_${boardNum}_${num}`, "r", "");
        node.dataset.r = r;
        if (r) {
          const html = this.getTooltipHtml(_("Caravan Cell"), _("When placing upgrade that covers this cell:") + " " + this.getOpListTr(r));
          this.game.addTooltipHtml(node.id, html, this.game.defaultTooltipDelay);
        }
      });
  }
  setupLayoutControls() {
    super.setupLocalControls("board_layout_controls");
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
    $("ebd-body").dataset.boardLayout = this.boardLayout;
    this.boundUpdateBoardScale();

    // Update button active states
    document.querySelectorAll(".layout_button").forEach((btn) => btn.classList.remove("active"));
    $(`layout_${this.boardLayout}`)?.classList.add("active");

    // Handle scale mode with dynamic calculation
    if (this.boardLayout === "scale") {
      // Add resize listener for scale mode
      window.addEventListener("resize", this.boundUpdateBoardScale);
    } else {
      window.removeEventListener("resize", this.boundUpdateBoardScale);
    }
  }

  private boundUpdateBoardScale = () => {
    this.updateBoardScale($("mainboardall"));
    // main player
    document.querySelectorAll("#current_player_panel .tableau").forEach((node: HTMLElement) => {
      this.updateBoardScale(node);
    });
    // other players take max
    let min = 1;
    document.querySelectorAll("#players_panels .tableau").forEach((node: HTMLElement) => {
      this.updateBoardScale(node);
      const scale = parseFloat(node.dataset.scale);
      if (scale < min) min = scale;
    });
    document.querySelectorAll("#players_panels .tableau").forEach((node: HTMLElement) => {
      this.applyScale(node, min);
    });
  };

  updateBoardScale(scalecontrol: HTMLElement) {
    const set = this.boardLayout === "scale";
    const parent = scalecontrol.parentElement;

    // Reset all inline style
    scalecontrol.style.transform = "none";
    scalecontrol.style.width = "";
    scalecontrol.style.height = "";
    scalecontrol.style.marginBottom = "";
    scalecontrol.style.transformOrigin = "";
    scalecontrol.scrollLeft = 0;
    scalecontrol.dataset.scale = "1";
    parent.scrollLeft = 0;

    if (!set) return; // just unset

    const naturalWidth = scalecontrol.scrollWidth;
    const availableWidth = parent.clientWidth;

    let scale = 1;
    if (naturalWidth > availableWidth) {
      scale = availableWidth / naturalWidth;
    }

    this.applyScale(scalecontrol, scale);
  }

  applyScale(scalecontrol: HTMLElement, scale: number) {
    if (Math.abs(scale - 1) < 0.01) return;
    const naturalHeight = scalecontrol.scrollHeight;
    scalecontrol.dataset.scale = String(scale);
    scalecontrol.style.transform = `scale(${scale})`;
    scalecontrol.style.transformOrigin = "top center";
    // Use negative margin to reduce flow space instead of setting height,
    // so that absolutely positioned children keep their containing block size
    const reducedHeight = naturalHeight * (1 - scale);
    scalecontrol.style.marginBottom = `-${reducedHeight}px`;
  }

  updateBanner() {
    if (this.gamedatas.lastTurn) this.bga.gameArea.addLastTurnBanner(_("This is the last round!"));
    else this.bga.gameArea.removeLastTurnBanner();
  }

  setupScoreSheet() {
    const entries = [
      { property: "game_vp_tags", label: _("VP from Primary Tags") },
      { property: "game_vp_sets", label: _("VP from Tag Sets") },
      { property: "game_vp_space", label: _("VP from Space Cards") },
      { property: "game_vp_insp", label: _("VP from Inspiration Cards") },
      { property: "game_vp_caravan", label: _("VP from Upgrades") },
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

    // add second scoreSheet for AI
    if (this.isSolo() && this.gamedatas.aiEndScores) {
      this.setupAIScoreSheet(this.gamedatas.aiEndScores);
    }
  }
  setupAIScoreSheet(scores: any) {
    if (this.scoreSheetAI) return;
    const aiEntries = [
      { property: "game_vp_ai_folk", label: _("VP from Folk Cards (1 VP per card)") },
      { property: "game_vp_ai_cards", label: _("VP from Land/Water Cards (2 VP per card)") },
      { property: "game_vp_ai_space", label: _("VP from Space Cards (3 VP per card)") },
      { property: "game_vp_ai_insp", label: _("VP from Inspiration Cards (4 VP per card)") },
      { property: "game_vp_ai_caravan", label: _("VP from Upgrades") },
      { property: "game_vp_ai_guilds", label: _("VP from Guild Majorities") },
      { property: "total", label: _("Total"), scoresClasses: "total", width: 80, height: 40 }
    ];
    const aiPlayer = this.gamedatas.playerswithbots[this.AI_PLAYER_ID];
    const players = {};
    players[this.AI_PLAYER_ID] = { ...aiPlayer, color: this.AI_COLOR_OVERRIDE };
    this.scoreSheetAI = new BgaScoreSheet.ScoreSheet(document.getElementById(`game-score-sheet-ai`), {
      animationsActive: () => this.gameAnimationsActive(),
      playerNameWidth: 80,
      playerNameHeight: 30,
      entryLabelWidth: 220,
      entryLabelHeight: 20,
      classes: "score-sheet",
      players,
      entries: aiEntries,
      scores,
      onScoreDisplayed: (property, playerId, score: number) => {
        if (property === "total") {
          this.bga.playerPanels.getScoreCounter(playerId).setValue(score);
        }
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
      if (location.startsWith("mainarea")) {
        result.location = `carddisplay_${cardType}`;
      } else if (location.startsWith("hand")) {
        const color = getPart(location, 1);
        if (color != this.player_color) result.nop = true;
        else {
          result.location = `selection_area`;
          result.onClick = (x) => this.onToken(x);
        }
      } else if (location.startsWith("tableau")) {
        const color = getPart(location, 1);
        let x = tokenInfo.state;
        if (cardType == "home" || tokenId.startsWith("card_folk_1_") || x == 1 || x == -1) {
          result.location = `pboard_${color}`;
          return result;
        }
        if (location.startsWith("tableau_ffffff")) {
          // automa places card in different negative column per type
          switch (cardType) {
            case "folk":
              x = -2;
              break;
            case "land":
            case "water":
              x = -3;
              break;
            case "space":
              x = -4;
              break;
            case "insp":
              x = -5;
              break;
          }
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
          if (this.gameAnimationsActive()) {
            this.boundUpdateBoardScale();
            $(result.location).scrollIntoView({ behavior: "smooth", block: "center" });
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
      // } else if (tokenId.startsWith("jpos")) {
      //   //jpos_10
      //   result.onClick = (x) => this.onToken(x);
    } else if (tokenId.startsWith("mainboard_")) {
      result.location = `mainboardall`;
    } else if (tokenId.startsWith("marker")) {
      result.location = `jpos_${tokenInfo.state}`;
    } else if (tokenId.startsWith("hand")) {
      result.nop = true;
    } else if (tokenId.startsWith("deck") || tokenId.startsWith("discard")) {
      result.nop = true;
    } else if (tokenId.startsWith("slot") || tokenId == "round_banner") {
      result.nop = true; // do not move slots
    } else if (tokenId.startsWith("tracker_res") && location.startsWith("tableau")) {
      const color = getPart(location, 1);
      result.location = `restrack_${color}`;
    } else if (tokenId.startsWith("tracker_comet") && location.startsWith("tableau")) {
      const color = getPart(location, 1);
      result.location = `comettrack_${color}`;
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
    } else if (tokenId.startsWith("inf")) {
      // influence
      result.onClick = (x) => this.onToken(x);
      const infColor = getPart(tokenId, 1);
      if (location.startsWith("tableau")) {
        const color = getPart(location, 1);
        result.location = `infsupply_${color}`;
        result.onEnd = () => this.updateGuildCounters(infColor);
      } else if (location.startsWith("guild")) {
        result.location = `${location}_${infColor}`;
        result.onEnd = () => this.updateGuildCounters(infColor);
      }
    } else if (tokenId.startsWith("upg")) {
      if (location.startsWith("tableau")) {
        // Upgrade tiles in caravan - state encodes position: pos = x + y * 6 + 1
        const color = getPart(location, 1);
        const pos = Number(tokenInfo.state);
        if (pos <= 0) {
          // they hung out on tableau?
        } else {
          result.location = `ccell_${pos}_${color}`;
        }
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

  createMiniboard(pcolor: string, parentId: string) {
    placeHtml(
      `<div id='miniboard_${pcolor}' class='miniboard'>
        <div id='guild_yellow_count_${pcolor}' class='guild_count wicon wicon_inf_yellow' data-state='0'></div>
        <div id='guild_blue_count_${pcolor}' class='guild_count wicon wicon_inf_blue' data-state='0'></div>
        <div id='guild_black_count_${pcolor}' class='guild_count wicon wicon_inf_black' data-state='0'></div>
      </div>`,
      parentId
    );
  }

  updateGuildCounters(pcolor: string) {
    const guilds = ["yellow", "blue", "black"];
    for (const guild of guilds) {
      let count = 0;
      for (const token in this.gamedatas.tokens) {
        if (token.startsWith(`influence_${pcolor}_`) && this.gamedatas.tokens[token].location === `guild_${guild}`) {
          count++;
        }
      }
      const node = $(`guild_${guild}_count_${pcolor}`);
      if (node) node.dataset.state = String(count);
    }
  }

  onToken_nonActive(target: string, node: HTMLElement) {
    if (!target) return false;
    const mainType = getPart(target, 0);
    switch (mainType) {
      case "card":
        {
          const cardType = getPart(target, 1);
          if (cardType == "home") {
            this.showHiddenContent(node, _("Home Actions"), target);
            return false;
          }
          const container = $(target).parentElement?.id;
          this.showHiddenContent(container, _("Pile contents"), 0, function (a: HTMLElement, b: HTMLElement) {
            const orderA = parseInt(a.dataset.state);
            const orderB = parseInt(b.dataset.state);
            return -orderA + orderB; // descending
          });
        }
        break;
    }
    return true;
  }

  createCustomButtonImageHtml(target: string, paramInfo: ParamInfo): string | undefined {
    const op = this.opInfo.type;
    switch (op) {
      case "diceMod":
        // special rendering
        const from = (paramInfo as any).from;
        const to = (paramInfo as any).to;
        const clases = $(paramInfo.token_id).className;
        const elem = `<div class='${clases}' data-state='${from}'></div>⤇<div class='${clases}' data-state='${to}'></div>`;

        return elem;
      default:
        return undefined;
    }
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

        const tname = this.getTokenName(`card_${t}`);
        const gname = this.getTr(tokenInfo.nom);

        tokenInfo.name = gname ? `${gname}` : `${tname} #${num}`;
        const origtt = (tokenInfo.tooltip ??= "");

        switch (t) {
          case "home":
            tokenInfo.tooltip = origtt;
            // tokenInfo.tooltip += this.ttSection(_("Name"), this.getTr(tokenInfo.nom));
            if (tokenInfo.dr) tokenInfo.tooltip += this.ttSection(_("Die Slot"), this.getTr(tokenInfo.todr));
            //tokenInfo.imageTypes = "home";
            break;
          case "land":
            tokenInfo.tooltip = this.ttSection(_("Card Type"), tname);
            tokenInfo.tooltip += this.ttSection(_("Ref#"), num);
            tokenInfo.tooltip += this.ttSection(_("Tags"), this.getTagsListTr(tokenInfo.tags));
            if (tokenInfo.r) tokenInfo.tooltip += this.ttSection(_("Instant"), this.getTr(tokenInfo.tor));
            if (tokenInfo.d) tokenInfo.tooltip += this.ttSection(_("Die Slot"), this.getTr(tokenInfo.todr));
            if (tokenInfo.trig) {
              tokenInfo.tooltip += this.ttSection(_("Triggers on"), this.getTagsListTr(tokenInfo.trig));
              tokenInfo.tooltip += this.ttSection(_("Trigger Effect"), this.getTr(tokenInfo.todr));
            }
            break;
          case "water":
            tokenInfo.tooltip = this.ttSection(_("Card Type"), tname);
            tokenInfo.tooltip += this.ttSection(_("Ref#"), num);
            tokenInfo.tooltip += this.ttSection(_("Tags"), this.getTagsListTr(tokenInfo.tags));
            if (tokenInfo.r) tokenInfo.tooltip += this.ttSection(_("Instant"), this.getTr(tokenInfo.tor));
            if (tokenInfo.dr) tokenInfo.tooltip += this.ttSection(_("Die Slot"), this.getTr(tokenInfo.todr));
            break;

          case "space":
            tokenInfo.tooltip = this.ttSection(_("Card Type"), tname);
            tokenInfo.tooltip += this.ttSection(_("Ref#"), num);
            //tokenInfo.tooltip += this.ttSection(_("Name"), this.getTr(tokenInfo.nom));
            tokenInfo.tooltip += this.ttSection(_("Tags"), this.getTagsListTr(tokenInfo.tags));
            tokenInfo.tooltip += this.ttSection(_("Cost"), _("Base cost in Silver shown on the board under the card"));
            if (tokenInfo.r) tokenInfo.tooltip += this.ttSection(_("Instant"), this.getTr(tokenInfo.tor));
            tokenInfo.tooltip += this.ttSection(_("VP"), this.getTr(tokenInfo.tovp));
            break;

          case "folk":
            tokenInfo.tooltip = this.ttSection(_("Card Type"), tname);
            tokenInfo.tooltip += this.ttSection(_("Ref#"), num);
            tokenInfo.tooltip += this.ttSection(_("Name"), this.getTr(tokenInfo.nom));
            tokenInfo.tooltip += this.ttSection(_("Cost"), tokenInfo.cost + " " + _("Silver"));
            tokenInfo.tooltip += this.ttSection(_("Required Tags"), this.getTagsListTr(tokenInfo.tags, ` / `));
            if (tokenInfo.rest) {
              tokenInfo.tooltip += this.ttSection(_("Rest"), this.getTr(origtt));
              tokenInfo.tooltip += this.ttSection(undefined, _("Rest bonus is activated when Rest is taken with one or less die"));
            } else {
              if (tokenInfo.dr) {
                tokenInfo.tooltip += this.ttSection(_("Bonus"), this.getTr(origtt));
                tokenInfo.tooltip += this.ttSection(undefined, _("Bonus is activated when die is placed above"));
              }
            }
            if (tokenInfo.da) {
              tokenInfo.tooltip += this.ttSection(_("Assets"), this.getOpListTr(tokenInfo.da));
              tokenInfo.tooltip += this.ttSection(undefined, _("Assets are activated when die is placed above"));
            }

            break;
          case "insp":
            tokenInfo.tooltip = this.ttSection(_("Card Type"), tname);
            tokenInfo.tooltip += this.ttSection(_("Ref#"), num);
            tokenInfo.tooltip += this.ttSection(_("Goal"), this.getTr(origtt));
            tokenInfo.tooltip += this.ttSection(
              undefined,
              _("If this goal is achieved at end of game the Inspiration Card will double their Star's scoring")
            );

            tokenInfo.tooltip += this.ttSection(
              _("Instant"),
              _("Instead of gaining, card maybe discarded for the effect of the Worker Placement spot that the Card is adjacent to")
            );

            break;

          case "scheme":
            // # 6 Scheme Cards for Solo AI
            // # t: blue or red
            // # c: silver value (0-2) - how far AI moves on Resource Track
            // # r1: first action AI attempts (primary)
            // # r2: second/fallback action if first is impossible r2 is also used on rest: AI acquires based on this
            // # p: special (pink) upgrade tile priority
            // # comet: 1 if card has comet icon (checked on rest), 0 otherwise
            tokenInfo.tooltip = this.ttSection(_("Card Type"), tname);
            tokenInfo.tooltip += this.ttSection(_("Ref#"), num);
            //tokenInfo.tooltip += this.ttSection(_("Name"), this.getTr(tokenInfo.nom));
            tokenInfo.tooltip += this.ttSection(_("Silver"), tokenInfo.c);
            tokenInfo.tooltip += this.ttSection(_("Color"), tokenInfo.t == "red" ? _("Red") : _("Blue"));
            tokenInfo.tooltip += this.ttSection(_("Primary Action"), this.getOpListTr(tokenInfo.r1));
            tokenInfo.tooltip += this.ttSection(_("Fallback Action"), this.getOpListTr(tokenInfo.r2));
            tokenInfo.tooltip += this.ttSection(_("Special Upgrade"), tokenInfo.p);
            if (tokenInfo.comet == "1") tokenInfo.tooltip += this.ttSection(_("Comet"), _("Yes"));
            else tokenInfo.tooltip += this.ttSection(_("Comet"), _("No"));
            break;
        }

        return;
      }
      case "upg": {
        //num|t|r|r2|tags|vp
        const num = getPart(tokenId, 2) ?? "";
        if (!num) return;
        const color = getPart(tokenId, 1);
        const tname = this.getTokenName(`upg_${color}`);
        tokenInfo.tooltip = "";
        tokenInfo.tooltip += this.ttSection(_("Type"), tname);
        tokenInfo.tooltip += this.ttSection(_("Ref#"), num);
        if (tokenInfo.tags) tokenInfo.tooltip += this.ttSection(_("Tags"), _(tokenInfo.tags));

        // r and r2 are left and right side of the same tile face
        if (tokenInfo.r || tokenInfo.r2) {
          const assets = [this.getOpListTr(tokenInfo.r), this.getOpListTr(tokenInfo.r2)].filter(Boolean).join(" | ");
          tokenInfo.tooltip += this.ttSection(_("Assets"), assets);
        }

        // Odd/even pairs are front/back of same physical tile

        const numInt = parseInt(num);
        if (tokenInfo.r && tokenInfo.r2 && numInt % 2 == 1) {
          const reverseNum = numInt + 1;
          const reverseTokenId = `upg_${color}_${reverseNum}`;
          const reverseInfo = this.getTokenDisplayInfo(reverseTokenId, false);
          if (reverseInfo && reverseInfo.typeKey !== tokenInfo.typeKey) {
            const revAssets = [this.getOpListTr(reverseInfo.r), this.getOpListTr(reverseInfo.r2)].join(" | ");
            tokenInfo.tooltip += this.ttSection(_("Assets (Reverse Side)"), revAssets);
            tokenInfo.reverseImageTypes = reverseInfo.imageTypes;
            tokenInfo.imageTypes += " _dual_image";
          }
        }
        if (tokenInfo.vp) tokenInfo.tooltip += this.ttSection(_("VP"), _(tokenInfo.vp));

        return;
      }

      case "dice": {
        const num = getPart(tokenId, 2) ?? "";
        if (!num) return;
        // const color = getPart(tokenId, 1);
        tokenInfo.imageTypes += " _nottimage";
        return;
      }
      case "pboard":
      case "mainarea":
        tokenInfo.showtooltip = false;
        break;
    }
  }

  ttSection(prefix: string, text: string) {
    if (prefix) return `<p><b>${prefix}</b>: ${text}</p>`;
    else return `<p>${text}</p>`;
  }

  getTagsListTr(tags: string, sep: string = ", ") {
    if (!tags) return "";
    // get translated tags
    const tagList = tags.split(/[, \/]/);
    const trTags: string[] = [];
    for (const tag of tagList) {
      if (!tag) continue;
      trTags.push(this.getTr(this.getRulesFor(`tag_${tag}`, "name")) ?? tag);
    }
    return trTags.join(sep);
  }

  getOpListTr(tags: string, sep: string = ", ") {
    // get translated ops
    if (!tags) return "";
    const tagList = tags.split(/[, \/]/);
    const trTags: string[] = [];
    for (const tag of tagList) {
      if (!tag) continue;
      let opName = this.getRulesFor(`Op_${tag}`, "name", null);
      if (!opName) opName = this.getRulesFor(tag, "name", null);
      if (!opName) opName = tag;
      trTags.push(this.getTr(opName));
    }
    return trTags.join(sep);
  }

  getColorName(color: string) {
    switch (color) {
      case "ff0000":
      case "red":
        return _("Red");
      case "ffcc02":
      case "yellow":
        return _("Yellow");
      case "ffffff": // automa purple
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

  showHiddenContent(id: ElementOrId, title: string, selectedId?: string | number, sort?: any) {
    let dialog = new ebg.popindialog();
    dialog.create("pile");
    dialog.setTitle(title);
    const node = this.cloneAndFixIds(id, "_tt", true);
    node.removeAttribute("_lis");
    const cards_htm = node.innerHTML;
    const html = `
    <div id="card_pile_selector" class="card_pile_selector"></div>
    <div id="card_pile_help" class="card_pile_help">${_("Click on element below to see details")}</div>
    <div id="pile_content" class="pile_content">${cards_htm}</div>`;
    dialog.setContent(html);
    const parent = $("pile_content");

    let children = Array.from(parent.children);
    if (sort) {
      children.sort(sort);
      parent.replaceChildren(...children);
    }
    children.forEach((node: HTMLElement, index) => {
      const origId = node.id.replace("_tt", "");
      node.addEventListener("click", (e) => {
        const selected_html = this.getTooltipHtmlForToken(origId);
        $("card_pile_selector").innerHTML = selected_html;
      });
      if (index === selectedId) selectedId = origId;
    });
    if (children.length == 0) {
      $("card_pile_help").remove();
    }
    if (selectedId && typeof selectedId === "string") {
      const selected_html = this.getTooltipHtmlForToken(selectedId);
      $("card_pile_selector").innerHTML = selected_html;
    }
    dialog.show();
    return dialog;
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

  async notif_lastTurn(args: any) {
    this.gamedatas.lastTurn = true;
    this.updateBanner();
  }

  async notif_endScores(args: any) {
    // setting scores will make the score sheet visible if it isn't already
    if (args.final) {
      $("round_banner").innerHTML = _("Game Over");
    }
    await this.scoreSheet.setScores(args.endScores, {
      startBy: this.bga.players.getCurrentPlayerId()
    });
    if (args.aiEndScores) {
      if (!this.scoreSheetAI) {
        this.setupAIScoreSheet(args.aiEndScores);
      } else {
        await this.scoreSheetAI.setScores(args.aiEndScores);
      }
    }
  }
  /** @Override */
  bgaFormatText(log: string, args: any) {
    try {
      if (log && args && !args.processed && log.includes("$")) {
        args.processed = true;

        if (!args.player_id) {
          args.player_id = this.bga.players.getActivePlayerId();
        }
        if (args.player_id == this.AI_PLAYER_ID) {
          args.player_name = `<span class="playername" style="color: #${this.AI_COLOR_OVERRIDE};">Aida</span>`;
        } else if (args.player_id && !args.player_name) {
          args.player_name = this.gamedatas.players[args.player_id].name;
        }

        if (args.you) args.you = this.divYou(); // will replace ${you} with colored version
        args.You = this.divYou(); // will replace ${You} with colored version

        if (args.reason) {
          args.reason = "(" + this.getTokenName(args.reason) + ")";
        }
        if (log.includes("actplayer") && !args.actplayer) {
          args.actplayer = this.gamedatas.players[this.bga.players.getActivePlayerId()].name;
        }
        const res = super.bgaFormatText(log, args);
        log = res.log;
        args = res.args;
      }

      // Process square bracket syntax [tokenId]
      if (log && log.includes("[")) {
        log = log.replace(/\[([^\]]+)\]/g, (match, keyExpr) => {
          try {
            return this.getTokenPresentaton(keyExpr, keyExpr, args) ?? match;
          } catch (e) {
            console.error(`Failed to get token presentation for [${keyExpr}]`, e);
            return match; // Return original if error
          }
        });
      }
    } catch (e) {
      console.error(log, args, "Exception thrown", e.stack);
    }
    return { log, args };
  }
}
