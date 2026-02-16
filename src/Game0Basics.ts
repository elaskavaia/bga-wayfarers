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
// @ts-ignore
GameGui = /** @class */ (function () {
  function GameGui() {}
  return GameGui;
})();

/** Class that extends default bga core game class with more functionality
 */

class Game0Basics extends GameGui<any> {
  player_color: string;
  defaultTooltipDelay: number = 800;

  constructor() {
    super();
    console.log("game constructor");
  }

  // state hooks
  setup(gamedatas: any) {
    console.log("Starting game setup", gamedatas);
    const first_player_id = Object.keys(gamedatas.players)[0];
    if (!this.bga.players.isCurrentPlayerSpectator()) this.player_color = gamedatas.players[this.player_id].color;
    else this.player_color = gamedatas.players[first_player_id].color;
  }

  onEnteringState(stateName: string, eargs: { args: any }) {
    console.log("onEnteringState", stateName, eargs, this.debugStateInfo());

    // Call appropriate method
    const args = eargs?.args; // this method has extra wrapper for args for some reason
    this.callfn("onEnteringState_before", args);
    const methodName = "onEnteringState_" + stateName;

    // Call appropriate method
    const privates = args._private;
    let nargs = args;
    if (privates) {
      nargs = { ...nargs, ...privates };
      delete nargs._private;
    }

    this.callfn(methodName, nargs);
  }

  onLeavingState(stateName: string) {
    //console.log("onLeavingState", stateName, this.debugStateInfo());

    const methodName = "onLeavingState_" + stateName;
    this.callfn(methodName, {});
  }

  onUpdateActionButtons(stateName: string, args: any) {
    // Call appropriate method
    console.log("onUpdateActionButtons", stateName, this.debugStateInfo());
    const privates = args._private;
    let nargs = args;
    if (privates) {
      nargs = { ...nargs, ...privates };
      delete nargs._private;
    }

    this.callfn("onUpdateActionButtons_" + stateName, nargs);
  }

  // utils
  /**
   * Remove all listed class from all document elements
   * @param classList - list of classes  array
   */
  removeAllClasses(...classList: string[]) {
    if (!classList) return;

    classList.forEach((className) => {
      document.querySelectorAll(`.${className}`).forEach((node) => {
        node.classList.remove(className);
      });
    });
  }

  onCancel(event?: Event) {
    this.cancelLocalStateEffects();
  }

  cancelLocalStateEffects() {
    //console.log(this.last_server_state);

    if (this.on_client_state) this.restoreServerGameState();

    this.updatePageTitle(this.gamedatas.gamestate);
  }

  /**
   * Function overriden to prevent format error on reset
   * @Override
   * @param state
   * @returns
   */
  updatePageTitle(state = null) {
    //debugger;
    //console.log("updatePageTitle", state);
    if ((this as any).prevent_error_rentry === undefined) (this as any).prevent_error_rentry = 11; // XXX hack to prevent popin up formatter error
    try {
      return this.inherited(arguments);
    } catch (e) {
      console.error("updatePageTitle", e);
    } finally {
      (this as any).prevent_error_rentry = undefined;
    }
  }
  /**
   * Function overriden to prevent interface lock of other player
   * @Override
   * @param state
   * @returns
   */
  onLockInterface(lock) {
    if (lock.status == "queued") {
      // hack: do not hide the buttons when locking call comes from another player
    } else {
      this.inherited(arguments);
    }
  }

  destroyDivOtherCopies(id: string) {
    const panels = document.querySelectorAll("#" + id);
    panels.forEach((p, i) => {
      if (i < panels.length - 1) p.parentNode.removeChild(p);
    });
    return panels[0] ?? null;
  }

  setupLocalControls(divId: string) {
    // undo adds more of these
    this.destroyDivOtherCopies(divId);
    if (this.bga.players.isCurrentPlayerSpectator()) {
      const loc = document.querySelector("#right-side .spectator-mode");
      if (loc) loc.insertAdjacentElement("beforeend", $(divId));
    } else {
      const loc = document.querySelector("#current_player_board");
      if (loc) loc.insertAdjacentElement("beforeend", $(divId));
    }
  }

