/**
 * Harness renderer: reads staging/gamedatas.json + staging/notifications.json,
 * loads template.html into JSDOM, runs game.setup(), replays notifications,
 * inlines wayfarers.css, and writes staging/snapshot.html.
 *
 * Usage: ts-node --project tests/Harness/tsconfig.json tests/Harness/render.ts
 */

import fs from "fs";
import path from "path";
import { JSDOM, VirtualConsole } from "jsdom";
import Module from "module";
import { execFileSync } from "child_process";
type CustomGamedatas = any;
type CustomPlayer = any;

// Harness gamedatas carries both BGA spellings of the player fields
type HarnessPlayer = CustomPlayer & { player_color?: string; player_name?: string };
type HarnessNotif = { type: string; log?: string; args?: unknown; channel?: string; player_id?: number };
// The gameui stub as seen from the replay code below
type HarnessGameui = { format_string_recursive(str: string, args: unknown): string } & Record<string, unknown>;

// ── Suppress verbose game console.log output ──────────────────────────────────
// Game code logs a lot of debug info; suppress it unless HARNESS_VERBOSE=1.
const log = console.log.bind(console);
if (!process.env.HARNESS_VERBOSE) {
  console.log = () => {};
}

// ── Path constants ─────────────────────────────────────────────────────────────

const repoRoot = path.resolve(__dirname, "../..");
const stagingDir = path.join(repoRoot, "staging");

function readJson(file: string): unknown {
  const p = path.join(stagingDir, file);
  if (!fs.existsSync(p)) {
    console.error(`Missing: ${p}`);
    process.exit(1);
  }
  return JSON.parse(fs.readFileSync(p, "utf8"));
}

// Same scenario file the PHP side ran (GameDriver resolves it as <harness>/plays/<name>.json).
const playName = process.argv[2] ?? "setup";
const scriptPath = path.join(__dirname, "plays", `${playName}.json`);
const script: { current_player_id?: number } = fs.existsSync(scriptPath) ? JSON.parse(fs.readFileSync(scriptPath, "utf8")) : {};

const gamedatas = readJson("gamedatas.json") as CustomGamedatas;
const notifications = readJson("notifications.json") as HarnessNotif[];

// ── Intercept require("./libs") → libs.stub.ts (top-level await breaks CommonJS) ──

const moduleInternals = Module as unknown as { _resolveFilename(request: string, parent: unknown, ...args: unknown[]): string };
const originalResolve = moduleInternals._resolveFilename;
const libsStubPath = path.resolve(repoRoot, "src/tests/libs.stub.ts");
const libsSrcPath = path.resolve(repoRoot, "src/libs.ts");
moduleInternals._resolveFilename = function (request: string, parent: unknown, ...args: unknown[]) {
  const resolved = originalResolve.call(this, request, parent, ...args);
  if (resolved === libsSrcPath) return libsStubPath;
  return resolved;
};

// ── Load template.html into JSDOM ─────────────────────────────────────────────

const templatePath = path.join(__dirname, "template.html");
const templateHtml = fs.readFileSync(templatePath, "utf8");
const virtualConsole = new VirtualConsole();
virtualConsole.forwardTo(console, { jsdomErrors: "none" });
const dom = new JSDOM(templateHtml, {
  runScripts: "dangerously",
  virtualConsole
});
const { window } = dom;
const { document } = window;

// ── Inject player panels (framework normally does this server-side) ────────────

function buildPlayerPanels(gamedatas: CustomGamedatas, currentPlayerId: number): void {
  const playerBoards = document.getElementById("player_boards");
  if (!playerBoards) return;

  Object.entries(gamedatas.players as Record<string, HarnessPlayer>).forEach(([id, player]) => {
    const color = player.player_color ?? player.color;
    const name = player.player_name ?? "Player";
    const isCurrent = Number(id) == currentPlayerId;

    const panel = document.createElement("div");
    panel.id = `overall_player_board_${id}`;
    panel.className = `player-board${isCurrent ? " current-player-board" : ""}`;
    panel.innerHTML = `
      <div class="player_board_inner" id="player_board_inner_${color}">
        <div class="player-name" id="player_name_${id}">
          <a style="color:#${color}">${name}</a>
        </div>
        <div id="player_board_${id}" class="player_board_content">
          <div class="player_score">
            <span id="player_score_${id}" class="player_score_value"></span>
          </div>
          <div class="player-board-game-specific-content"></div>
          <div class="player_table_status" id="player_table_status_${id}"></div>
        </div>
        ${isCurrent ? '<div id="current_player_board"></div>' : ""}
        <div id="player_panel_content_${color}" class="player_panel_content"></div>
      </div>`;
    playerBoards.appendChild(panel);
  });
}

