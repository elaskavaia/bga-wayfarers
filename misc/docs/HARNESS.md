# Local harness

## Overview

The local harness lets you develop and validate game UI without a real BGA server. You set up a specific game state, run one command, and get a static HTML snapshot (plus a rendered PNG) showing how the game looks - tokens, buttons, tooltips, and game log - all rendered by the real client code.

**For AI agents:** `npm run play` also writes `staging/snapshot.png` (a headless-Chrome screenshot of the snapshot). Read that PNG with your image tool to **visually validate UI work** - board/sprite alignment, sizing, colours - not just the HTML structure.

**Goal**:

- catch UI bugs (wrong tokens, missing buttons, broken tooltips, bad log text) locally before deploying to BGA.
- ability to debug server code with local php debugger

## Problem

The game is client-server. Server logic (PHP) and client logic (TypeScript) are tightly coupled: the server drives the game state and emits notifications; the client renders them. The real BGA server is not accessible locally, so there is no way to test the two together without deploying.

## Design

The key insight is that the client-server contract is narrow: two JSON payloads.

- **Game state** (`gamedatas`) - a full snapshot returned on page load; client calls `game.setup(gamedatas)` to build the DOM
- **Notifications** - a sequence of events emitted during an action; client calls `notif_xxx(args)` for each one in order

We can produce both payloads locally using the existing PHP test infrastructure (same in-memory stubs as unit tests), then feed them to the real client code running in a simulated browser (JSDOM).

Two parts run in sequence:

1. **PHP runner** (`npm run play:only`) - sets up game state via a scenario or debug function, captures the resulting game state and notifications as JSON
2. **JS renderer** (`npm run play:render`) - loads that JSON into a simulated browser, runs the client code (`setup` + notification replay), writes a static HTML snapshot, and screenshots it to a PNG via headless Chrome

The snapshot has the game CSS inlined and extra inspection sections appended: a click-handler registry, a tooltip registry, a wicon gallery, and a game log - so it can be read as a file without running a browser. The PNG (`staging/snapshot.png`) is a true visual render for checking layout and graphics.

`npm run play` runs both parts and is part of `npm run predeploy`.

## What we had to stub

The BGA framework does a lot of work that normally only runs on their servers or in their browser environment. To run locally we had to replicate or stub everything the game code depends on.

**Server side (PHP):**

- **Database** - all token and machine state normally lives in MySQL; replaced with in-memory implementations (`phptests/TokensInMem.php`, `phptests/MachineInMem.php`) that mirror the real DB API
- **Framework base class** (`Table`) - the BGA `Table` class wires up DB connections, player data, notifications, game state transitions; replaced with stubs in `bga-sharedcode` that provide the same interface without a real server
- **Notify** - `$this->notify->all(...)` normally pushes to BGA's real-time channel; the `Notify` stub in `bga-sharedcode` records all calls to `$this->notify->log` and supports `addDecorator`, so no replacement is needed
- **Game state machine** - `gamestate->jumpToState()`, `changeActivePlayer()`, etc. are normally server-side BGA infra; stubbed to track current state in memory
- **Harness driver** (`GameDriver`) - appends the `gamestate` field to `getAllDatas()` (real BGA adds this automatically on reload, our stub does not); also handles state persistence via `toJson`/`fromJson` and endpoint dispatch via reflection
- **Randomness** - `GameWrapper::bgaRand` returns the queued values from `$randQueue`, or the minimum, so scenarios are reproducible

**Client side (JS):**

- **HTML template** - the real BGA page is served by their platform; we provide a minimal `template.html` with the same element IDs the client expects (`#game_play_area`, `#generalactions`, `#pagemaintitletext`, `#player_boards`, `#logs`, etc.)
- **Minimal CSS** - BGA's own layout CSS is not available; `common.css` provides just enough structural rules (flex layout, action bar, player panels, log) for the snapshot to be readable
- **Framework globals** - `$`, `_`, `gameui`, `ebg` and other globals the client code calls; stubbed with enough behaviour to let `game.setup()` and notification handlers run without errors
- **`format_string_recursive`** - BGA's log formatting function; fully reimplemented including i18n, nested log objects, separator joining, and `bgaFormatText` hook for token/place name resolution
- **Tooltip capture** - `addTooltipHtml` normally registers tooltips with a real dijit widget; intercepted to collect them in a registry that is appended to the snapshot
- **Player panels** - normally injected into the page by BGA's server-side rendering; built from `gamedatas.players` in the renderer

