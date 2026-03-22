/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Wayfarers implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

import { Game0Basics } from "./Game0Basics";

declare const dijit: any;

export class HelpMode {
  _helpMode: boolean = false; // help mode where tooltip shown instead of click action
  _displayedTooltip: any = null; // used in help mode
  constructor(private game: Game0Basics) {}

  toggleHelpMode(b) {
    if (b) this.activateHelpMode();
    else this.deactivateHelpMode();
  }

  helpModeHandler = this.onClickForHelp.bind(this);
  closeHelpHandler = this.closeCurrentTooltip.bind(this);

  activateHelpMode() {
    let chk = $("help-mode-switch");
    chk.setAttribute("bchecked", "true");

    this._helpMode = true;
    $("ebd-body").classList.add("help-mode");
    this._displayedTooltip = null;
    document.body.addEventListener("click", this.closeHelpHandler);
    this.game.bga.statusBar.setTitle(_("HELP MODE Activated. Click on game elements to get tooltips"));
    $("generalactions").replaceChildren();
    this.game.addCancelButton(undefined, () => this.deactivateHelpMode());

    document.querySelectorAll(".withtooltip").forEach((node) => {
      node.addEventListener("click", this.helpModeHandler, false);
    });
  }

  deactivateHelpMode() {
    let chk = $("help-mode-switch");
    chk.setAttribute("bchecked", "false");
    this.closeCurrentTooltip();
    this._helpMode = false;
    $("ebd-body").classList.remove("help-mode");
    document.body.removeEventListener("click", this.closeHelpHandler);
    document.querySelectorAll(".withtooltip").forEach((node) => {
      node.removeEventListener("click", this.helpModeHandler, false);
    });
    (gameui as any).on_client_state = true;
    this.game.cancelLocalStateEffects();
  }

  closeCurrentTooltip() {
    if (this._displayedTooltip == null) return;

    this._displayedTooltip.destroy();
    this._displayedTooltip = null;
  }

  onClickForHelp(event) {
    //console.trace("onhelp", event);
    if (!this._helpMode) return false;
    event.stopPropagation();
    event.preventDefault();
    this.showHelp(event.currentTarget.id);
    return true;
  }

  showHelp(id: string, force?: boolean) {
    if (!force) if (!this._helpMode) return false;
    if ((gameui as any).tooltips[id]) {
      dijit.hideTooltip(id);
      var html = (gameui as any).tooltips[id].getContent($(id));
      this._displayedTooltip = this.game.showPopin(html, "current_tooltip");
      return true;
    }
    return false;
  }
}