// current_player_id from script.json; fall back to first player in gamedatas
const currentPlayerId: number = script.current_player_id ?? parseInt(Object.keys(gamedatas.players)[0], 10);
buildPlayerPanels(gamedatas, currentPlayerId);

// Populate player.id from key (real BGA framework sets this; harness gamedatas may omit it)
Object.entries(gamedatas.players as Record<string, HarnessPlayer>).forEach(([id, player]) => {
  if (!player.id) player.id = id;
});

// ── Expose DOM globals ─────────────────────────────────────────────────────────

// Typed view of the Node global object for installing browser/BGA globals
const globalStub = global as unknown as Record<string, unknown>;

globalStub.window = window;
globalStub.document = document;
globalStub.HTMLElement = window.HTMLElement;
globalStub.Element = window.Element;
globalStub.DOMMatrix = (window as unknown as Record<string, unknown>).DOMMatrix ?? class {};

// JSDOM lacks layout methods the client calls during rendering; make them harmless no-ops.
const elementProto = window.HTMLElement.prototype as unknown as Record<string, unknown>;
elementProto.scrollIntoView = () => {};
if (!elementProto.getBoundingClientRect) {
  elementProto.getBoundingClientRect = () => ({ x: 0, y: 0, top: 0, left: 0, right: 0, bottom: 0, width: 0, height: 0 });
}

// ── BGA framework stubs ────────────────────────────────────────────────────────

globalStub.$ = function $(id: unknown) {
  if (typeof id === "string") return document.getElementById(id);
  return id;
};

globalStub._ = function _(str: string) {
  return str;
};

const tooltipRegistry = new Map<string, string>();

// Harness copy of the BGA log-formatting plumbing: args bags stay untyped like the real framework.
/* eslint-disable @typescript-eslint/no-explicit-any */
(global as any).gameui = {
  player_id: currentPlayerId,
  on_client_state: false,
  format_string_recursive_sub_logs: function (args: any): void {
    const gm = (global as any).gameui;
    for (const key in args) {
      if (key === "i18n") continue;
      const val = args[key];
      if (val === null || typeof val !== "object" || Array.isArray(val) || (typeof Node !== "undefined" && val instanceof Node)) continue;
      // `message` is the PHP NotificationMessage field; only notifications are remapped to `log`.
      if (val.log !== undefined || val.message !== undefined) {
        args[key] = gm.format_string_recursive(val.log ?? val.message, val.args);
      } else {
        gm.format_string_recursive_sub_logs(val);
      }
    }
  },
  format_string_recursive: function (str: string, args: any): string {
    if (str === null) {
      console.error("format_string_recursive called with null string", args);
      return "null_tr_string";
    }
    const gm = (global as any).gameui;
    // Allow game to pre-process log via bgaFormatText
    if (typeof gm.bgaFormatText === "function") {
      try {
        const r = gm.bgaFormatText(str, args);
        if (r) {
          str = r.log ?? str;
          args = r.args ?? args;
        }
      } catch (e: any) {
        console.error(str, args, "bgaFormatText threw", e.stack);
      }
    }
    if (!str) return "";
    // Translate i18n fields
    if (args?.i18n) {
      for (const key of Object.values(args.i18n) as string[]) {
        if (Array.isArray(args[key])) {
          args[key] = args[key].map((v: string) => gm.clienttranslate_string(v));
        } else if (args[key]) {
          args[key] = gm.clienttranslate_string(args[key]);
        }
      }
    }
    // Recursively format nested {log, args} objects
    gm.format_string_recursive_sub_logs(args);
    // Join array args with separator if specified
    if (args?.separator && typeof args.separator === "object") {
      for (const key of Object.keys(args.separator)) {
        if (Array.isArray(args[key]) && args[key].length > 1) {
          const sep = args.separator[key];
          if (sep === "and" || sep === "or") {
            const arr = args[key];
            args[key] = `${arr.slice(0, -1).join(", ")} ${sep} ${arr[arr.length - 1]}`;
          } else {
            args[key] = args[key].join(sep);
          }
        }
      }
    }
    // Substitute ${key} placeholders (dojo string.substitute style)
    return str.replace(/\$\{(\w+)\}/g, (_m: string, key: string) => args?.[key] ?? `\${${key}}`);
  },
  addTooltipHtml: (nodeId: string, html: string, _delay?: number) => {
    tooltipRegistry.set(nodeId, html);
  },
  removeTooltip: (nodeId: string) => {
    tooltipRegistry.delete(nodeId);
  },
  bgaAnimationsActive: () => false,
  restoreServerGameState: () => {},
  updatePageTitle: () => {},
  wait: (_ms: number) => Promise.resolve(),
  clienttranslate_string: (s: string) => s,
  tooltips: {}
};
/* eslint-enable @typescript-eslint/no-explicit-any */