## What the harness does NOT cover

- **Hover behaviour.** Tooltips are captured in a registry, not attached to a live dijit widget, so the snapshot cannot show which tooltip wins on hover or how wide the tooltip box renders. Ancestor-precedence logic is unit-tested instead (see the `updateCaravanCellTooltips` block in `src/tests/Game.spec.ts`).
- **Anything driven by real user input** - clicks, drags, pinch zoom. The click registry lists the handlers, it does not fire them.
- **The real BGA page chrome** - their layout CSS, the top bar, the notification/log panel styling. Only `common.css` structure plus our own `wayfarers.css`.
- **Real player preferences** - `getUserPreference` returns 0 (the default) for every code.

To check something the snapshot cannot show, build a small standalone HTML that inlines `wayfarers.css` and screenshot it with the same headless Chrome (see the `screenshot()` helper in `render.ts`).

## Saved state

To avoid re-running setup (which involves shuffling) on every run, the harness persists the full in-memory DB to `staging/db.json` after each run and can reload it at the start of the next with `--db`. This makes runs fast and reproducible.

## Scenarios and debug functions

Two ways to drive the server:

- **Scenario** - a script of sequential actions (same endpoints as the real client: `action_resolve`, `action_skip`, etc.), used to build up a saved state incrementally
- **Debug function** - a single PHP function that sets up a specific state in one shot, used for inspecting a particular operation or UI state without replaying history

---

## Usage

```
npm run play                                       # default setup scenario + render
npm run play:only                                  # PHP side only (writes staging/*.json)
npm run play:render                                # JS side only (re-render the last staging/*.json)
```

For other options call `play.php` directly (it needs `APP_GAMEMODULE_PATH`, the same variable `npm run tests` sets):

```
APP_GAMEMODULE_PATH=~/git/bga-sharedcode/misc/ php8.4 phptests/Harness/play.php --script phptests/Harness/plays/setup.json
APP_GAMEMODULE_PATH=~/git/bga-sharedcode/misc/ php8.4 phptests/Harness/play.php --debug debug_finalScoring
APP_GAMEMODULE_PATH=~/git/bga-sharedcode/misc/ php8.4 phptests/Harness/play.php --debug debug_q --db staging/db.json
APP_GAMEMODULE_PATH=~/git/bga-sharedcode/misc/ php8.4 phptests/Harness/play.php --output /tmp/out
```

With no `--script` and no `--debug` it runs `phptests/Harness/plays/setup.json`. A bare (non-flag) argument is taken as the script path. Then render:

```
npm run play:render                                # renders plays/setup.json's result
npm run play:render -- <playname>                  # same, for a named play
```

Then read `staging/snapshot.html`, or **view `staging/snapshot.png`** for a visual render.

### Screenshot (`staging/snapshot.png`)

`npm run play` screenshots the snapshot with headless Chrome so the result can be inspected visually (AI agents: read the PNG to validate UI). It:

- finds a browser via `$CHROME_BIN`, then `google-chrome-stable` / `google-chrome` / `chromium` / `chromium-browser`, and **skips with a log message** if none is installed (so `play` never fails without a browser);
- symlinks `staging/img -> ../img` so the inlined `url(img/...)` resolves.

`staging/` is gitignored, so the PNG and symlink are never committed.

Size is configurable via env vars:

| Var                   | Default | Effect                                                              |
| --------------------- | ------- | ------------------------------------------------------------------- |
| `HARNESS_SHOT_WIDTH`  | `2400`  | viewport width (px)                                                 |
| `HARNESS_SHOT_HEIGHT` | `1600`  | viewport height (px); width:height is the output aspect ratio       |
| `HARNESS_SHOT_SCALE`  | `1`     | DPI multiplier (device scale factor); `2` doubles output resolution |

Width/height change the _layout_ viewport (wider reflows the page wider; taller captures more vertical content before it is cut); scale just renders the same layout sharper. A narrow width is how you check mobile layout:

