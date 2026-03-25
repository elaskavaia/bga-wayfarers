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
class Game0Basics {
    // proxies for GameGui properties/methods accessed via gameui
    get player_id() {
        return gameui.player_id;
    }
    format_string_recursive(log, args) {
        return gameui.format_string_recursive(log, args);
    }
    addTooltipHtml(nodeId, html, delay) {
        gameui.addTooltipHtml(nodeId, html, delay);
    }
    bgaAnimationsActive() {
        return gameui.bgaAnimationsActive();
    }
    constructor(bga) {
        this.defaultTooltipDelay = 800;
        this.lastMoveId = 0;
        this.prevLogId = 0;
        //console.log("game constructor");
        this.bga = bga;
    }
    setup(gamedatas) {
        this.gamedatas = gamedatas;
        console.log("Starting game setup", gamedatas);
        const first_player_id = Object.keys(gamedatas.players)[0];
        if (!this.bga.players.isCurrentPlayerSpectator())
            this.player_color = gamedatas.players[this.player_id].color;
        else
            this.player_color = gamedatas.players[first_player_id].color;
    }
    // utils
    /**
     * Remove all listed class from all document elements
     * @param classList - list of classes  array
     */
    removeAllClasses(...classList) {
        if (!classList)
            return;
        classList.forEach((className) => {
            document.querySelectorAll(`.${className}`).forEach((node) => {
                node.classList.remove(className);
            });
        });
    }
    onCancel(event) {
        this.cancelLocalStateEffects();
    }
    cancelLocalStateEffects() {
        //console.log(this.last_server_state);
        if (gameui.on_client_state)
            gameui.restoreServerGameState();
        gameui.updatePageTitle();
    }
    destroyDivOtherCopies(id) {
        const panels = document.querySelectorAll("#" + id);
        panels.forEach((p, i) => {
            if (i < panels.length - 1)
                p.parentNode.removeChild(p);
        });
        return panels[0] ?? null;
    }
    setupLocalControls(divId) {
        // undo adds more of these
        this.destroyDivOtherCopies(divId);
        if (this.bga.players.isCurrentPlayerSpectator()) {
            const loc = document.querySelector("#right-side .spectator-mode");
            if (loc)
                loc.insertAdjacentElement("beforeend", $(divId));
        }
        else {
            const loc = document.querySelector("#current_player_board");
            if (loc)
                loc.insertAdjacentElement("beforeend", $(divId));
        }
    }
    addCancelButton(name, handler) {
        if (!name)
            name = _("Cancel");
        if (!handler)
            handler = () => this.onCancel();
        if ($("button_cancel"))
            $("button_cancel").remove();
        this.bga.statusBar.addActionButton(name, handler, { id: "button_cancel", color: "alert" });
    }
    /** Show pop in dialog. If you need div id of dialog its `popin_${id}` where id is second parameter here */
    showPopin(html, id = "gg_dialog", title = undefined, refresh = false) {
        const content_id = `popin_${id}_contents`;
        if (refresh && $(content_id)) {
            $(content_id).innerHTML = html;
            return undefined;
        }
        const dialog = new ebg.popindialog();
        dialog.create(id);
        if (title)
            dialog.setTitle(title);
        dialog.setContent(html);
        dialog.show();
        return dialog;
    }
    getStateName() {
        return this.gamedatas.gamestate.name;
    }
    getPlayerColor(playerId) {
        return this.gamedatas.players[playerId]?.color ?? "ffffff";
    }
    getPlayerName(playerId) {
        return this.gamedatas.players[playerId]?.name ?? _("Not a Player");
    }
    custom_getPlayerIdByColor(color) {
        for (var playerId in this.gamedatas.players) {
            var playerInfo = this.gamedatas.players[playerId];
            if (color == playerInfo.color) {
                return parseInt(playerId);
            }
        }
        return undefined;
    }
    removeTooltip(nodeId) {
        // if (this.tooltips[nodeId])
        if (!nodeId)
            return;
        //console.log("removeTooltip", nodeId);
        $(nodeId)?.classList.remove("withtooltip");
        gameui.removeTooltip(nodeId);
        delete gameui.tooltips[nodeId]; // HACK: removeTooltip leaking this entry, removing manually
    }
    callfn(methodName, ...args) {
        if (this[methodName] !== undefined) {
            console.log("Calling " + methodName, args);
            return this[methodName](...args);
        }
        return undefined;
    }
    /** @Override onScriptError from gameui */
    onScriptError(msg, url, linenumber) {
        if (gameui.page_is_unloading) {
            // Don't report errors during page unloading
            return;
        }
        console.error(msg);
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
    cloneAndFixIds(orig, postfix, removeInlineStyle) {
        if (!$(orig)) {
            const div = document.createElement("div");
            div.innerHTML = _("NOT FOUND") + " " + orig.toString();
            return div;
        }
        const fixIds = function (node) {
            if (node.id) {
                node.id = node.id + postfix;
            }
            if (removeInlineStyle) {
                node.removeAttribute("style");
            }
        };
        const div = $(orig).cloneNode(true);
        div.querySelectorAll("*").forEach(fixIds);
        fixIds(div);
        return div;
    }
    getTr(name, args = {}) {
        if (!name)
            return "";
        if (name.log !== undefined) {
            const notif = name;
            const log = this.format_string_recursive(gameui.clienttranslate_string(notif.log), notif.args);
            return log;
        }
        if (typeof name !== "string")
            return name.toString();
        //if (name.includes("$"))
        {
            const log = this.format_string_recursive(gameui.clienttranslate_string(name), args);
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
    addTooltipToLogItems(log_id) {
        // override
    }
    addMoveToLog(log_id, move_id) {
        if (move_id)
            this.lastMoveId = move_id;
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
        }
        else if (move_id) {
            const tsnode = document.createElement("div");
            tsnode.classList.add("movestamp");
            tsnode.innerHTML = _("Move #") + move_id;
            const lognode = $("log_" + log_id);
            lognode.appendChild(tsnode);
            tsnode.setAttribute("data-move-id", String(move_id));
        }
        this.prevLogId = log_id;
    }
    notif_log(args) {
        // if (notif.log) {
        //   console.log(notif.log, notif.args);
        //   var message = this.format_string_recursive(notif.log, notif.args);
        //   if (message != notif.log) console.log(message);
        // } else {
        if (args.log) {
            var message = this.format_string_recursive(args.log, args.args);
            delete args.log;
            console.log("debug log", message, args);
        }
        else {
            console.log("hidden log", args);
        }
    }
    notif_message_warning(notif) {
        if (gameui.bgaAnimationsActive()) {
            var message = this.format_string_recursive(notif.log, notif.args);
            this.bga.dialogs.showMessage(_("Warning:") + " " + message, "info");
        }
    }
    notif_message_info(notif) {
        if (gameui.bgaAnimationsActive()) {
            var message = this.format_string_recursive(notif.log, notif.args);
            this.bga.dialogs.showMessage(_("Announcement:") + " " + message, "info");
        }
    }
}
/** This is essentically dojo.place but without dojo */
function placeHtml(html, parent, how = "beforeend") {
    $(parent).insertAdjacentHTML(how, html);
}
function getIntPart(word, i) {
    return parseInt(getPart(word, i));
}
function getPart(word, i) {
    var arr = word.split("_");
    if (i < 0)
        i = arr.length + i;
    if (arr.length <= i)
        return "";
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
    if (arr.length <= 1)
        return "";
    return getFirstParts(word, arr.length - 1);
}

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
class LaAnimations {
    constructor() {
        this.defaultAnimationDuration = 500;
    }
    phantomMove(mobileId, newparentId, duration, mobileStyle, onEnd) {
        var mobileNode = $(mobileId);
        if (!mobileNode)
            throw new Error(`Does not exists ${mobileId}`);
        var newparent = $(newparentId);
        if (!newparent)
            throw new Error(`Does not exists ${newparentId}`);
        if (duration === undefined)
            duration = this.defaultAnimationDuration;
        if (!duration || duration < 0)
            duration = 0;
        const noanimation = duration <= 0 || !mobileNode.parentNode;
        const oldParent = mobileNode.parentElement;
        var clone = null;
        if (!noanimation) {
            // do animation
            clone = this.projectOnto(mobileNode, "_temp");
            mobileNode.style.opacity = "0"; // hide original
        }
        const rel = mobileStyle?.relation;
        if (rel) {
            delete mobileStyle.relation;
        }
        if (rel == "first") {
            newparent.insertBefore(mobileNode, null);
        }
        else {
            newparent.appendChild(mobileNode); // move original
        }
        setStyleAttributes(mobileNode, mobileStyle);
        newparent.classList.add("move_target");
        oldParent?.classList.add("move_source");
        mobileNode.offsetHeight; // recalc
        if (noanimation) {
            setTimeout(() => {
                newparent.offsetHeight;
                newparent.classList.remove("move_target");
                oldParent?.classList.remove("move_source");
                if (onEnd)
                    onEnd(mobileNode);
            }, 0);
            return;
        }
        var desti = this.projectOnto(mobileNode, "_temp2"); // invisible destination on top of new parent
        try {
            //setStyleAttributes(desti, mobileStyle);
            clone.style.transitionDuration = duration + "ms";
            clone.style.transitionProperty = "all";
            clone.style.visibility = "visible";
            clone.style.opacity = "1";
            // that will cause animation
            clone.style.left = desti.style.left;
            clone.style.top = desti.style.top;
            clone.style.transform = desti.style.transform;
            // now we don't need destination anymore
            desti.parentNode?.removeChild(desti);
            setTimeout(() => {
                newparent.classList.remove("move_target");
                oldParent?.classList.remove("move_source");
                mobileNode.style.removeProperty("opacity"); // restore visibility of original
                clone.parentNode?.removeChild(clone); // destroy clone
                if (onEnd)
                    onEnd(mobileNode);
            }, duration);
        }
        catch (e) {
            // if bad thing happen we have to clean up clones
            console.error("ERR:C01:animation error", e);
            desti.parentNode?.removeChild(desti);
            clone.parentNode?.removeChild(clone); // destroy clone
            //if (onEnd) onEnd(mobileNode);
        }
    }
    getFulltransformMatrix(from, to) {
        let fullmatrix = "";
        let par = from;
        while (par != to && par != null && par != document.body) {
            var style = window.getComputedStyle(par);
            var matrix = style.transform; //|| "matrix(1,0,0,1,0,0)";
            if (matrix && matrix != "none")
                fullmatrix += " " + matrix;
            par = par.parentNode;
            // console.log("tranform  ",fullmatrix,par);
        }
        return fullmatrix;
    }
    projectOnto(from, postfix, ontoWhat) {
        const elem = $(from);
        let over;
        if (ontoWhat)
            over = $(ontoWhat);
        else
            over = $("oversurface"); // this div has to exists with pointer-events: none and cover all area with high zIndex
        var elemRect = elem.getBoundingClientRect();
        //console.log("elemRect", elemRect);
        var newId = elem.id + postfix;
        var old = $(newId);
        if (old)
            old.parentNode.removeChild(old);
        var clone = elem.cloneNode(true);
        clone.id = newId;
        clone.classList.add("phantom");
        clone.classList.add("phantom" + postfix);
        clone.style.transitionDuration = "0ms"; // disable animation during projection
        var fullmatrix = this.getFulltransformMatrix(elem.parentNode, over.parentNode);
        // Calculate the scale factor of oversurface relative to viewport
        // This handles cases where oversurface or its ancestors are scaled
        const overElement = over;
        const overRect = over.getBoundingClientRect();
        const scaleX = overElement.offsetWidth > 0 ? overRect.width / overElement.offsetWidth : 1;
        const scaleY = overElement.offsetHeight > 0 ? overRect.height / overElement.offsetHeight : 1;
        // Set dimensions adjusted for scale so clone appears same visual size as original
        if (elemRect.width > 1) {
            clone.style.width = elemRect.width / scaleX + "px";
            clone.style.height = elemRect.height / scaleY + "px";
        }
        // Set initial position before appending so we measure from a known baseline
        clone.style.position = "absolute";
        clone.style.left = "0px";
        clone.style.top = "0px";
        over.appendChild(clone);
        var cloneRect = clone.getBoundingClientRect();
        const centerY = elemRect.y + elemRect.height / 2;
        const centerX = elemRect.x + elemRect.width / 2;
        // centerX/Y is where the center point must be
        // I need to calculate the offset from top and left
        // Therefore I remove half of the dimensions + the existing offset
        const offsetX = centerX - cloneRect.width / 2 - cloneRect.x;
        const offsetY = centerY - cloneRect.height / 2 - cloneRect.y;
        // Then remove the clone's parent position (since left/top is from the parent)
        // Divide by scale factor to convert from viewport pixels to CSS pixels
        clone.style.left = offsetX / scaleX + "px";
        clone.style.top = offsetY / scaleY + "px";
        clone.style.transform = fullmatrix;
        clone.style.transitionDuration = undefined;
        return clone;
    }
    /**
     * Pulse an element: scale up then back to normal size.
     * If called again while already pulsing, queues the next pulse after the current one.
     */
    pulse(targetId, scale = 2, duration = 400) {
        const node = $(targetId);
        if (!node)
            return;
        const pending = Number(node.dataset.pulseQueue || 0);
        if (pending > 0) {
            node.dataset.pulseQueue = String(pending + 1);
            return;
        }
        node.dataset.pulseQueue = "1";
        this.doPulse(node, scale, duration);
    }
    doPulse(node, scale, duration) {
        const half = duration / 2;
        node.style.transitionDuration = half + "ms";
        node.style.transitionProperty = "transform";
        node.style.transitionTimingFunction = "ease-out";
        node.offsetHeight;
        node.style.transform = `scale(${scale})`;
        setTimeout(() => {
            node.style.transitionTimingFunction = "ease-in";
            node.style.transform = "";
            setTimeout(() => {
                const remaining = Number(node.dataset.pulseQueue || 0) - 1;
                if (remaining > 0) {
                    node.dataset.pulseQueue = String(remaining);
                    this.doPulse(node, scale, duration);
                }
                else {
                    delete node.dataset.pulseQueue;
                    node.style.removeProperty("transition-duration");
                    node.style.removeProperty("transition-property");
                    node.style.removeProperty("transition-timing-function");
                }
            }, half);
        }, half);
    }
    /**
     * Clone an element, position it over a target, then float up and fade out.
     * The original element is not affected.
     */
    evaporate(mobileId, targetId, duration) {
        const mobileNode = $(mobileId);
        const targetNode = $(targetId);
        if (!mobileNode || !targetNode)
            return;
        if (duration === undefined)
            duration = 1200;
        // Project a clone of the target to get its position on oversurface
        const targetClone = this.projectOnto(targetNode, "_evap_dest");
        const targetLeft = targetClone.style.left;
        const targetTop = targetClone.style.top;
        targetClone.remove();
        // Project a clone of the mobile onto oversurface
        const clone = this.projectOnto(mobileNode, "_evap");
        // Reposition clone over the target (centered horizontally, above vertically)
        clone.style.left = targetLeft;
        clone.style.top = targetTop;
        clone.style.pointerEvents = "none";
        clone.offsetHeight; // force reflow
        // Animate: float up + fade out
        clone.style.transitionDuration = duration + "ms";
        clone.style.transitionProperty = "opacity, transform";
        clone.style.transitionTimingFunction = "ease-out";
        clone.offsetHeight; // force reflow
        clone.style.opacity = "0";
        clone.style.transform = (clone.style.transform || "") + " translateY(-60px) scale(1.3)";
        setTimeout(() => clone.remove(), duration);
    }
    /**
     * Shrink and fade an element in place.
     * The element is hidden (opacity 0) during the animation; a clone performs the visual effect.
     */
    shrinkAndFade(mobileId, duration) {
        const mobileNode = $(mobileId);
        if (!mobileNode)
            return Promise.resolve();
        if (duration === undefined)
            duration = 600;
        const clone = this.projectOnto(mobileNode, "_shrink");
        clone.style.pointerEvents = "none";
        mobileNode.style.opacity = "0";
        clone.offsetHeight; // force reflow
        clone.style.transitionDuration = duration + "ms";
        clone.style.transitionProperty = "opacity, transform";
        clone.style.transitionTimingFunction = "ease-in";
        clone.offsetHeight; // force reflow
        clone.style.opacity = "0";
        clone.style.transform = (clone.style.transform || "") + " scale(0)";
        return new Promise((resolve) => {
            setTimeout(() => {
                clone.remove();
                mobileNode.style.removeProperty("opacity");
                resolve();
            }, duration);
        });
    }
    cardFlip(mobileId, newState, duration, onEnd) {
        var mobileNode = $(mobileId);
        if (!mobileNode)
            throw new Error(`Does not exists ${mobileId}`);
        if (duration === undefined)
            duration = this.defaultAnimationDuration;
        if (!duration || duration < 0)
            duration = 0;
        const noanimation = duration <= 0 || !mobileNode.parentNode;
        if (noanimation) {
            mobileNode.dataset.state = newState;
            setTimeout(() => {
                if (onEnd)
                    onEnd(mobileNode);
            }, 0);
            return;
        }
        const clone = this.projectOnto(mobileNode, "_temp");
        clone.innerHTML = "";
        mobileNode.dataset.state = newState;
        mobileNode.offsetHeight; // recalc
        const desti = this.projectOnto(mobileNode, "_temp2"); // invisible destination on top of new parent
        desti.innerHTML = "";
        mobileNode.style.opacity = "0"; // hide original
        placeHtml(`<div id="card_temp"></div>`, "oversurface");
        const group = $("card_temp");
        group.style.left = clone.style.left;
        group.style.top = clone.style.top;
        group.style.transform = clone.style.transform;
        group.style.width = clone.style.width;
        group.style.height = clone.style.height;
        group.style.position = "absolute";
        group.style.transformStyle = "preserve-3d";
        group.style.transitionProperty = "all";
        group.appendChild(clone);
        group.appendChild(desti);
        delete clone.style.left;
        delete clone.style.top;
        delete desti.style.left;
        delete desti.style.top;
        desti.style.transform = "rotateY(180deg)";
        desti.style.backfaceVisibility = "hidden";
        clone.style.backfaceVisibility = "hidden";
        try {
            //setStyleAttributes(desti, mobileStyle);
            group.style.transitionDuration = duration + "ms";
            //group.style.visibility = "visible";
            //group.style.opacity = "1";
            // that will cause animation
            //group.style.scale = "2.0";
            group.style.animation = `flip ${duration}ms`;
            setTimeout(() => {
                mobileNode.style.removeProperty("opacity"); // restore visibility of original
                group.remove();
                if (onEnd)
                    onEnd(mobileNode);
            }, duration);
        }
        catch (e) {
            // if bad thing happen we have to clean up clones
            console.error("ERR:C01:animation error", e);
            group.remove();
            if (onEnd)
                onEnd(mobileNode);
        }
    }
}
function setStyleAttributes(element, attrs) {
    if (attrs !== undefined) {
        Object.keys(attrs).forEach((key) => {
            element.style.setProperty(key, attrs[key]);
        });
    }
}

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
const BgaAnimations = (await importEsmLib("bga-animations", "1.x"));
const BgaScoreSheet = (await importEsmLib("bga-score-sheet", "1.x"));

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
class Game1Tokens extends Game0Basics {
    constructor() {
        super(...arguments);
        this.globlog = 1;
        this.tokenInfoCache = {};
        this.defaultAnimationDuration = 500;
        this.classActiveSlot = "active_slot";
        this.classActiveSlotHidden = "hidden_active_slot";
        this.classButtonDisabled = "disabled";
        this.classSelected = "gg_selected"; // for the purpose of multi-select operations
        this.classSelectedAlt = "gg_selected_alt"; // for the purpose of multi-select operations with alternative node
        this.game = this;
    }
    setupGame(gamedatas) {
        this.tokenInfoCache = {};
        // create the animation manager, and bind it to the `game.bgaAnimationsActive()` function
        this.animationManager = new BgaAnimations.Manager({
            animationsActive: () => this.bgaAnimationsActive()
        });
        this.animationLa = new LaAnimations();
        if (!this.gamedatas.tokens) {
            console.error("Missing gamadatas.tokens!");
            this.gamedatas.tokens = {};
        }
        if (!this.gamedatas.token_types) {
            console.error("Missing gamadatas.token_types!");
            this.gamedatas.token_types = {};
        }
        this.gamedatas.tokens["limbo"] = {
            key: "limbo",
            state: 0,
            location: "thething"
        };
        this.placeTokenSetup("limbo");
        placeHtml(`<div id="oversurface"></div>`, this.bga.gameArea.getElement());
        this.setupTokens();
        this.updateCountersSafe(this.gamedatas.counters);
    }
    onLeavingState(stateName, args) {
        console.log("onLeavingState: " + stateName);
        //this.disconnectAllTemp();
        this.removeAllClasses(this.classActiveSlot, this.classActiveSlotHidden);
        if (!gameui.on_client_state) {
            this.removeAllClasses(this.classSelected, this.classSelectedAlt);
        }
        //super.onLeavingState(stateName);
    }
    cancelLocalStateEffects() {
        //console.log(this.last_server_state);
        this.game.removeAllClasses(this.classActiveSlot, this.classActiveSlotHidden);
        this.game.removeAllClasses(this.classSelected, this.classSelectedAlt);
        //this.restoreServerData();
        //this.updateCountersSafe(this.gamedatas.counters);
    }
    addShowMeButton(scroll) {
        const firstTarget = document.querySelector("." + this.classActiveSlot);
        if (!firstTarget)
            return;
        this.bga.statusBar.addActionButton(_("Show me"), () => {
            const butt = $("button_showme");
            const firstTarget = document.querySelector("." + this.classActiveSlot);
            if (!firstTarget)
                return;
            if (scroll)
                $(firstTarget).scrollIntoView({ behavior: "smooth", block: "center" });
            document.querySelectorAll("." + this.classActiveSlot).forEach((node) => {
                const elem = node;
                elem.style.removeProperty("animation");
                elem.style.setProperty("animation", "active-pulse 500ms 3");
                butt.classList.add(this.classButtonDisabled);
                setTimeout(() => {
                    elem.style.removeProperty("animation");
                    butt.classList.remove(this.classButtonDisabled);
                }, 1500);
            });
        }, {
            color: "secondary",
            id: "button_showme"
        });
    }
    getAllLocations() {
        const res = [];
        for (const key in this.gamedatas.token_types) {
            const info = this.gamedatas.token_types[key];
            if (this.isLocationByType(key) && info.scope != "player")
                res.push(key);
        }
        for (var token in this.gamedatas.tokens) {
            var tokenInfo = this.gamedatas.tokens[token];
            var location = tokenInfo.location;
            if (location && res.indexOf(location) < 0)
                res.push(location);
        }
        return res;
    }
    isLocationByType(id) {
        return this.getRulesFor(id, "type", "").indexOf("location") >= 0;
    }
    updateCountersSafe(counters) {
        //console.log(counters);
        for (var key in counters) {
            let node = $(key);
            if (counters.hasOwnProperty(key)) {
                if (!node) {
                    const deckId = key.replace("counter_", "");
                    if ($(deckId)) {
                        placeHtml(`<div id='${key}' class='counter'></div>`, deckId);
                        node = $(key);
                    }
                }
                if (node) {
                    const value = counters[key].value;
                    node.dataset.state = value;
                }
                else {
                    console.log("unknown counter " + key);
                }
            }
        }
    }
    setupTokens() {
        console.log("Setup tokens");
        for (let loc of this.getAllLocations()) {
            this.placeTokenSetup(loc);
        }
        for (let token in this.gamedatas.tokens) {
            const tokenInfo = this.gamedatas.tokens[token];
            const location = tokenInfo.location;
            if (location && !this.gamedatas.tokens[location] && !$(location)) {
                this.placeTokenSetup(location);
            }
            this.placeTokenSetup(token);
        }
        for (let token in this.gamedatas.tokens) {
            this.updateTooltip(token);
        }
        for (let loc of this.getAllLocations()) {
            this.updateTooltip(loc);
        }
    }
    setTokenInfo(token_id, place_id, new_state, serverdata, args) {
        var token = token_id;
        if (!this.gamedatas.tokens[token]) {
            this.gamedatas.tokens[token] = {
                key: token,
                state: 0,
                location: "limbo"
            };
        }
        if (args) {
            args._prev = structuredClone(this.gamedatas.tokens[token]);
        }
        if (place_id !== undefined) {
            this.gamedatas.tokens[token].location = place_id;
        }
        if (new_state !== undefined) {
            this.gamedatas.tokens[token].state = new_state;
        }
        //if (serverdata === undefined) serverdata = true;
        //if (serverdata && this.gamedatas_server) this.gamedatas_server.tokens[token] = dojo.clone(this.gamedatas.tokens[token]);
        return this.gamedatas.tokens[token];
    }
    createToken(placeInfo) {
        const tokenId = placeInfo.key;
        const location = placeInfo.place_from ?? placeInfo.location ?? this.getRulesFor(tokenId, "location");
        const div = document.createElement("div");
        div.id = tokenId;
        let parentNode = $(location);
        if (location && !parentNode) {
            if (location.indexOf("{") == -1)
                console.error("Cannot find location [" + location + "] for ", div);
            parentNode = $("limbo");
        }
        parentNode.appendChild(div);
        return div;
    }
    updateToken(tokenNode, placeInfo) {
        const tokenId = placeInfo.key;
        const displayInfo = this.getTokenDisplayInfo(tokenId);
        const classes = displayInfo.imageTypes.split(/  */);
        tokenNode.classList.add(...classes);
        if (displayInfo.name)
            tokenNode.dataset.name = this.getTr(displayInfo.name);
        if (displayInfo.tc)
            tokenNode.dataset.tc = displayInfo.tc;
        this.addListenerWithGuard(tokenNode, placeInfo.onClick);
    }
    addListenerWithGuard(tokenNode, handler) {
        if (!tokenNode.getAttribute("_lis") && handler) {
            tokenNode.addEventListener("click", handler);
            tokenNode.setAttribute("_lis", "1");
        }
    }
    findActiveParent(element) {
        if (this.isActiveSlot(element))
            return element;
        const parent = element.parentElement;
        if (!parent || parent.id == "thething" || parent == element)
            return null;
        return this.findActiveParent(parent);
    }
    /**
     * This is convenient function to be called when processing click events, it - remembers id of object - stops propagation - logs to
     * console - the if checkActive is set to true check if element has active_slot class
     */
    onClickSanity(event, checkActiveSlot, checkActivePlayer) {
        let id = event.currentTarget.id;
        let target = event.target;
        if (id == "thething") {
            let node = this.findActiveParent(target);
            id = node?.id;
            target = node;
        }
        console.log("on slot " + id, target?.id || target);
        if (!id)
            return null;
        if (this.showHelp(id))
            return null;
        if (checkActiveSlot && !id.startsWith("button_") && !this.checkActiveSlot(id)) {
            return null;
        }
        if (checkActivePlayer && !this.checkActivePlayer()) {
            return null;
        }
        if (target.dataset.targetId)
            return target.dataset.targetId;
        id = id.replace("tmp_", "");
        id = id.replace("button_", "");
        return id;
    }
    // override to hook the help
    showHelp(id) {
        return false;
    }
    // override to prove additinal animation parameters
    getPlaceRedirect(tokenInfo, args = {}) {
        return tokenInfo;
    }
    checkActivePlayer() {
        if (!this.bga.players.isCurrentPlayerActive()) {
            this.bga.dialogs.showMessage(_("This is not your turn"), "error");
            return false;
        }
        return true;
    }
    isActiveSlot(id) {
        const node = $(id);
        if (node.classList.contains(this.classActiveSlot)) {
            return true;
        }
        if (node.classList.contains(this.classActiveSlotHidden)) {
            return true;
        }
        return false;
    }
    checkActiveSlot(id, showError = true) {
        if (!this.isActiveSlot(id)) {
            if (showError) {
                console.error(new Error("unauth"), id);
                this.bga.dialogs.showMoveUnauthorized();
            }
            return false;
        }
        return true;
    }
    async placeTokenServer(tokenId, location, state, args) {
        const tokenInfo = this.setTokenInfo(tokenId, location, state, true, args);
        await this.placeToken(tokenId, tokenInfo, args);
        this.updateTooltip(tokenId, undefined, { force: true });
        this.updateTooltip(tokenInfo.location, undefined, { force: true });
    }
    prapareToken(tokenId, tokenDbInfo, args = {}) {
        if (!tokenDbInfo) {
            tokenDbInfo = this.gamedatas.tokens[tokenId];
        }
        if (!tokenDbInfo) {
            let tokenNode = $(tokenId);
            if (tokenNode) {
                const st = parseInt(tokenNode.dataset.state);
                tokenDbInfo = this.setTokenInfo(tokenId, tokenNode.parentElement.id, st, false);
            }
            else {
                //console.error("Cannot setup token for " + tokenId);
                tokenDbInfo = this.setTokenInfo(tokenId, undefined, 0, false);
            }
        }
        const placeInfo = this.getPlaceRedirect(tokenDbInfo, args);
        const tokenNode = $(tokenId) ?? this.createToken(placeInfo);
        tokenNode.dataset.state = String(tokenDbInfo.state);
        tokenNode.dataset.location = tokenDbInfo.location;
        this.updateToken(tokenNode, placeInfo);
        // no movement
        if (placeInfo.nop) {
            return placeInfo;
        }
        const location = placeInfo.location;
        if (!$(location)) {
            if (location)
                console.error(`Unknown place ${location} for ${tokenId}`);
            return undefined;
        }
        return placeInfo;
    }
    placeTokenSetup(tokenId, tokenDbInfo) {
        const placeInfo = this.prapareToken(tokenId, tokenDbInfo);
        if (!placeInfo) {
            return;
        }
        const tokenNode = $(tokenId);
        if (!tokenNode)
            return;
        void placeInfo.onStart?.(tokenNode);
        if (placeInfo.nop) {
            return;
        }
        $(placeInfo.location).appendChild(tokenNode);
        void placeInfo.onEnd?.(tokenNode);
    }
    async placeToken(tokenId, tokenDbInfo, args = {}) {
        try {
            const placeInfo = this.prapareToken(tokenId, tokenDbInfo, args);
            if (!placeInfo) {
                return;
            }
            const tokenNode = $(tokenId);
            let animTime = placeInfo.animtime ?? this.defaultAnimationDuration;
            if (this.game.bgaAnimationsActive() == false || args.noa || placeInfo.noa || placeInfo.animtime === 0 || !tokenNode.parentNode) {
                animTime = 0;
            }
            if (placeInfo.onStart)
                await placeInfo.onStart(tokenNode);
            if (!placeInfo.nop)
                await this.slideAndPlace(tokenNode, placeInfo.location, animTime, 0, undefined, placeInfo.onEnd);
            else
                placeInfo.onEnd?.(tokenNode);
            //if (animTime == 0) $(location).appendChild(tokenNode);
            //else void this.animationManager.slideAndAttach(tokenNode, $(location));
        }
        catch (e) {
            console.error("Exception thrown", e, e.stack);
            // this.showMessage(token + " -> FAILED -> " + place + "\n" + e, "error");
        }
    }
    updateTooltip(tokenId, attachTo, options = {}) {
        if (attachTo === undefined) {
            attachTo = tokenId;
        }
        let attachNode = $(attachTo);
        if (!attachNode)
            return;
        // attach node has to have id
        if (!attachNode.id)
            attachNode.id = "gen_id_" + Math.random() * 10000000;
        // console.log("tooltips for "+token);
        if (typeof tokenId != "string") {
            console.error("cannot calc tooltip" + tokenId);
            return;
        }
        var tokenInfo = this.getTokenDisplayInfo(tokenId, options.force);
        if (tokenInfo.name) {
            attachNode.dataset.name = this.game.getTr(tokenInfo.name);
        }
        if (tokenInfo.showtooltip == false) {
            return;
        }
        if (tokenInfo.title) {
            attachNode.setAttribute("title", this.game.getTr(tokenInfo.title));
            return;
        }
        if (!tokenInfo.tooltip && !tokenInfo.name) {
            return;
        }
        var main = this.getTooltipHtmlForTokenInfo(tokenInfo);
        if (main) {
            attachNode.classList.add("withtooltip");
            if (attachNode.id != tokenId)
                attachNode.dataset.tt = tokenId; // id of token that provides the tooltip
            //console.log("addTooltipHtml", attachNode.id);
            this.game.addTooltipHtml(attachNode.id, main, options.delay ?? this.game.defaultTooltipDelay);
            attachNode.removeAttribute("title"); // unset title so both title and tooltip do not show up
            this.handleStackedTooltips(attachNode);
        }
        else {
            attachNode.classList.remove("withtooltip");
        }
    }
    handleStackedTooltips(attachNode) { }
    getTooltipHtmlForToken(token) {
        if (typeof token != "string") {
            console.error("cannot calc tooltip" + token);
            return null;
        }
        var tokenInfo = this.getTokenDisplayInfo(token, true);
        // console.log(tokenInfo);
        if (!tokenInfo)
            return;
        return this.getTooltipHtmlForTokenInfo(tokenInfo);
    }
    getTooltipHtmlForTokenInfo(tokenInfo) {
        return this.getTooltipHtml(tokenInfo.name, tokenInfo.tooltip, tokenInfo.imageTypes, tokenInfo.reverseImageTypes, tokenInfo.imageData);
    }
    getTokenName(tokenId, force = true) {
        var tokenInfo = this.getTokenDisplayInfo(tokenId);
        if (tokenInfo) {
            return this.game.getTr(tokenInfo.name);
        }
        else {
            if (!force)
                return undefined;
            return "? " + tokenId;
        }
    }
    getTooltipHtml(name, message, imgTypes = "", reverseImgTypes = "", imageData) {
        if (name == null || message == "-")
            return "";
        if (!message)
            message = "";
        var divImg = "";
        var containerType = "tooltipcontainer ";
        if (imgTypes && !imgTypes.includes("_nottimage")) {
            // Check if this is a dual-image tooltip (upgrade tiles with front and reverse)
            if (imgTypes.includes("_dual_image") && reverseImgTypes) {
                const frontImgTypes = imgTypes.replace("_dual_image", "").trim();
                divImg = `
          <div class='tooltipimage ${frontImgTypes}'></div>
          <div class='tooltipimage ${reverseImgTypes}'></div>
        `;
            }
            else {
                const dataAttrs = imageData
                    ? Object.entries(imageData)
                        .map(([k, v]) => `data-${k}="${v}"`)
                        .join(" ")
                    : "";
                divImg = `<div class='tooltipimage ${imgTypes}' ${dataAttrs}></div>`;
            }
            var itypes = imgTypes.split(" ");
            for (var i = 0; i < itypes.length; i++) {
                containerType += itypes[i] + "_tooltipcontainer ";
            }
        }
        const name_tr = this.game.getTr(name);
        let body = "";
        if (imgTypes.includes("_override")) {
            body = message;
        }
        else {
            const message_tr = this.game.getTr(message);
            body = `
           <div class='tooltip-left'>${divImg}</div>
           <div class='tooltip-right'>
             <div class='tooltiptitle'>${name_tr}</div>
             <div class='tooltiptext'>${message_tr}</div>
           </div>
    `;
        }
        return `<div class='${containerType}'>
        <div class='tooltip-body'>${body}</div>
    </div>`;
    }
    getTokenState(tokenId) {
        var tokenInfo = this.gamedatas.tokens[tokenId];
        return Number(tokenInfo?.state);
    }
    getTokenLocation(tokenId) {
        var tokenInfo = this.gamedatas.tokens[tokenId];
        return tokenInfo?.location;
    }
    getAllRules(tokenId) {
        return this.getRulesFor(tokenId, "*", null);
    }
    getRulesFor(tokenId, field, def) {
        if (field === undefined)
            field = "r";
        var key = tokenId;
        let chain = [key];
        while (key) {
            var info = this.gamedatas.token_types[key];
            if (info === undefined) {
                key = getParentParts(key);
                if (!key) {
                    //console.error("Undefined info for " + tokenId);
                    return def;
                }
                chain.push(key);
                continue;
            }
            if (field === "*") {
                info["_chain"] = chain.join(" ");
                return info;
            }
            var rule = info[field];
            if (rule === undefined)
                return def;
            return rule;
        }
        return def;
    }
    getTokenDisplayInfo(tokenId, force = false) {
        tokenId = String(tokenId);
        const cache = this.tokenInfoCache[tokenId];
        if (!force && cache) {
            return cache;
        }
        let tokenInfo = this.getAllRules(tokenId);
        if (!tokenInfo) {
            tokenInfo = {
                key: tokenId,
                _chain: tokenId,
                name: tokenId,
                showtooltip: false
            };
        }
        else {
            tokenInfo = structuredClone(tokenInfo);
        }
        const imageTypes = tokenInfo._chain ?? tokenId ?? "";
        const ita = imageTypes.split(" ");
        const tokenKey = ita[ita.length - 1];
        const parentParts = getParentParts(tokenId);
        tokenInfo.type ?? (tokenInfo.type = this.getRulesFor(parentParts, "type", "token"));
        const declaredTypes = tokenInfo.type;
        tokenInfo.typeKey = tokenKey; // this is key in token_types structure
        tokenInfo.mainType = getPart(tokenId, 0); // first type
        tokenInfo.imageTypes = `${tokenInfo.mainType} ${declaredTypes} ${imageTypes}`.trim(); // other types used for div
        tokenInfo.location ?? (tokenInfo.location = this.getRulesFor(parentParts, "location", undefined));
        const create = tokenInfo.create ?? 0;
        if (create == 3 || create == 4) {
            const prefix = tokenKey.split("_").length;
            tokenInfo.color = getPart(tokenId, prefix);
            tokenInfo.imageTypes += " color_" + tokenInfo.color;
        }
        if (create == 3) {
            const part = getPart(tokenId, -1);
            tokenInfo.imageTypes += " n_" + part;
        }
        if (!tokenInfo.key) {
            tokenInfo.key = tokenId;
        }
        tokenInfo.tokenId = tokenId;
        try {
            this.updateTokenDisplayInfo(tokenInfo);
        }
        catch (e) {
            console.error(`Failed to update token info for ${tokenId}`, e);
        }
        this.tokenInfoCache[tokenId] = tokenInfo;
        //console.log("cached", tokenId);
        return tokenInfo;
    }
    getTokenPresentaton(type, tokenKey, args = {}) {
        if (type.includes("_div"))
            return this.createTokenImage(tokenKey);
        if (tokenKey.includes("wicon"))
            return this.createTokenImage(tokenKey);
        return this.getTokenName(tokenKey);
    }
    // override to generate dynamic tooltips and such
    updateTokenDisplayInfo(tokenDisplayInfo) { }
    ttSection(prefix, text) {
        if (prefix)
            return `<p><b>${prefix}</b>: ${text}</p>`;
        else
            return `<p>${text}</p>`;
    }
    iiSection(text) {
        return `<p><i>${text}</i></p>`;
    }
    createTokenImage(tokenId, state = 0) {
        const div = document.createElement("div");
        div.id = tokenId + "_tt_" + this.globlog++;
        this.updateToken(div, { key: tokenId, location: "log", state });
        div.title = this.getTokenName(tokenId, false) ?? "";
        return div.outerHTML;
    }
    isMarkedForTranslation(key, args) {
        if (!args.i18n) {
            return false;
        }
        else {
            var i = args.i18n.indexOf(key);
            if (i >= 0) {
                return true;
            }
        }
        return false;
    }
    bgaFormatText(log, args) {
        try {
            if (log && args) {
                // if adding key here and it ends with _name make sure also exclude from rtr in dbSetTokenLocation
                var keys = [
                    "token_name",
                    "token2_name",
                    "token_divs",
                    "token_names",
                    "place_name",
                    "card_type_name",
                    "token_div",
                    "token2_div",
                    "token3_div",
                    "token_icon",
                    "place_from_name"
                ];
                for (var i in keys) {
                    const key = keys[i];
                    // console.log("checking " + key + " for " + log);
                    if (args[key] === undefined)
                        continue;
                    const arg_value = args[key];
                    if (key == "token_divs" || key == "token_names") {
                        var list = args[key].split(",");
                        var res = "";
                        for (let l = 0; l < list.length; l++) {
                            const value = list[l];
                            if (l > 0)
                                res += ", ";
                            res += this.getTokenPresentaton(key, value, args);
                        }
                        res = res.trim();
                        if (res)
                            args[key] = res;
                        continue;
                    }
                    var res = this.getTokenPresentaton(key, arg_value, args);
                    if (res)
                        args[key] = res;
                }
            }
        }
        catch (e) {
            console.error(log, args, "Exception thrown", e.stack);
        }
        return { log, args };
    }
    async slideAndPlace(token, finalPlace, duration, delay = 0, mobileStyle, onEnd) {
        if (!$(token))
            console.error(`token not found for ${token}`);
        if ($(token)?.parentNode == $(finalPlace))
            return;
        if (gameui.bgaAnimationsActive() == false) {
            duration = 0;
            delay = 0;
        }
        if (delay)
            await gameui.wait(delay);
        this.animationLa.phantomMove(token, finalPlace, duration, mobileStyle, onEnd);
        return gameui.wait(duration);
    }
    async notif_animate(args) {
        return gameui.wait(args.time ?? 1);
    }
    async notif_tokenMovedAsync(args) {
        void this.notif_tokenMoved(args);
    }
    async notif_tokenMoved(args) {
        if (args.list !== undefined) {
            // move bunch of tokens
            const moves = [];
            for (var i = 0; i < args.list.length; i++) {
                var one = args.list[i];
                var new_state = args.new_state;
                if (new_state === undefined) {
                    if (args.new_states !== undefined && args.new_states.length > i) {
                        new_state = args.new_states[i];
                    }
                }
                moves.push(this.placeTokenServer(one, args.place_id, new_state, args));
            }
            return Promise.all(moves);
        }
        else {
            return this.placeTokenServer(args.token_id, args.place_id, args.new_state, args);
        }
    }
    async notif_counterAsync(args) {
        void this.notif_counter(args);
    }
    /**
     *
     * name: the name of the counter
  value: the new value
  oldValue: the value before the update
  inc: the increment
  absInc: the absolute value of the increment, allowing you to use '...loses ${absInc} ...' in the notif message if you are incrementing with a negative value
  playerId (only for PlayerCounter)
  player_name (only for PlayerCounter)
     * @param args
     * @returns
     *
     */
    async notif_counter(args) {
        try {
            const name = args.name;
            const value = args.value;
            const node = $(name);
            if (node && this.gamedatas.tokens[name]) {
                args.nop = true; // no move animation
                return Promise.all([this.placeTokenServer(name, this.gamedatas.tokens[name].location, value, args), gameui.wait(500)]);
            }
            else if (node) {
                node.dataset.state = value;
            }
        }
        catch (ex) {
            console.error("Cannot update " + args.counter_name, ex, ex.stack);
        }
        return gameui.wait(500);
    }
}

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
/**  Generic processing related to Operation Machine */
class GameMachine extends Game1Tokens {
    onEnteringState_PlayerTurn(opInfo) {
        if (!this.bga.players.isCurrentPlayerActive()) {
            if (opInfo?.description)
                this.bga.statusBar.setTitle(this.getTr(opInfo.description, opInfo));
            this.setSubPrompt("");
            this.addUndoButton(opInfo.ui?.undo);
            return;
        }
        this.completeOpInfo(opInfo);
        this.opInfo = opInfo;
        if (opInfo.prompt) {
            this.bga.statusBar.setTitle(this.getTr(opInfo.prompt, opInfo));
        }
        if (opInfo.err) {
            this.setSubPrompt(_("Error") + " " + this.getTr(opInfo.err, opInfo));
        }
        else if (opInfo.subtitle)
            this.setSubPrompt(this.getTr(opInfo.subtitle, opInfo), opInfo);
        else if (opInfo.data?.reason)
            this.setSubPrompt(this.getReasonText(opInfo.data.reason));
        const multiselect = this.isMultiSelectArgs(opInfo);
        const sortedTargets = Object.keys(opInfo.info);
        sortedTargets.sort((a, b) => opInfo.info[a].o - opInfo.info[b].o);
        for (const target of sortedTargets) {
            const paramInfo = opInfo.info[target];
            if (paramInfo.sec) {
                continue; // secondary buttons
            }
            const div = $(target);
            const q = paramInfo.q;
            const active = q == 0;
            // simple case we select element (dom node) which is target of operation
            if (div && active && paramInfo.noactive !== true) {
                const doNotShowActive = paramInfo.noactive ?? opInfo.ui.noactive ?? false;
                if (doNotShowActive == false) {
                    div.classList.add(this.classActiveSlot);
                    div.dataset.targetOpType = opInfo.type;
                }
            }
            // we also can have one addition way of selection (possibly)
            let altNode;
            if (opInfo.ui.replicate == true) {
                altNode = this.replicateTargetOnSelectionArea(target, paramInfo);
            }
            if (opInfo.ui.imagebuttons == true) {
                altNode = this.replicateTargetOnToolbar(target, paramInfo);
            }
            if (!altNode && (opInfo.ui.buttons || !div || paramInfo.buttons)) {
                altNode = this.createTargetButton(target, paramInfo);
            }
            if (!altNode)
                continue;
            altNode.dataset.targetId = target;
            altNode.dataset.targetOpType = opInfo.type;
            if (!active) {
                altNode.title = this.getTr(paramInfo.err ?? _("Operation cannot be performed now"), paramInfo);
                altNode.classList.add(this.classButtonDisabled);
            }
            else {
                const title = paramInfo.tooltip;
                if (title)
                    altNode.title = this.getTr(title, paramInfo);
                else
                    this.updateTooltip(target, altNode);
            }
            if (paramInfo.max !== undefined) {
                altNode.dataset.max = String(paramInfo.max);
            }
            else {
                altNode.dataset.max = "1";
            }
        }
        if (opInfo.ui.buttons == false || opInfo.ui.replicate) {
            this.addShowMeButton(true);
        }
        // secondary buttons
        for (const target of sortedTargets) {
            const paramInfo = opInfo.info[target];
            if (paramInfo.sec) {
                // skip, whatever TODO: anytime
                const color = paramInfo.color ?? "secondary";
                const call = paramInfo.call ?? target;
                const button = this.bga.statusBar.addActionButton(this.getTargetButtonName(target, paramInfo), () => this.bga.actions.performAction(`action_${call}`, {
                    data: JSON.stringify({ target })
                }), {
                    color: color,
                    id: "button_" + target,
                    confirm: this.getTr(paramInfo.confirm)
                });
                button.dataset.targetId = target;
            }
        }
        if (multiselect) {
            this.activateMultiSelectPrompt(opInfo);
        }
        // need a global condition when this can be added
        this.addUndoButton(this.bga.players.isCurrentPlayerActive() || opInfo.ui.undo);
    }
    createTargetButton(target, paramInfo) {
        const q = paramInfo.q;
        const active = q == 0;
        const color = paramInfo.color ?? this.opInfo.ui.color;
        const button = this.bga.statusBar.addActionButton(this.getTargetButtonName(target, paramInfo), (event) => this.onToken(event), {
            color: color,
            disabled: !active,
            id: "button_" + target
        });
        return button;
    }
    replicateTargetOnToolbar(target, paramInfo) {
        const q = paramInfo.q;
        const active = q == 0;
        const color = paramInfo.color ?? "secondary";
        const div = $(target);
        let cloneHtml = this.createCustomButtonImageHtml(target, paramInfo);
        if (!cloneHtml && div) {
            const clone = div.cloneNode(true);
            clone.id = target + "_temp";
            clone.classList.remove(this.classActiveSlot);
            clone.classList.add(this.classActiveSlotHidden);
            cloneHtml = clone.outerHTML;
        }
        if (!cloneHtml) {
            return undefined;
        }
        const button = this.bga.statusBar.addActionButton(cloneHtml, (event) => this.onToken(event), {
            color,
            disabled: !active,
            id: "button_" + target
        });
        return button;
    }
    createCustomButtonImageHtml(target, paramInfo) {
        return undefined;
    }
    replicateTargetOnSelectionArea(target, paramInfo) {
        const div = $(target);
        if (!div)
            return undefined;
        const parent = document.createElement("div");
        parent.classList.add("target_container");
        const clone = div.cloneNode(true);
        clone.id = div.id + "_temp";
        parent.appendChild(clone);
        $("selection_area").appendChild(parent);
        clone.addEventListener("click", (event) => this.onToken(event));
        clone.classList.remove(this.classActiveSlot);
        clone.classList.add(this.classActiveSlotHidden);
        return clone;
    }
    getReasonText(reason) {
        if (!reason)
            return "";
        return _("Reason:") + " " + this.getTokenName(reason);
    }
    getTargetButtonName(target, paramInfo) {
        const div = $(target);
        let name = paramInfo.name;
        if (!name && div) {
            name = div.dataset.name;
        }
        if (!name)
            return this.getTokenName(target);
        else
            return this.getTr(name, paramInfo.args ?? paramInfo);
    }
    isMultiSelectArgs(args) {
        return args.ttype == "token_count" || args.ttype == "token_array";
    }
    isMultiCountArgs(args) {
        return args.ttype == "token_count";
    }
    onLeavingState(stateName, args) {
        super.onLeavingState(stateName, args);
        $("button_undo")?.remove();
    }
    /** default click processor */
    onToken(event, fromMethod) {
        console.log(event);
        let id = this.onClickSanity(event);
        if (!id) {
            return true;
        }
        if (!fromMethod)
            fromMethod = "onToken";
        event.stopPropagation();
        event.preventDefault();
        const ttype = this.opInfo?.ttype;
        if (ttype) {
            var methodName = "onToken_" + ttype;
            let ret = this.callfn(methodName, id, event.currentTarget);
            if (ret === undefined)
                return false;
            return true;
        }
        else if (!this.isActiveSlot(id)) {
            return this.onToken_nonActive(id, event.currentTarget);
        }
        console.error("no handler for ", ttype);
        return false;
    }
    onToken_nonActive(target, node) {
        return false;
    }
    clientCheckTargetError(target, opInfo, node) {
        const paramInfo = opInfo?.info?.[target];
        if (!paramInfo) {
            this.onToken_nonActive(target, node);
            return false; // not sending to server
        }
        return true;
    }
    onToken_token(target, node) {
        if (!target)
            return false;
        if (!this.clientCheckTargetError(target, this.opInfo, node)) {
            return false;
        }
        this.resolveAction({ target });
        return true;
    }
    onToken_token_array(target, node) {
        return this.onMultiCount(target, this.opInfo, node);
    }
    onToken_token_count(target, node) {
        return this.onMultiCount(target, this.opInfo, node);
    }
    activateMultiSelectPrompt(opInfo) {
        const ttype = opInfo.ttype;
        const buttonName = _("Submit");
        const doneButtonId = "button_done";
        const resetButtonId = "button_reset";
        this.bga.statusBar.addActionButton(buttonName, () => {
            const res = {};
            const count = this.getMultiSelectCountAndSync(res);
            if (opInfo.ttype == "token_count") {
                this.resolveAction({ target: res, count });
            }
            else {
                this.resolveAction({ target: Object.keys(res), count });
            }
        }, {
            color: "primary",
            id: doneButtonId
        });
        this.bga.statusBar.addActionButton(_("Reset"), () => {
            const allSel = document.querySelectorAll(`.${this.classSelectedAlt},.${this.classSelected}`);
            allSel.forEach((node) => {
                delete node.dataset.count;
            });
            this.removeAllClasses(this.classSelected, this.classSelectedAlt);
            this.onMultiSelectionUpdate(opInfo);
        }, {
            color: "alert",
            id: resetButtonId
        });
        // this.replicateTokensOnToolbar(opInfo, (target) => {
        //   return this.onMultiCount(target, opInfo);
        // });
        this.onMultiSelectionUpdate(opInfo);
        // this[`onToken_${ttype}`] = (tid: string, o: OpInfo, node: HTMLElement) => {
        //   return this.onMultiCount(tid, opInfo, node);
        // };
    }
    onUpdateActionButtons_PlayerTurnConfirm(args) {
        this.bga.statusBar.addActionButton(_("Confirm"), () => this.resolveAction());
        this.addUndoButton();
    }
    resolveAction(args = {}) {
        this.bga.actions
            .performAction("action_resolve", {
            data: JSON.stringify(args)
        })
            .then((x) => {
            console.log("action complete", x);
        })
            .catch((e) => {
            console.log("action failed", e);
            this.setSubPrompt(e.message, e.args ?? []);
        });
    }
    addUndoButton(cond = true) {
        if (!$("button_undo") && !this.bga.players.isCurrentPlayerSpectator() && cond) {
            const div = this.bga.statusBar.addActionButton(_("Undo"), () => this.bga.actions
                .performAction("action_undo", [], {
                checkAction: false
            })
                .catch((e) => {
                this.setSubPrompt(e.message, e.args ?? []);
            }), {
                color: "alert",
                id: "button_undo"
            });
            div.classList.add("button_undo");
            div.title = _("Undo all possible steps");
            $("undoredo_wrap")?.appendChild(div);
            // const div2 = this.addActionButtonColor("button_undo_last", _("Undo"), () => this.sendActionUndo(-1), "red");
            // div2.classList.add("button_undo");
            // div2.title = _("Undo One Step");
            // $("undoredo_wrap")?.appendChild(div2);
        }
    }
    getMultiSelectCountAndSync(result = {}) {
        // sync alternative selection on toolbar
        const allSel = document.querySelectorAll(`.${this.classSelected}`);
        const selectedAlt = this.classSelectedAlt;
        this.removeAllClasses(selectedAlt);
        let totalCount = 0;
        allSel.forEach((node) => {
            let altnode = document.querySelector(`[data-target-id="${node.id}"]`);
            // if (!altnode) {
            //   altnode = $(node.dataset.targetId);
            // }
            if (altnode && altnode != node) {
                altnode.classList.add(selectedAlt);
            }
            const cnode = altnode ?? node;
            const tid = cnode.dataset.targetId ?? node.id;
            const count = cnode.dataset.count === undefined ? 1 : Number(cnode.dataset.count);
            result[tid] = count;
            totalCount += count;
        });
        return totalCount;
    }
    onMultiCount(tid, opInfo, clicknode) {
        if (!tid)
            return false;
        let node = clicknode ?? $(tid);
        let altnode;
        if (clicknode) {
            altnode = $(clicknode.dataset.primaryId);
        }
        if (!altnode)
            altnode = document.querySelector(`[data-target-id="${tid}"]`);
        const cnode = altnode ?? node;
        const count = Number(cnode.dataset.count ?? 0);
        cnode.dataset.count = String(count + 1);
        const max = Number(cnode.dataset.max ?? 1);
        const selNode = cnode;
        if (count + 1 > max) {
            cnode.dataset.count = "0";
            selNode.classList.remove(this.classSelected);
        }
        else {
            selNode.classList.add(this.classSelected);
        }
        this.onMultiSelectionUpdate(opInfo);
        return true;
    }
    onMultiSelectionUpdate(opInfo) {
        const ttype = opInfo.ttype;
        const skippable = false; // XXX
        const doneButtonId = "button_done";
        const resetButtonId = "button_reset";
        const skipButton = $("button_skip");
        const buttonName = _("Submit");
        // sync real selection to alt selection on toolbar
        const count = this.getMultiSelectCountAndSync();
        const doneButton = $(doneButtonId);
        if (doneButton) {
            if ((count == 0 && skippable) || count < opInfo.mcount) {
                doneButton.classList.add(this.classButtonDisabled);
                doneButton.title = _("Cannot use this action because insuffient amount of elements selected");
            }
            else if (count > opInfo.count) {
                doneButton.classList.add(this.classButtonDisabled);
                doneButton.title = _("Cannot use this action because superfluous amount of elements selected");
            }
            else {
                doneButton.classList.remove(this.classButtonDisabled);
                doneButton.title = "";
            }
            $(doneButtonId).innerHTML = buttonName + ": " + count;
        }
        if (count > 0) {
            $(resetButtonId)?.classList.remove(this.classButtonDisabled);
            if (skipButton) {
                skipButton.classList.add(this.classButtonDisabled);
                skipButton.title = _("Cannot use this action because there are some elements selected");
            }
        }
        else {
            $(resetButtonId)?.classList.add(this.classButtonDisabled);
            if (skipButton) {
                skipButton.title = "";
                skipButton.classList.remove(this.classButtonDisabled);
            }
        }
    }
    setSubPrompt(text, args = {}) {
        if (!text)
            text = "";
        const message = this.format_string_recursive(this.getTr(text, args), args);
        // have to set after otherwise status update wipes it
        setTimeout(() => {
            $("gameaction_status").innerHTML = `<div class="subtitle">${message}</div>`;
        }, 100);
    }
    completeOpInfo(opInfo) {
        var _a, _b;
        try {
            // server may skip sending some data, this will feel all omitted fields
            if (opInfo.data?.count !== undefined && opInfo.count === undefined)
                opInfo.count = parseInt(opInfo.data.count);
            if (opInfo.data?.mcount !== undefined && opInfo.mcount === undefined)
                opInfo.mcount = parseInt(opInfo.data.mcount);
            if (opInfo.void === undefined)
                opInfo.void = false;
            opInfo.confirm = opInfo.confirm ?? false;
            if (!opInfo.info)
                opInfo.info = {};
            if (!opInfo.target)
                opInfo.target = [];
            if (!opInfo.ui)
                opInfo.ui = {};
            const infokeys = Object.keys(opInfo.info);
            if (infokeys.length == 0 && opInfo.target.length > 0) {
                opInfo.target.forEach((element) => {
                    opInfo.info[element] = { q: 0 };
                });
            }
            else if (infokeys.length > 0 && opInfo.target.length == 0) {
                infokeys.forEach((element) => {
                    if (opInfo.info[element].q == 0)
                        opInfo.target.push(element);
                });
            }
            // set default order
            let i = 1;
            for (const target of opInfo.target) {
                const paramInfo = opInfo.info[target];
                if (!paramInfo.o)
                    paramInfo.o = i;
                i++;
            }
            if (opInfo.info.confirm && !opInfo.info.confirm.name) {
                opInfo.info.confirm.name = _("Confirm");
            }
            if (opInfo.info.skip && !opInfo.info.skip.name) {
                opInfo.info.skip.name = _("Skip");
            }
            if (this.isMultiSelectArgs(opInfo)) {
                opInfo.ui.replicate = true;
                (_a = opInfo.ui).color ?? (_a.color = "secondary");
            }
            else {
                (_b = opInfo.ui).color ?? (_b.color = "primary");
            }
            if (opInfo.ui.buttons === undefined && !opInfo.ui.replicate) {
                opInfo.ui.buttons = true;
            }
        }
        catch (e) {
            console.error(e);
        }
    }
}

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
class PlayerTurn {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }
    onEnteringState(args, isCurrentPlayerActive) {
        if (args._private)
            this.game.onEnteringState_PlayerTurn(args._private);
        else
            this.game.onEnteringState_PlayerTurn(args);
    }
    // onLeavingState(args: any, isCurrentPlayerActive: boolean) {
    //   this.game.onLeavingState("PlayerTurn", args);
    // }
    onPlayerActivationChange(args, isCurrentPlayerActive) { }
}
class PlayerTurnConfirm extends PlayerTurn {
    onEnteringState(args, isCurrentPlayerActive) {
        this.bga.statusBar.addActionButton(_("Confirm"), () => this.game.resolveAction());
    }
}
class Game extends GameMachine {
    constructor(bga) {
        super(bga);
        this.inSetup = true;
        this.boardLayout = "scale";
        this.AI_PLAYER_ID = 1;
        this.AI_COLOR_OVERRIDE = "982fff";
        this._ghostMouseHandler = null;
        this.gameTemplate = `
<div id='selection_area' class='selection_area'></div>
<div id="score-area">
  <div id="game-score-sheet"></div>
  <div id="game-score-sheet-ai"></div>
</div>


<div id="thething_wrap">
<div id="thething">
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
 
</div>



`;
        this.boundUpdateBoardScale = () => {
            this.updateBoardScale($("thething"));
        };
        //console.log("wayfarers constructor");
        this.bga.states.register("PlayerTurn", new PlayerTurn(this, bga));
        this.bga.states.register("PlayerTurnConfirm", new PlayerTurnConfirm(this, bga));
    }
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
            document.querySelectorAll(".jpos").forEach((node) => {
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
        }
        catch (e) {
            console.error("Exception during game setup", e.stack);
        }
        console.log("Ending game setup");
        this.inSetup = false;
    }
    setupPlayer(playerInfo) {
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
        placeHtml(`
      <div id='tableau_${pcolor}' class='tableau' data-player-name='${playerInfo.name}' style='--player-color: #${pcolor}'>

         <div id='pboard_${pcolor}' class='pboard' data-player-name='${playerInfo.name}'>
           <div id='breakroom_${pcolor}' class='breakroom'></div>
           <div id='infsupply_${pcolor}' class='infsupply'></div>
           <div id='caravan_${pcolor}' class='caravan'>
             ${caravanCells}
           </div>
         </div>
      </div>`, parent);
        const boardNum = Number(this.gamedatas.tokens[`pboard_${pcolor}`]?.state ?? 1);
        $(`caravan_${pcolor}`)
            .querySelectorAll(".ccell")
            .forEach((node) => {
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
    setupAutoma(playerInfo) {
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
        placeHtml(`<div id='player_panel_content_${pcolor}' class='player_panel_content'></div>`, this.bga.playerPanels.getElement(playerInfo.id));
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
        const boardNum = -Number(this.gamedatas.tokens[`pboard_${pcolor}`]?.state ?? -1);
        placeHtml(`
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
      </div>`, parent);
        $(`caravan_${pcolor}`)
            .querySelectorAll(".ccell")
            .forEach((node) => {
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
    setBoardLayout(layout) {
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
        }
        else {
            window.removeEventListener("resize", this.boundUpdateBoardScale);
        }
    }
    updateBoardScale(scalecontrol) {
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
        if (!set)
            return; // just unset
        // Temporarily allow overflow and shrink to min-content to measure natural content width
        scalecontrol.style.overflow = "visible";
        scalecontrol.style.width = "min-content";
        const naturalWidth = scalecontrol.scrollWidth;
        scalecontrol.style.width = "";
        scalecontrol.style.overflow = "";
        const availableWidth = parent.clientWidth;
        let scale = 1;
        if (naturalWidth > availableWidth) {
            scale = availableWidth / naturalWidth;
        }
        this.applyScale(scalecontrol, scale, naturalWidth);
    }
    applyScale(scalecontrol, scale, naturalWidth) {
        if (Math.abs(scale - 1) < 0.01)
            return;
        // Set width to natural content width so scaling fills the available space
        if (naturalWidth) {
            scalecontrol.style.width = `${naturalWidth}px`;
        }
        const naturalHeight = scalecontrol.scrollHeight;
        scalecontrol.dataset.scale = String(scale);
        scalecontrol.style.transform = `scale(${scale})`;
        scalecontrol.style.transformOrigin = "top left";
        // Use negative margin to reduce flow space instead of setting height,
        // so that absolutely positioned children keep their containing block size
        const reducedHeight = naturalHeight * (1 - scale);
        scalecontrol.style.marginBottom = `-${reducedHeight}px`;
    }
    updateBanner() {
        if (this.gamedatas.lastTurn)
            this.bga.gameArea.addLastTurnBanner(_("This is the last round!"));
        else
            this.bga.gameArea.removeLastTurnBanner();
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
    setupAIScoreSheet(scores) {
        if (this.scoreSheetAI)
            return;
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
            onScoreDisplayed: (property, playerId, score) => {
                if (property === "total") {
                    this.bga.playerPanels.getScoreCounter(playerId).setValue(score);
                }
            }
        });
    }
    onEnteringState_PlayerTurn(opInfo) {
        console.log("onEnteringState_PlayerTurn", opInfo);
        super.onEnteringState_PlayerTurn(opInfo);
        if (!this.bga.players.isCurrentPlayerActive())
            return;
        switch (opInfo.type) {
            case "turn":
                // $("selection_area").insertAdjacentElement("afterend", $("mainarea"));
                const firstTarget = document.querySelector("." + this.classActiveSlot);
                if (!firstTarget)
                    return;
                $(firstTarget).scrollIntoView({
                    behavior: "smooth",
                    block: "nearest" // Scrolls the minimum amount to bring the element into view vertically
                });
                break;
            case "upgYellow":
            case "upgBlue":
            case "upgGreen":
            case "upgPink":
            case "upgBlack":
                this.startGhostTile(opInfo);
                break;
        }
    }
    startGhostTile(opInfo) {
        const tileId = opInfo.data.tile;
        const tileNode = $(tileId);
        if (!tileNode)
            return;
        const ghost = this.animationLa.projectOnto(tileNode, "_ghost");
        ghost.style.opacity = "0.6";
        ghost.style.pointerEvents = "none";
        ghost.style.transitionProperty = "none";
        ghost.style.visibility = "hidden";
        const over = $("oversurface");
        const caravan = $(`caravan_${this.player_color}`);
        if (!caravan)
            return;
        const handler = (e) => {
            const overRect = over.getBoundingClientRect();
            const scaleX = over.offsetWidth > 0 ? overRect.width / over.offsetWidth : 1;
            const scaleY = over.offsetHeight > 0 ? overRect.height / over.offsetHeight : 1;
            const caravanRect = caravan.getBoundingClientRect();
            const inCaravan = e.clientX >= caravanRect.left && e.clientX <= caravanRect.right && e.clientY >= caravanRect.top && e.clientY <= caravanRect.bottom;
            if (inCaravan) {
                const x = (e.clientX - overRect.left) / scaleX - 21;
                const y = (e.clientY - overRect.top) / scaleY - 21;
                ghost.style.left = x + "px";
                ghost.style.top = y + "px";
                ghost.style.transform = "none";
                ghost.style.visibility = "visible";
            }
            else {
                ghost.style.visibility = "hidden";
            }
        };
        document.addEventListener("mousemove", handler);
        this._ghostMouseHandler = handler;
    }
    stopGhostTile() {
        if (this._ghostMouseHandler) {
            document.removeEventListener("mousemove", this._ghostMouseHandler);
            this._ghostMouseHandler = null;
        }
        document.querySelectorAll("[id$='_ghost']").forEach((el) => el.remove());
    }
    onLeavingState(stateName, args) {
        super.onLeavingState(stateName, args);
        this.stopGhostTile();
        const opInfo = this.opInfo;
        if (opInfo?.ui?.replicate) {
            $("selection_area")
                .querySelectorAll("& > *")
                .forEach((element) => {
                element.remove();
            });
        }
    }
    showHelp(id) {
        return false;
    }
    hideCard(tokenId) {
        $("limbo")?.appendChild($(tokenId));
    }
    getPlaceRedirect(tokenInfo, args = {}) {
        const location = tokenInfo.location ?? "limbo";
        const tokenId = tokenInfo.key;
        const result = {
            location: location,
            key: tokenId,
            state: tokenInfo.state
        };
        if (args.place_from)
            result.place_from = args.place_from;
        if (args.inc)
            result.inc = args.inc;
        if (!this.gameAnimationsActive()) {
            result.animtime = 0;
        }
        if (tokenId.startsWith("card")) {
            // cards
            result.onClick = (x) => this.onToken(x);
            const cardType = getPart(tokenId, 1);
            if (location.startsWith("mainarea")) {
                result.location = `carddisplay_${cardType}`;
            }
            else if (location.startsWith("hand")) {
                const color = getPart(location, 1);
                if (color != this.player_color)
                    result.nop = true;
                else {
                    result.location = `selection_area`;
                    result.onClick = (x) => this.onToken(x);
                }
            }
            else if (location.startsWith("tableau")) {
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
                    placeHtml(`<div id='${result.location}' class='column' data-state='${x}' style='order: ${x};'></div>`, `pboard_${color}`, "afterend");
                    if (this.gameAnimationsActive()) {
                        this.boundUpdateBoardScale();
                        $(result.location).scrollIntoView({ behavior: "smooth", block: "center" });
                    }
                }
            }
            else if (location.startsWith("discard")) {
                result.onEnd = (node) => this.hideCard(node);
            }
            else if (location.startsWith("deck")) {
                result.onEnd = (node) => this.hideCard(node);
            }
            else if (location.startsWith("card")) {
                result.onEnd = (node) => {
                    const grand = node.parentElement.parentElement;
                    grand.appendChild(node);
                    node.dataset[`${getPart(location, 1)}Pos`] = getPart(location, 2);
                };
            }
        }
        else if (tokenId.startsWith("tableau")) {
            result.nop = true;
            // } else if (tokenId.startsWith("jpos")) {
            //   //jpos_10
            //   result.onClick = (x) => this.onToken(x);
        }
        else if (tokenId.startsWith("mainboard_")) {
            result.location = `mainboardall`;
        }
        else if (tokenId.startsWith("marker")) {
            result.location = `jpos_${tokenInfo.state}`;
        }
        else if (tokenId.startsWith("hand")) {
            result.nop = true;
        }
        else if (tokenId.startsWith("deck") || tokenId.startsWith("discard")) {
            result.nop = true;
        }
        else if (tokenId.startsWith("slot") || tokenId == "round_banner") {
            result.nop = true; // do not move slots
        }
        else if (tokenId.startsWith("tracker_res") && location.startsWith("tableau")) {
            const color = getPart(location, 1);
            result.location = `restrack_${color}`;
        }
        else if (tokenId.startsWith("tracker_comet") && location.startsWith("tableau")) {
            const color = getPart(location, 1);
            result.location = `comettrack_${color}`;
        }
        else if (tokenId.startsWith("tracker")) {
            result.nop = true;
        }
        else if (location.startsWith("miniboard") && $(tokenId)) {
            result.nop = true; // do not move
        }
        else if ((tokenId.startsWith("worker") || tokenId.startsWith("dice")) && location.startsWith("tableau")) {
            const color = getPart(location, 1);
            result.location = `breakroom_${color}`;
            result.onClick = (x) => this.onToken(x);
        }
        else if (tokenId.startsWith("dice") && location.startsWith("card")) {
            result.onClick = (x) => this.onToken(x);
        }
        else if (tokenId.startsWith("inf")) {
            // influence
            result.onClick = (x) => this.onToken(x);
            const infColor = getPart(tokenId, 1);
            if (location.startsWith("tableau")) {
                const color = getPart(location, 1);
                result.location = `infsupply_${color}`;
                result.onEnd = () => this.updateGuildCounters(infColor);
            }
            else if (location.startsWith("guild")) {
                result.location = `${location}_${infColor}`;
                result.onEnd = () => this.updateGuildCounters(infColor);
            }
        }
        else if (tokenId.startsWith("upg")) {
            if (location.startsWith("tableau")) {
                // Upgrade tiles in caravan - state encodes position: pos = x + y * 6 + 1
                const color = getPart(location, 1);
                const pos = Number(tokenInfo.state);
                if (pos <= 0) {
                    // they hung out on tableau?
                }
                else {
                    result.location = `ccell_${pos}_${color}`;
                }
            }
            else if (location.startsWith("mainarea")) {
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
    createMiniboard(pcolor, parentId) {
        placeHtml(`<div id='miniboard_${pcolor}' class='miniboard'>
        <div id='guild_yellow_count_${pcolor}' class='guild_count wicon wicon_inf_yellow' data-state='0'></div>
        <div id='guild_blue_count_${pcolor}' class='guild_count wicon wicon_inf_blue' data-state='0'></div>
        <div id='guild_black_count_${pcolor}' class='guild_count wicon wicon_inf_black' data-state='0'></div>
      </div>`, parentId);
    }
    updateGuildCounters(pcolor) {
        const guilds = ["yellow", "blue", "black"];
        for (const guild of guilds) {
            let count = 0;
            for (const token in this.gamedatas.tokens) {
                if (token.startsWith(`influence_${pcolor}_`) && this.gamedatas.tokens[token].location === `guild_${guild}`) {
                    count++;
                }
            }
            const node = $(`guild_${guild}_count_${pcolor}`);
            if (node)
                node.dataset.state = String(count);
        }
    }
    onToken_nonActive(target, node) {
        if (!target)
            return false;
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
                    this.showHiddenContent(container, _("Pile contents"), 0, function (a, b) {
                        const orderA = parseInt(a.dataset.state);
                        const orderB = parseInt(b.dataset.state);
                        return -orderA + orderB; // descending
                    });
                }
                break;
        }
        return true;
    }
    createCustomButtonImageHtml(target, paramInfo) {
        const op = this.opInfo.type;
        switch (op) {
            case "diceMod":
                // special rendering
                const from = paramInfo.from;
                const to = paramInfo.to;
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
    updateTokenDisplayInfo(tokenInfo) {
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
                if (!num)
                    return;
                const tname = this.getTokenName(`card_${t}`);
                const gname = this.getTr(tokenInfo.nom);
                tokenInfo.name = gname ? `${gname}` : `${tname} #${num}`;
                const origtt = (tokenInfo.tooltip ?? (tokenInfo.tooltip = ""));
                switch (t) {
                    case "home":
                        tokenInfo.tooltip = origtt;
                        // tokenInfo.tooltip += this.ttSection(_("Name"), this.getTr(tokenInfo.nom));
                        if (tokenInfo.dr)
                            tokenInfo.tooltip += this.ttSection(_("Die Slot"), this.getTr(tokenInfo.todr));
                        //tokenInfo.imageTypes = "home";
                        break;
                    case "land":
                        tokenInfo.tooltip = this.ttSection(_("Card Type"), tname);
                        tokenInfo.tooltip += this.ttSection(_("Ref#"), num);
                        tokenInfo.tooltip += this.ttSection(_("Tags"), this.getTagsListTr(tokenInfo.tags));
                        if (tokenInfo.r)
                            tokenInfo.tooltip += this.ttSection(_("Instant"), this.getTr(tokenInfo.tor));
                        if (tokenInfo.d)
                            tokenInfo.tooltip += this.ttSection(_("Die Slot"), this.getTr(tokenInfo.todr));
                        if (tokenInfo.trig) {
                            tokenInfo.tooltip += this.ttSection(_("Triggers on"), this.getTagsListTr(tokenInfo.trig));
                            tokenInfo.tooltip += this.ttSection(_("Trigger Effect"), this.getTr(tokenInfo.todr));
                        }
                        break;
                    case "water":
                        tokenInfo.tooltip = this.ttSection(_("Card Type"), tname);
                        tokenInfo.tooltip += this.ttSection(_("Ref#"), num);
                        tokenInfo.tooltip += this.ttSection(_("Tags"), this.getTagsListTr(tokenInfo.tags));
                        if (tokenInfo.r)
                            tokenInfo.tooltip += this.ttSection(_("Instant"), this.getTr(tokenInfo.tor));
                        if (tokenInfo.dr)
                            tokenInfo.tooltip += this.ttSection(_("Die Slot"), this.getTr(tokenInfo.todr));
                        break;
                    case "space":
                        tokenInfo.tooltip = this.ttSection(_("Card Type"), tname);
                        tokenInfo.tooltip += this.ttSection(_("Ref#"), num);
                        //tokenInfo.tooltip += this.ttSection(_("Name"), this.getTr(tokenInfo.nom));
                        tokenInfo.tooltip += this.ttSection(_("Tags"), this.getTagsListTr(tokenInfo.tags));
                        tokenInfo.tooltip += this.ttSection(_("Cost"), _("Base cost in Silver shown on the board under the card"));
                        if (tokenInfo.r)
                            tokenInfo.tooltip += this.ttSection(_("Instant"), this.getTr(tokenInfo.tor));
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
                        }
                        else {
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
                        tokenInfo.tooltip += this.ttSection(undefined, _("If this goal is achieved at end of game the Inspiration Card will double their Star's scoring"));
                        tokenInfo.tooltip += this.ttSection(_("Instant"), _("Instead of gaining, card maybe discarded for the effect of the Worker Placement spot that the Card is adjacent to"));
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
                        if (tokenInfo.comet == "1")
                            tokenInfo.tooltip += this.ttSection(_("Comet"), _("Yes"));
                        else
                            tokenInfo.tooltip += this.ttSection(_("Comet"), _("No"));
                        break;
                }
                return;
            }
            case "upg": {
                //num|t|r|r2|tags|vp
                const num = getPart(tokenId, 2) ?? "";
                if (!num)
                    return;
                const color = getPart(tokenId, 1);
                const tname = this.getTokenName(`upg_${color}`);
                tokenInfo.tooltip = "";
                tokenInfo.tooltip += this.ttSection(_("Type"), tname);
                tokenInfo.tooltip += this.ttSection(_("Ref#"), num);
                if (tokenInfo.tags)
                    tokenInfo.tooltip += this.ttSection(_("Tags"), _(tokenInfo.tags));
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
                if (tokenInfo.vp)
                    tokenInfo.tooltip += this.ttSection(_("VP"), _(tokenInfo.vp));
                return;
            }
            case "jtile": {
                const num = getPart(tokenId, 1) ?? "";
                if (!num)
                    return;
                tokenInfo.name = this.getTokenName("jtile");
                tokenInfo.tooltip = this.ttSection(_("Bonus"), this.getTr(tokenInfo.tooltip));
                return;
            }
            case "dice": {
                const num = getPart(tokenId, 2) ?? "";
                if (!num)
                    return;
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
    ttSection(prefix, text) {
        if (prefix)
            return `<p><b>${prefix}</b>: ${text}</p>`;
        else
            return `<p>${text}</p>`;
    }
    getTagsListTr(tags, sep = ", ") {
        if (!tags)
            return "";
        // get translated tags
        const tagList = tags.split(/[, \/]/);
        const trTags = [];
        for (const tag of tagList) {
            if (!tag)
                continue;
            trTags.push(this.getTr(this.getRulesFor(`tag_${tag}`, "name")) ?? tag);
        }
        return trTags.join(sep);
    }
    getOpListTr(tags, sep = ", ") {
        // get translated ops
        if (!tags)
            return "";
        const tagList = tags.split(/[, \/]/);
        const trTags = [];
        for (const tag of tagList) {
            if (!tag)
                continue;
            let opName = this.getRulesFor(`Op_${tag}`, "name", null);
            if (!opName)
                opName = this.getRulesFor(tag, "name", null);
            if (!opName)
                opName = tag;
            trTags.push(this.getTr(opName));
        }
        return trTags.join(sep);
    }
    getColorName(color) {
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
    showHiddenContent(id, title, selectedId, sort) {
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
        children.forEach((node, index) => {
            const origId = node.id.replace("_tt", "");
            node.addEventListener("click", (e) => {
                const selected_html = this.getTooltipHtmlForToken(origId);
                $("card_pile_selector").innerHTML = selected_html;
            });
            if (index === selectedId)
                selectedId = origId;
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
            handlers: [this],
            onStart: (notifName, msg, args) => {
                if (msg)
                    this.setSubPrompt(msg, args);
            }
            // onEnd: (notifName, msg, args) => this.setSubPrompt("", args)
        });
    }
    // Re-declare parent notif_ methods so setupPromiseNotifications discovers them
    async notif_tokenMoved(args) {
        return super.notif_tokenMoved(args);
    }
    async notif_counter(args) {
        return super.notif_counter(args);
    }
    async notif_animate(args) {
        return super.notif_animate(args);
    }
    notif_log(args) {
        return super.notif_log(args);
    }
    notif_message_warning(notif) {
        return super.notif_message_warning(notif);
    }
    notif_message_info(notif) {
        return super.notif_message_info(notif);
    }
    async notif_message(args) {
        //console.log("notif", args);
        return gameui.wait(1);
    }
    async notif_undoMove(args) {
        console.log("notif", args);
        return gameui.wait(1);
    }
    async notif_lastTurn(args) {
        this.gamedatas.lastTurn = true;
        this.updateBanner();
    }
    async notif_endScores(args) {
        // setting scores will make the score sheet visible if it isn't already
        await this.scoreSheet.setScores(args.endScores, {
            startBy: this.bga.players.getCurrentPlayerId()
        });
        if (args.aiEndScores) {
            if (!this.scoreSheetAI) {
                this.setupAIScoreSheet(args.aiEndScores);
            }
            else {
                await this.scoreSheetAI.setScores(args.aiEndScores);
            }
        }
    }
    replaceSimpleIconsInLog(log) {
        // Process square bracket syntax [tokenId]
        if (log.includes("[")) {
            log = log.replace(/\[([^\]]+)\]/g, (match, keyExpr) => {
                try {
                    return this.getTokenPresentaton(keyExpr, keyExpr, []) ?? match;
                }
                catch (e) {
                    console.error(`Failed to get token presentation for [${keyExpr}]`, e);
                    return match; // Return original if error
                }
            });
        }
        return log;
    }
    /** @Override */
    bgaFormatText(log, args) {
        try {
            if (!log)
                return { log: "", args: [] };
            if (typeof log !== "string") {
                //console.trace("Non-string log message", log, args);
                if (log.log) {
                    return this.bgaFormatText(log.log, log.args);
                }
                return { log: "?", args: [] };
            }
            if (args && args.processed) {
                return { log, args };
            }
            if (args && !args.processed && log.includes("$")) {
                args.processed = true;
                if (!args.player_id) {
                    args.player_id = this.bga.players.getActivePlayerId();
                }
                if (args.player_id == this.AI_PLAYER_ID) {
                    args.player_name = `<span class="playername" style="color: #${this.AI_COLOR_OVERRIDE};">Aida</span>`;
                }
                else if (args.player_id && !args.player_name) {
                    args.player_name = this.gamedatas.players[args.player_id].name;
                }
                if (args.you)
                    args.you = this.divYou(); // will replace ${you} with colored version
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
                log = this.replaceSimpleIconsInLog(log);
                return { log, args };
            }
            log = this.replaceSimpleIconsInLog(log);
        }
        catch (e) {
            console.error(log, args, "Exception thrown", e.stack);
        }
        return { log, args: {} }; // no args - to prevent framework doing nasty stuff
    }
}

export { Game };