globalStub.ebg = {
  core: { gamegui: {} },
  counter: class {},
  popindialog: class {
    create() {}
    setTitle() {}
    setContent() {}
    show() {}
  }
};

globalStub.define = function () {};

// localStorage stub (not available in Node.js)
globalStub.localStorage = {
  _store: {} as Record<string, string>,
  getItem(k: string) {
    return this._store[k] ?? null;
  },
  setItem(k: string, v: string) {
    this._store[k] = v;
  },
  removeItem(k: string) {
    delete this._store[k];
  }
};

// ── Mock Bga object (mirrors Game.spec.ts) ─────────────────────────────────────

const gameArea = document.getElementById("game_play_area")!;

// ── statusBar: writes title + buttons into #pagemaintitletext / #generalactions ──

const statusBar = {
  setTitle(html: string) {
    const el = document.getElementById("pagemaintitletext");
    if (el) el.innerHTML = html;
  },
  addActionButton(label: string, handler: unknown, options: { color?: string; id?: string } = {}) {
    const el = document.getElementById("generalactions");
    if (!el) return null;
    const btn = document.createElement("button");
    btn.className = `action-button bgabutton bgabutton_${options.color ?? "blue"}`;
    if (options.id) btn.id = options.id;
    btn.innerHTML = label;
    if (typeof handler === "function") {
      // Strategy 1: if button id is "button_<target>", construct action payload directly
      // (onToken uses event.currentTarget.id to derive target, which won't work with synthetic events)
      if (options.id && options.id.startsWith("button_")) {
        const target = options.id.replace(/^button_/, "");
        if (target && target !== "cancel" && target !== "undo" && target !== "done" && target !== "reset") {
          btn.setAttribute("data-action", JSON.stringify({ endpoint: "action_resolve", data: { data: JSON.stringify({ target }) } }));
        }
      } else {
        // Strategy 2: intercept performAction — works for handlers that call it directly
        const origPerformAction = mockBga.actions.performAction;
        let captured: string | null = null;
        mockBga.actions.performAction = (endpoint: string, data?: unknown) => {
          captured = JSON.stringify({ endpoint, data: data ?? {} });
          return Promise.resolve({});
        };
        try {
          handler(new Event("click"));
        } catch (e) {
          console.error(e);
        }
        mockBga.actions.performAction = origPerformAction;
        if (captured) btn.setAttribute("data-action", captured);
      }
    }
    el.appendChild(btn);
    return btn;
  }
};

// ── states registry: stores handlers registered by Game constructor ────────────

type StateHandler = {
  onEnteringState?: (args: object, isActive: boolean) => void;
  onLeavingState?: (args: object, isActive: boolean) => void;
};
const statesRegistry: Record<string, StateHandler> = {};
const states = {
  register(name: string, handler: StateHandler) {
    statesRegistry[name] = handler;
  },
  isOnClientState() {
    return false;
  }
};