  addCancelButton(name?: string, handler?: any) {
    if (!name) name = _("Cancel");
    if (!handler) handler = () => this.onCancel();
    if ($("button_cancel")) $("button_cancel").remove();
    this.bga.statusBar.addActionButton(name, handler, { id: "button_cancel", color: "alert" });
  }

  /** Show pop in dialog. If you need div id of dialog its `popin_${id}` where id is second parameter here */
  showPopin(html: string, id = "gg_dialog", title: string = undefined, refresh: boolean = false) {
    const content_id = `popin_${id}_contents`;
    if (refresh && $(content_id)) {
      $(content_id).innerHTML = html;
      return undefined;
    }

    const dialog = new ebg.popindialog();
    dialog.create(id);
    if (title) dialog.setTitle(title);
    dialog.setContent(html);
    dialog.show();
    return dialog;
  }

  getStateName() {
    return this.gamedatas.gamestate.name;
  }

  getPlayerColor(playerId: number) {
    return this.gamedatas.players[playerId]?.color ?? "ffffff";
  }

  getPlayerName(playerId: number) {
    return this.gamedatas.players[playerId]?.name ?? _("Not a Player");
  }

  custom_getPlayerIdByColor(color: string): number | undefined {
    for (var playerId in this.gamedatas.players) {
      var playerInfo = this.gamedatas.players[playerId];
      if (color == playerInfo.color) {
        return parseInt(playerId);
      }
    }
    return undefined;
  }
  removeTooltip(nodeId: string): void {
    // if (this.tooltips[nodeId])
    if (!nodeId) return;
    //console.log("removeTooltip", nodeId);
    $(nodeId)?.classList.remove("withtooltip");
    this.inherited(arguments);
    delete this.tooltips[nodeId];
  }
  /**
   * setClientState and defines handler for onUpdateActionButtons and onToken for specific client state only
   * the setClientState will be called asyncroniously
   * @param name - state name i.e. client_foo
   * @param onUpdate - onUpdateActionButtons handler
   * @param onToken - onToken handler
   * @param args - args passes to setClientState
   */
  setClientStateUpdOn(name: string, onUpdate: (args: any) => void, onToken: (id: string) => void, args?: any) {
    this[`onUpdateActionButtons_${name}`] = onUpdate;
    if (onToken) this[`onToken_${name}`] = onToken;
    setTimeout(() => this.setClientState(name, args), 1);
  }

  debugStateInfo() {
    let replayMode = false;
    if (typeof g_replayFrom != "undefined") {
      replayMode = true;
    }

    const res = {
      isCurrentPlayerActive: gameui.bga.players.isCurrentPlayerActive(),
      animationsActive: gameui.bgaAnimationsActive(),
      replayMode: replayMode
    };
    return res;
  }

  callfn(methodName: string, ...args: any) {
    if (this[methodName] !== undefined) {
      console.log("Calling " + methodName, args);
      return this[methodName](...args);
    }
    return undefined;
  }
  /** @Override onScriptError from gameui */
  onScriptError(msg: any, url, linenumber) {
    if ((gameui as any).page_is_unloading) {
      // Don't report errors during page unloading
      return;
    }
    // In anycase, report these errors in the console
    console.error(msg);
    // cannot call super - dojo still have to used here
    //super.onScriptError(msg, url, linenumber);
    return this.inherited(arguments);
  }

  divYou() {
    var color = "black";
    var color_bg = "";
    if (this.gamedatas.players[this.player_id]) {
      color = this.gamedatas.players[this.player_id].color;
    }
    if (this.gamedatas.players[this.player_id] && this.gamedatas.players[this.player_id].color_back) {
      color_bg = "background-color:#" + this.gamedatas.players[this.player_id].color_back + ";";
    }
    var you = '<span style="font-weight:bold;color:#' + color + ";" + color_bg + '">' + _("You") + "</span>";
    return you;
  }