```
HARNESS_SHOT_WIDTH=3600 npm run play               # big width
HARNESS_SHOT_SCALE=2 npm run play                  # hi-DPI / crisp
HARNESS_SHOT_WIDTH=390 HARNESS_SHOT_HEIGHT=844 npm run play:render   # phone portrait
```

`HARNESS_VERBOSE=1` keeps the client's own `console.log` output instead of suppressing it.

## Snapshot inspection

Sections appended to `staging/snapshot.html`:

- `#pagemaintitletext` - prompt from `getPrompt()` for the current operation
- `#generalactions` - action buttons with `data-action` attributes
- `#logs` - formatted game log entries
- `#harness-click-registry` - all clickable elements with id, class, and action payload
- `#harness-tooltip-registry` - all registered tooltips, by node id
- `#harness-wicon-gallery` - every `wicon_*` in the CSS and in the material, flagging icons with no CSS class (red) and ops with no wicon; also written standalone to `staging/wicons.html` + `staging/wicons.png`

## Adding debug functions

Game-wide `debug_*` functions live in `modules/php/Game.php` (`debug_op`, `debug_q`, `debug_finalScoring`, `debug_goToState`, `debug_playAutomatically`, `debug_maxRes`, ...); harness-only ones go in `phptests/Harness/GameWrapper.php` (`debug_setup`). Params are matched by name via reflection.

Typical pattern:

```php
public function debug_op(string $type) {
    $this->machine->push($type, $this->getPlayerColorById((int) $this->getCurrentPlayerId()), []);
    $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
}
```

Run with:

```bash
APP_GAMEMODULE_PATH=~/git/bga-sharedcode/misc/ php8.4 phptests/Harness/play.php --script phptests/Harness/plays/setup.json --debug debug_op
npm run play:render
```

---

## Implementation details

### npm scripts

```json
"play:only": "APP_GAMEMODULE_PATH=~/git/bga-sharedcode/misc/ php8.4 phptests/Harness/play.php",
"play:render": "TS_NODE_PROJECT=phptests/Harness/tsconfig.json node -r ts-node/register phptests/Harness/render.ts",
"play": "run-s play:only play:render"
```

### PHP runner - `phptests/Harness/play.php` + `GameDriver` + `GameWrapper`

**`play.php`** is a thin bootstrap: requires `phptests/_autoload.php`, then calls `GameDriver::main(new GameWrapper(), $argv, __DIR__, staging)`.

**`GameDriver`** is fully generic (no game-specific imports). It orchestrates the harness run:

1. `main(game, argv, baseDir, stagingDir)` - static entry point: parses CLI args (`--debug`, `--script`/`--scenario`, `--db`, `--output`), constructs the driver, runs steps/debug, saves output
2. Constructor takes a `Table&HarnessGameInterface` instance, output dir, and current player ID; gets game name from `$game->getGameName()`; builds a state name map by scanning `modules/php/States/` via the `Bga\Games\{name}\States\` namespace
3. `loadDbFromJson(dbPath)` - calls `$game->loadDbState($db)` + restores gamestate/players (framework-level)
4. `runSteps()` - for each step: dispatch the endpoint, run `onEnteringState()`, emit a synthetic `gameStateChange` notification
5. `runDebug()` - calls a single `debug_*` function, then dispatch + emit
6. `saveDbToJson()` - calls `$game->saveDbState()` + saves gamestate/players to `<output>/db.json`
7. `saveGamedatas()` / `saveNotifications()` - writes JSON for the JS renderer

**`GameWrapper`** is the game-specific part. It extends `Game` directly (not `GameUT`) and implements `HarnessGameInterface`. See "GameWrapper contract" below.

### JS renderer - `phptests/Harness/render.ts`

1. Read `staging/gamedatas.json` and `staging/notifications.json`
2. Load `phptests/Harness/template.html` as the JSDOM base document
3. Build player panels from `gamedatas.players` (framework normally generates these server-side)
4. Set up JSDOM globals and BGA framework stubs (`$`, `_`, `gameui`, `ebg`, etc.)
   - `gameui.addTooltipHtml(nodeId, html)` - captured in a tooltip registry
   - `gameui.format_string_recursive(str, args)` - full implementation: null handling, i18n, nested `{log,args}` recursion, separator joining, `${key}` substitution; calls `gameui.bgaFormatText` hook
   - `gameui.bgaFormatText` - wired to `game.bgaFormatText(str, args)` so token/place names resolve correctly in the log
5. Instantiate `Game`; wire `bgaFormatText`
6. Call `game.setup(gamedatas)` - builds initial DOM
7. Call `enterState(gamedatas.gamestate)` - triggers `onEnteringState` (sets title + buttons)
8. For each notification:
   - If `notif.log` is non-empty: format and append to `#logs`
   - If `type === "gameStateChange"`: re-enter state (clears + re-renders title/buttons)
   - Otherwise: call `await game.notif_<type>(notif.args)` if handler exists