const mockBga = {
  gameui: globalStub.gameui,
  statusBar,
  images: {},
  sounds: {},
  // No preference UI in the harness: every preference reads 0, so the client falls back to its own default.
  userPreferences: { get: () => 0, set: () => {}, toggleVisibility: () => {} },
  players: {
    isCurrentPlayerSpectator: () => false,
    isCurrentPlayerActive: () => true,
    isPlayerActive: () => true,
    getActivePlayerIds: (): number[] => [],
    getActivePlayerId: () => currentPlayerId,
    getFormattedPlayerName: (playerId: number, opts?: { replaceByYou?: boolean }) => {
      const player = gamedatas.players[playerId];
      const label = opts?.replaceByYou && playerId == currentPlayerId ? _("You") : (player?.name ?? String(playerId));
      const color = player?.color ?? "000000";
      return `<span style="font-weight:bold;color:#${color}">${label}</span>`;
    }
  },
  actions: {
    performAction(endpoint: string, data?: unknown) {
      const payload = JSON.stringify({ endpoint, data: data ?? {} });
      log("ACTION:", payload);
      return Promise.resolve({});
    }
  },
  notifications: { setupPromiseNotifications: () => {} },
  gameArea: {
    getElement: () => gameArea,
    addLastTurnBanner: () => {},
    removeLastTurnBanner: () => {}
  },
  playerPanels: {
    getElement: (playerId: number) => {
      return document.getElementById(`player_board_${playerId}`);
    },
    addAutomataPlayerPanel: (playerId: number) => document.getElementById(`player_board_${playerId}`),
    getScoreCounter: () => ({ setValue: () => {}, getValue: () => 0, incValue: () => {} })
  },
  dialogs: { showMessage: () => {}, showMoveUnauthorized: () => {} },
  states
} as unknown as Bga<CustomPlayer, CustomGamedatas>;

// ── Import Game (after globals are set) ───────────────────────────────────────

import { Game } from "../../src/Game";

// ── Run setup and notification replay ─────────────────────────────────────────