  getTr(name: string | NotificationMessage, args: any = {}) {
    if (!name) return "";

    if ((name as any).log !== undefined) {
      const notif = name as NotificationMessage;
      const log = this.format_string_recursive(this.clienttranslate_string(notif.log), notif.args);
      return log;
    }
    if (typeof name !== "string") return name.toString();

    //if (name.includes("$"))
    {
      const log = this.format_string_recursive(this.clienttranslate_string(name) as string, args);
      return log;
    }

    //return this.clienttranslate_string(name);
  }
  reloadCss() {
    var links = document.getElementsByTagName("link");
    for (var cl in links) {
      var link = links[cl];
      if (link.rel === "stylesheet" && link.href.includes("99999")) {
        var index = link.href.indexOf("?timestamp=");
        var href = link.href;
        if (index >= 0) {
          href = href.substring(0, index);
        }
        link.href = href + "?timestamp=" + Date.now();
        console.log("reloading " + link.href);
      }
    }
  }
  isSolo() {
    return this.gamedatas.playerorder.length == 1;
  }

  protected lastMoveId = 0;
  private prevLogId = 0;
  addTooltipToLogItems(log_id: number) {
    // override
  }

  addMoveToLog(log_id: number, move_id) {
    this.inherited(arguments);
    if (move_id) this.lastMoveId = move_id;
    if (this.prevLogId + 1 < log_id) {
      // we skip over some logs, but we need to look at them also
      for (let i = this.prevLogId + 1; i < log_id; i++) {
        this.addTooltipToLogItems(i);
      }
    }

    this.addTooltipToLogItems(log_id);

    // add move #
    var prevmove = document.querySelector('[data-move-id="' + move_id + '"]');
    if (prevmove) {
      // ?
    } else if (move_id) {
      const tsnode = document.createElement("div");
      tsnode.classList.add("movestamp");
      tsnode.innerHTML = _("Move #") + move_id;
      const lognode = $("log_" + log_id);
      lognode.appendChild(tsnode);

      tsnode.setAttribute("data-move-id", move_id);
    }
    this.prevLogId = log_id;
  }

  notif_log(args: any) {
    // if (notif.log) {
    //   console.log(notif.log, notif.args);
    //   var message = this.format_string_recursive(notif.log, notif.args);
    //   if (message != notif.log) console.log(message);
    // } else {
    if (args.log) {
      var message = this.format_string_recursive(args.log, args.args);
      delete args.log;
      console.log("debug log", message, args);
    } else {
      console.log("hidden log", args);
    }
  }

  notif_message_warning(notif: Notif) {
    if (this.bgaAnimationsActive()) {
      var message = this.format_string_recursive(notif.log, notif.args);
      this.bga.dialogs.showMessage(_("Warning:") + " " + message, "info");
    }
  }

  notif_message_info(notif: Notif) {
    if (this.bgaAnimationsActive()) {
      var message = this.format_string_recursive(notif.log, notif.args);
      this.bga.dialogs.showMessage(_("Announcement:") + " " + message, "info");
    }
  }
}
interface NotificationMessage {
  log: string;
  args?: {
    [key: string]: any;
  };
}

/** This is essentically dojo.place but without dojo */
function placeHtml(html: string, parent: ElementOrId, how: InsertPosition = "beforeend") {
  $(parent).insertAdjacentHTML(how, html);
}
function getIntPart(word, i) {
  return parseInt(getPart(word, i));
}
function getPart(word: string, i: number) {
  var arr = word.split("_");
  if (i < 0) i = arr.length + i;
  if (arr.length <= i) return "";
  return arr[i];
}
function getFirstParts(word, count) {
  var arr = word.split("_");
  var res = arr[0];
  for (var i = 1; i < arr.length && i < count; i++) {
    res += "_" + arr[i];
  }
  return res;
}
function getParentParts(word) {
  var arr = word.split("_");
  if (arr.length <= 1) return "";
  return getFirstParts(word, arr.length - 1);
}