9. Inline `common.css` + `wayfarers.css` into `<head>`
10. Append `#harness-click-registry`, `#harness-tooltip-registry`, `#harness-wicon-gallery`, and a click-logging script
11. Write `staging/snapshot.html` and `staging/wicons.html`
12. Screenshot both via headless Chrome (`screenshot()` / `findChrome()`); skips with a log if no browser is found

Note: the renderer reads the BUILT client (`modules/js/Game.js` is produced by `npm run build:ts`) through the same TypeScript sources, so run `npm run build` after editing `src/` before trusting a snapshot of CSS.

### Scenario format

`phptests/Harness/plays/<name>.json`:

```json
{
  "current_player_id": 10,
  "reset": true,
  "steps": [
    { "endpoint": "debug_setup", "reload": true },
    { "endpoint": "action_resolve", "data": { "target": "worker_green_1" } }
  ]
}
```

Optional per-step: `"reload": true` - writes `gamedatas.json` after that step.

Available endpoints - **actions**: `action_*`; **debug**: any `debug_*` method on `Game` (or `GameWrapper`), params matched by name via reflection.

### DB state format

```json
{
  "tokens": [{ "key": "worker_green_1", "location": "tableau_6cd0f6", "state": 0 }],
  "machine": [{ "id": 1, "rank": 1, "type": "turn", "owner": "6cd0f6", "pool": "main", "data": null }],
  "gamestate": { "state_id": 10, "active_player": 10 },
  "players": [
    { "player_id": 10, "player_no": 1, "player_color": "6cd0f6", "player_name": "player1", "player_zombie": 0, "player_eliminated": 0 }
  ]
}
```

---

## GameWrapper contract

`GameDriver` is fully generic - it has no game-specific imports. It communicates with the game through `HarnessGameInterface`, implemented by `GameWrapper`:

- `getGameName(): string` - namespace name (`"wayfarers"`). Used by GameDriver to discover state classes via `Bga\Games\{name}\States\*`
- `saveDbState(): array` - serialize all custom DB tables to an associative array. GameDriver persists this as part of `db.json`
- `loadDbState(array $db, bool $reset = true): void` - restore custom DB tables from the array produced by `saveDbState()`
- `getAllDatas(): array` - return game data for the client (must be `public` - BGA framework declares it `protected`)

These are called by GameDriver but already provided by `BgaFrameworkStubs.php` - no implementation needed:

- `$game->gamestate` - state machine (`jumpToState()`, `changeActivePlayer()`, `getCurrentMainStateId()`)
- `$game->notify` - notifications (`all()`, `_getNotifications()`)
- `$game->getActivePlayerId()`, `$game->getCurrentPlayerId()`, `$game->loadPlayersBasicInfos()`
- `$game->_setCurrentPlayerId()`, `$game->_getCurrentPlayerId()`, `$game->_colors`

Wayfarers additions in `GameWrapper` beyond the contract: `bgaRand` + `$randQueue` (deterministic RNG), `setPlayersNumber`, `setupForTest` (also used by the Campaign tests), `getPrivateStateId` / `getUserPreference` stubs, and `customUndoSavepoint` recording into `$savepointCalls`.

### Reusing for another game

1. **`GameWrapper`** - extend your `Game` class, swap in-memory DB stubs in the constructor, implement `HarnessGameInterface`
2. **`play.php`** - bootstrap: require the autoloader, call `GameDriver::main(new GameWrapper(), $argv, ...)`
3. **`GameDriver.php`** - use as-is (no edits needed)
4. **State classes** in `modules/php/States/` following `Bga\Games\{name}\States\` namespace
5. **`render.ts`** - adjust the CSS filename for your game