async function main() {
  log("Instantiating Game...");
  const game = new Game(mockBga);
  // Wire bgaFormatText so format_string_recursive can call it
  (globalStub.gameui as HarnessGameui).bgaFormatText = (str: string, args: unknown) => game.bgaFormatText(str, args);

  log("Calling game.setup()...");
  game.setup(gamedatas);

  // Simulate framework calling onLeavingState + onEnteringState for a state transition
  let currentStateName: string | null = null;

  // gamestate: { name, active_player, args }
  // privateAlreadyUnwrapped: true when _private is already the current player's opInfo (gameStateChange notif)
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  function enterState(gamestate: any, privateAlreadyUnwrapped = false) {
    const handler = statesRegistry[gamestate.name];
    const isActive = String(gamestate.active_player) === String(currentPlayerId);
    if (currentStateName) {
      const leavingHandler = statesRegistry[currentStateName];
      if (leavingHandler?.onLeavingState) {
        log(`Leaving state: ${currentStateName}`);
        leavingHandler.onLeavingState({}, isActive);
      }
    }
    currentStateName = gamestate.name;
    const titleEl = document.getElementById("pagemaintitletext");
    const actionsEl = document.getElementById("generalactions");
    if (titleEl) titleEl.innerHTML = "";
    if (actionsEl) actionsEl.innerHTML = "";
    if (handler?.onEnteringState) {
      log(`Entering state: ${gamestate.name} (active=${isActive})`);
      const args = { ...(gamestate.args ?? {}) };
      // Real BGA framework unwraps _private[playerId] before calling onEnteringState.
      // In gamedatas it's keyed by player_id; in gameStateChange notif it's already unwrapped.
      if (!privateAlreadyUnwrapped && args._private) {
        args._private = args._private[currentPlayerId] ?? args._private[String(currentPlayerId)] ?? args._private;
      }
      handler.onEnteringState(args, isActive);
    } else {
      log(`No onEnteringState handler for state: ${gamestate.name}`);
    }
  }

  // Simulate framework calling onEnteringState for the current game state (as BGA does after reload)
  if (gamedatas.gamestate) {
    enterState(gamedatas.gamestate);
  }

  const logsEl = document.getElementById("logs");
  let logCounter = 0;

  function appendLogEntry(text: string) {
    if (!logsEl || !text.trim()) return;
    const entry = document.createElement("div");
    entry.className = "log log_replayable";
    entry.id = `log_${++logCounter}`;
    const box = document.createElement("div");
    box.className = "roundedbox";
    box.innerHTML = text;
    entry.appendChild(box);
    logsEl.appendChild(entry);
  }

  log(`Replaying ${notifications.length} notification(s)...`);
  let skippedPrivate = 0;
  for (const notif of notifications) {
    // A private notification only ever reaches its owner, so replaying it for anyone else would
    // render information the real client never receives - and hide a genuine leak.
    if (notif.channel === "player" && Number(notif.player_id) !== currentPlayerId) {
      skippedPrivate++;
      continue;
    }
    // Append game log entry for any notification with a non-empty log string
    const logStr: string = notif.log ?? "";
    if (logStr.trim()) {
      const args = Array.isArray(notif.args) ? {} : (notif.args ?? {});
      const text = (globalStub.gameui as HarnessGameui).format_string_recursive(logStr, args);
      appendLogEntry(text);
    }

    if (notif.type === "gameStateChange") {
      // Framework-level: call onLeavingState for old state, onEnteringState for new state.
      // notif.args = { id, name, active_player, type, args } — _private already unwrapped by PHP.
      enterState(notif.args, true);
      continue;
    }
    const handler = (game as unknown as Record<string, unknown>)[`notif_${notif.type}`];
    if (typeof handler === "function") {
      await handler.call(game, notif.args);
    } else {
      // Skip notifications with no handler (e.g. undoRestorePoint, tableWindow, etc.)
    }
  }
  if (skippedPrivate) {
    log(`Skipped ${skippedPrivate} private notification(s) addressed to other players`);
  }

  // ── Inline CSS ───────────────────────────────────────────────────────────────

  // Harness layout CSS (minimal BGA structural rules, source-controlled)
  const harnessCommonCss = path.join(__dirname, "common.css");
  {
    const style = document.createElement("style");
    style.textContent = fs.readFileSync(harnessCommonCss, "utf8");
    document.head.appendChild(style);
  }

  // Game CSS
  const cssPath = path.join(repoRoot, "wayfarers.css");
  if (fs.existsSync(cssPath)) {
    const style = document.createElement("style");
    style.textContent = fs.readFileSync(cssPath, "utf8");
    document.head.appendChild(style);
    log("Inlined harness/common.css + wayfarers.css");
  } else {
    console.warn("wayfarers.css not found - run npm run build:scss first");
  }

  // ── Inject harness script (survives serialization, runs in browser) ──────────

  const harnessScript = document.createElement("script");
  harnessScript.textContent = `
    // Harness: intercept clicks on action buttons and log the action payload to console
    (function() {
      document.addEventListener("click", function(e) {
        var btn = e.target.closest("[data-action]");
        if (!btn) return;
        var raw = btn.getAttribute("data-action");
        var display = raw;
        try {
          var parsed = JSON.parse(raw);
          if (parsed.data && typeof parsed.data.data === "string") {
            parsed.data = JSON.parse(parsed.data.data);
          }
          display = JSON.stringify(parsed);
        } catch(_) {}
        console.log("ACTION:", display);
      });
    })();
  `;
  document.body.appendChild(harnessScript);

  // ── Tooltip registry section ─────────────────────────────────────────────────

  // ── Click handler registry section ──────────────────────────────────────────

  const clickableEls = Array.from(document.querySelectorAll("[_lis], [data-action]")) as HTMLElement[];
  if (clickableEls.length > 0) {
    const section = document.createElement("div");
    section.id = "harness-click-registry";
    section.style.cssText = "margin:16px;font:12px monospace;";
    let inner = `<details><summary style="cursor:pointer;padding:4px;background:#ddd;border:1px solid #aaa;"><b>Click handlers (${clickableEls.length} elements)</b></summary><div style="display:flex;flex-wrap:wrap;gap:8px;padding:8px;border:1px solid #aaa;background:#f9f9f9;">`;
    for (const el of clickableEls) {
      const id = el.id || "(no id)";
      const classes = Array.from(el.classList).join(" ") || "(no class)";
      const action = el.getAttribute("data-action");
      let actionLabel = "onToken";
      if (action) {
        try {
          const parsed = JSON.parse(action);
          if (parsed.data && typeof parsed.data.data === "string") parsed.data = JSON.parse(parsed.data.data);
          actionLabel = JSON.stringify(parsed);
        } catch (_) {
          actionLabel = action;
        }
      }
      inner += `<div style="width:fit-content;max-width:500px;padding:6px;border:1px solid #ccc;background:#fff;">`;
      inner += `<div style="color:#666;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${id}">${id}</div>`;
      inner += `<div style="color:#888;font-size:11px;margin-bottom:2px;">${classes}</div>`;
      inner += `<div>${actionLabel}</div>`;
      inner += `</div>`;
    }
    inner += `</div></details>`;
    section.innerHTML = inner;
    document.body.appendChild(section);
    log(`Click handler registry: ${clickableEls.length} elements`);
  }

  if (tooltipRegistry.size > 0) {
    const section = document.createElement("div");
    section.id = "harness-tooltip-registry";
    section.style.cssText = "margin:16px;font:12px monospace;";
    let inner = `<details><summary style="cursor:pointer;padding:4px;background:#ddd;border:1px solid #aaa;"><b>Tooltip registry (${tooltipRegistry.size} entries)</b></summary><div style="display:flex;flex-wrap:wrap;gap:8px;padding:8px;border:1px solid #aaa;background:#f9f9f9;">`;
    for (const [nodeId, html] of tooltipRegistry) {
      inner += `<div style="width:fit-content;max-width:500px;padding:6px;border:1px solid #ccc;background:#fff;"><div style="color:#666;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${nodeId}">${nodeId}</div><div>${html}</div></div>`;
    }
    inner += `</div></details>`;
    section.innerHTML = inner;
    document.body.appendChild(section);
    log(`Tooltip registry: ${tooltipRegistry.size} entries`);
  }

  // --- Wicon gallery section (icon-to-op coverage) ----------------------------
  // Cross-references the wicon_* classes in wayfarers.css with the wicon rule fields in the material:
  // flags material wicons with no CSS class (red) and lists ops with no wicon at all. Also written
  // standalone to staging/wicons.html + wicons.png (the snapshot viewport crops the page bottom,
  // and visual icon verification needs every cell readable).

  const cssText = fs.existsSync(cssPath) ? fs.readFileSync(cssPath, "utf8") : "";
  const wiconClasses = new Set(cssText.match(/wicon_\w+/g) ?? []);
  const tokenTypes = (gamedatas.token_types ?? {}) as Record<string, { wicon?: string }>;
  const wiconUsers = new Map<string, string[]>();
  for (const [key, rules] of Object.entries(tokenTypes)) {
    if (!rules?.wicon) continue;
    if (!wiconUsers.has(rules.wicon)) wiconUsers.set(rules.wicon, []);
    wiconUsers.get(rules.wicon)!.push(key);
  }
  const allWicons = [...new Set([...wiconClasses, ...wiconUsers.keys()])].sort();
  const opsWithoutWicon = Object.keys(tokenTypes)
    .filter((k) => k.startsWith("Op_") && !tokenTypes[k]?.wicon)
    .sort();

  let galleryInner = `<div style="display:flex;flex-wrap:wrap;gap:8px;padding:8px;border:1px solid #aaa;background:#f9f9f9;">`;
  for (const w of allWicons) {
    const noCss = !wiconClasses.has(w);
    const users = (wiconUsers.get(w) ?? []).join(", ");
    galleryInner += `<div style="width:110px;padding:6px;border:2px solid ${noCss ? "red" : "#ccc"};background:#fff;text-align:center;">`;
    galleryInner += `<div class="wicon ${w}" style="height:48px;margin:0 auto;"></div>`;
    galleryInner += `<div style="overflow-wrap:break-word;">${w}${noCss ? "<br><b style='color:red'>no CSS</b>" : ""}</div>`;
    galleryInner += `<div style="color:#888;overflow-wrap:break-word;">${users || "(unused)"}</div></div>`;
  }
  galleryInner += `</div><div style="margin-top:8px;padding:8px;border:1px solid #aaa;background:#fff;">`;
  galleryInner += `<b>Ops without wicon (${opsWithoutWicon.length}):</b> ${opsWithoutWicon.join(", ")}</div>`;

  {
    const section = document.createElement("div");
    section.id = "harness-wicon-gallery";
    section.style.cssText = "margin:16px;font:12px monospace;";
    section.innerHTML = `<details><summary style="cursor:pointer;padding:4px;background:#ddd;border:1px solid #aaa;"><b>Wicon gallery (${allWicons.length} icons; ${opsWithoutWicon.length} ops without)</b></summary>${galleryInner}</details>`;
    document.body.appendChild(section);
    log(`Wicon gallery: ${allWicons.length} icons, ${opsWithoutWicon.length} ops without wicon`);
  }

  // ── Write snapshot ────────────────────────────────────────────────────────────

  const snapshotPath = path.join(stagingDir, "snapshot.html");
  fs.writeFileSync(snapshotPath, document.documentElement.outerHTML, "utf8");
  log(`Wrote staging/snapshot.html`);

  const wiconsPath = path.join(stagingDir, "wicons.html");
  fs.writeFileSync(
    wiconsPath,
    `<!DOCTYPE html><html><head><meta charset="utf-8"><style>${cssText}</style></head>` +
      `<body style="font:12px monospace;background:#fff;margin:8px;">${galleryInner}</body></html>`,
    "utf8"
  );
  log(`Wrote staging/wicons.html`);

  screenshot(snapshotPath);
  screenshot(wiconsPath, "wicons.png", "1200", "1000");
}

