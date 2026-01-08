class HelpMode {
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
    this.game.statusBar.setTitle(_("HELP MODE Activated. Click on game elements to get tooltips"));
    dojo.empty("generalactions");
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
    this.game.on_client_state = true;
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
    if (this.game.tooltips[id]) {
      dijit.hideTooltip(id);
      var html = this.game.tooltips[id].getContent($(id));
      this._displayedTooltip = this.game.showPopin(html, "current_tooltip");
      return true;
    }
    return false;
  }
}