// ── Screenshot via headless Chrome (optional) ─────────────────────────────────
// The inlined CSS uses url(img/...), which resolves relative to the snapshot in
// staging/, so we symlink staging/img -> ../img. Skips cleanly if no Chrome.
function findChrome(): string | null {
  if (process.env.CHROME_BIN) return process.env.CHROME_BIN;
  for (const bin of ["google-chrome-stable", "google-chrome", "chromium", "chromium-browser"]) {
    try {
      execFileSync("which", [bin], { stdio: "ignore" });
      return bin;
    } catch {
      // not found, try next
    }
  }
  return null;
}

// Screenshot dimensions are configurable via env vars (window size sets both the
// width and the output aspect ratio; scale factor sets DPI/sharpness):
//   HARNESS_SHOT_WIDTH  (default 2400)  HARNESS_SHOT_HEIGHT (default 1600)
//   HARNESS_SHOT_SCALE  (default 1)     e.g. 2 for a crisp hi-DPI capture
function screenshot(snapshotPath: string, pngName: string = "snapshot.png", forceWidth?: string, forceHeight?: string): void {
  const chrome = findChrome();
  if (!chrome) {
    log(`Screenshot skipped (no Chrome found; set CHROME_BIN to enable)`);
    return;
  }
  const width = forceWidth ?? process.env.HARNESS_SHOT_WIDTH ?? "2400";
  const height = forceHeight ?? process.env.HARNESS_SHOT_HEIGHT ?? "1600";
  const scale = process.env.HARNESS_SHOT_SCALE ?? "1";
  const imgLink = path.join(stagingDir, "img");
  // Idempotent: existsSync follows the link, so a dangling img/ link reads as absent and the second
  // screenshot call would retry the symlink and throw EEXIST. Create it, tolerate already-there.
  try {
    fs.symlinkSync("../img", imgLink);
  } catch (e) {
    if ((e as NodeJS.ErrnoException).code !== "EEXIST") throw e;
  }
  const pngPath = path.join(stagingDir, pngName);
  try {
    execFileSync(
      chrome,
      [
        "--headless=new",
        "--no-sandbox",
        "--disable-gpu",
        "--hide-scrollbars",
        `--force-device-scale-factor=${scale}`,
        `--window-size=${width},${height}`,
        "--default-background-color=FFFFFFFF",
        `--screenshot=${pngPath}`,
        `file://${snapshotPath}`
      ],
      { stdio: "ignore" }
    );
    log(`Wrote staging/${pngName} (${width}x${height} @${scale}x)`);
  } catch (e) {
    log(`Screenshot skipped (Chrome failed): ${(e as Error).message}`);
  }
}

main().catch((err) => {
  console.error("render.ts failed:", err);
  process.exit(1);
});
