# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Board Game Arena (BGA) implementation of the game "Wayfarers". It uses a custom template with minimal Dojo dependencies, featuring TypeScript for client-side code and PHP for server-side game logic.

## Development Commands

### Build and Watch

- `npm run build` - Full build (TypeScript, SCSS, and material generation)
- `npm run build:ts` - Compile TypeScript to wayfarers.js
- `npm run build:scss` - Compile SCSS to wayfarers.css
- `npm run watch:ts` - Watch and compile TypeScript
- `npm run watch:scss` - Watch and compile SCSS
- `npm run genmat` - Generate Material.php from CSV files in misc/

### Testing

- `npm run tests` - Run all PHPUnit tests
- To run a single test file: `APP_GAMEMODULE_PATH=~/git/bga-sharedcode/misc/ phpunit --bootstrap ./modules/php/Tests/_autoload.php modules/php/Tests/<TestFile>.php`
- Note: Tests require APP_GAMEMODULE_PATH environment variable pointing to bga-sharedcode repository

### Code Formatting

- Prettier is configured with PHP plugin (see package.json)
- Print width: 140 characters
- Brace style: 1tbs

## Architecture

### Operation-Based State Machine

The game logic is built around an operation-based state machine pattern:

- **OpMachine** ([modules/php/OpCommon/OpMachine.php](modules/php/OpCommon/OpMachine.php)) - Core state machine that manages operation queue and execution
- **Operation** ([modules/php/OpCommon/Operation.php](modules/php/OpCommon/Operation.php)) - Abstract base class for all game operations
- **Operation implementations** (modules/php/Operations/Op\_\*.php) - Concrete operations like Op_cardDraw, Op_placeDie, Op_gain, etc.
- **ComplexOperation** - Operations that can contain sub-operations (delegates)
- **CountableOperation** - Operations that can be repeated a specific number of times

Operations are queued in the database (DbMachine) and executed sequentially, enabling complex game flows with undo/redo support.

### Material System

Game elements are defined in CSV files and auto-generated into PHP code:

- CSV files in misc/ directory define tokens, cards, locations, operations, etc.
- [misc/other/genmat.php](misc/other/genmat.php) - Script that parses CSV files and generates Material.php
- Generated sections in Material.php are marked with `--- gen php begin <name> ---` and `--- gen php end <name> ---`
- Material.php is partially auto-generated - manual sections exist for constants and error codes

**Important**: When adding new game elements, update the corresponding CSV file and run `npm run genmat` rather than editing Material.php directly.

### Token Management

- **DbTokens** ([modules/php/Db/DbTokens.php](modules/php/Db/DbTokens.php)) - Database layer for token storage
- **PGameTokens** ([modules/php/Common/PGameTokens.php](modules/php/Common/PGameTokens.php)) - Game-specific token logic wrapper
- Tokens represent all physical game pieces (cards, dice, workers, resources, etc.)
- Token locations are managed through location strings (e.g., "deck*folk", "mainarea", "player_board*{color}")

### Game States

State classes in modules/php/States/ handle different game phases:

- GameDispatch - Main game flow dispatcher
- PlayerTurn - Individual player turn handling
- MultiPlayerMaster/MultiPlayerTurnPrivate/MultiPlayerWaitPrivate - Multiplayer coordination
- PlayerTurnConfirm - Turn confirmation state
- MachineHalted - Error/debug state

### Client-Side Structure

TypeScript files in src/ compile to a single wayfarers.js:

- **GameXBody.ts** - Main game class (extends GameMachine), entry point for client logic
- **Game0Basics.ts** - First file in compilation order, basic definitions
- **Game1Tokens.ts** - Token rendering and management
- **GameMachine.ts** - Client-side state machine handling
- **LaAnimations.ts** - Animation utilities
- TypeScript config uses `module: "none"` and `outFile: "wayfarers.js"` to concatenate all files

SCSS files in src/css/ compile to wayfarers.css with GameXBody.scss as the entry point.

### File Naming Conventions

- PHP Operation classes: `Op_<operationName>.php` (e.g., Op_cardDraw.php)
- Material CSV files: `<category>_material.csv` (e.g., token_material.csv, card_material.csv)
- PHP namespaces: `Bga\Games\wayfarers\<Subdirectory>`

## Prepare the game for BGA deployment:

#. Run the full build process: `npm run build`
#. Check for any build errors in TypeScript, SCSS, or material generation
#. Run tests: `npm run tests`
#. Check and fix failed tests
#. Show git status to see which files have changed
#. Check for spelling mistakes and issues in changed code
#. Check to see if new php tests should be added

## Common Development Patterns

### Adding a New Operation

1. Create modules/php/Operations/Op_yourOperation.php extending Operation
2. Implement required methods: effect(), canResolve(), etc.
3. Add operation definition to misc/op_material.csv if it needs material data
4. Run `npm run genmat` to update Material.php
5. Add tests in modules/php/Tests/

### Adding New Game Elements

1. Update the appropriate CSV file in misc/ (token_material.csv, card_material.csv, etc.)
2. Run `npm run genmat` to regenerate Material.php
3. Material generation uses pipe (|) as field separator
4. Translatable fields: name, tooltip, tooltip_action, text
5. Special directives in CSV: `#set _tr=field` (mark field as translatable), `#set _noquotes=field` (no quotes in output)

### Working with Tests

- Tests use an in-memory implementation (MachineInMem, TokensInMem) for fast execution
- Test base classes provide game setup utilities
- APP_GAMEMODULE_PATH must point to bga-sharedcode for BGA framework dependencies

### Solo Mode / Automa (AI Opponent)

The game supports a solo mode where a human player plays against an AI opponent ("Aida"). The automa is not a real BGA player — it exists only in the game's own data structures.

**Core constants and helpers** (in [modules/php/Base.php](modules/php/Base.php)):

- `PLAYER_AUTOMA = 1` — synthetic player ID (not in the BGA `player` table)
- `getAutomaColor()` returns `"ffffff"` — the automa's internal color key
- `isSolo()` — true when only 1 real player is in the game
- `getNextReadyPlayerId()` — alternates between the human player and `PLAYER_AUTOMA` in solo mode
- `loadPlayersBasicInfosWithBots()` — injects the automa entry into the players array (guarded by `isSolo()`)
- `custom_getPlayerColorById()`, `custom_getPlayerNameById()`, `custom_getPlayerNoById()` — automa-safe wrappers around BGA framework methods that would crash for a non-DB player
- `switchActivePlayer()` — skips player IDs <= 2 (automa is never the BGA "active player")

**Turn routing** ([modules/php/Operations/Op_turn.php](modules/php/Operations/Op_turn.php)):

- `Op_turn::auto()` checks if the current player is `PLAYER_AUTOMA`; if so, it queues `ai_turn` for the automa color and returns true (no human UI needed)
- `Game::queueNextTurnOrEnd($playerId)` — in [modules/php/Game.php](modules/php/Game.php), uses `getNextReadyPlayerId()` to alternate turns; queues `turn` or `finalScoring` based on end-game state

**AI turn logic** ([modules/php/Operations/Op_ai_turn.php](modules/php/Operations/Op_ai_turn.php)):

- Extends `AiOperation`; overrides `auto()` to fully resolve the AI turn without user input
- Determines Rest vs Reveal based on faceup scheme card colors (3+ of one color → rest)
- On reveal: draws scheme card to `tableau_ffffff`, queues `ai_res` for resource track movement, resolves actions
- On rest: queues `ai_rest` which handles comet check, acquisition, journaling, and shuffle
- Action resolution: tries `r1` first; if `isVoid()` (no valid targets and not skippable), falls back to `r2`

**AI operations** (in `modules/php/Operations/Op_ai_*.php`):

- All AI operations extend **AiOperation** ([modules/php/OpCommon/AiOperation.php](modules/php/OpCommon/AiOperation.php)) which extends `CountableOperation`
- AI ops always auto-resolve (`auto()` returns true) — they never enter a player input state
- `Op_ai_cardBase` — shared base for AI card acquisition (Land, Water, Space, Folk); mapped via `#set class=Op_ai_cardBase` in op_material.csv so multiple op types share one implementation
- `Op_ai_upgAny` — AI upgrade tile acquisition with color priority, positional selection, and winding caravan path placement
- `Op_ai_upgPink` — extends Op_ai_upgAny for pink/special tiles using scheme card `p` field for priority
- `Op_ai_placeWorker` — AI worker placement; reads allowed colors from params (e.g., `green/blue`), maps worker color to card type, uses positional priority
- `Op_ai_pickWorker` — AI worker retrieval; prioritizes green workers, then uses resource track color priority, uses positional priority for same-color workers, handles card interaction
- `Op_ai_infCard` — AI influence on cards; uses resource track color priority for card type selection, positional priority for position within type, only targets cards with no existing influence
- `Op_ai_cardInteract` — AI card interaction; extends Op_cardInteract, offers human player choice of coin/food when AI interacts with their influence, moves workers from card to AI tableau
- `Op_ai_journal` — AI journaling with path selection based on scheme card color majority; connection order from CSV determines High/Low (first = upper, last = lower)
- `Op_ai_rest` — AI rest: comet check, acquire per AI board, journal, shuffle
- `Op_ai_shuffle` — shuffles scheme cards back to deck
- `Op_ai_res` — moves AI resource track marker clockwise
- `Op_ai_focusAction` — executes AI board's focus action (from aiboard `t` field)
- AI operations are registered in `misc/op_material.csv` with `ai_` prefix (e.g., `ai_turn`, `ai_cardLand`, `ai_placeWorker`, `ai_infCard`, `ai_focusAction`)

**Operation parameters**:

- Operations can receive parameters via parenthesized syntax: `ai_placeWorker(green/blue)`, `cardDraw(land)`, `upgGreen(free)`
- Accessed via `getParams()` (full string) or `getParam($index)` (comma-separated)
- Slash-separated values are a convention for priority lists (parsed by the operation itself)

**AI prioritization helpers** (in AiOperation):

- `getPositionPriority()` — sum of silver values of the 2 most recent scheme cards (1-4); determines which mainarea slot to target
- `getNextPositionPriorityDirection($prev)` — returns 1 or -1 for wraparound direction based on priority value
- `getResourceMarkerPosition()` / `getResourceMarkerRules()` — reads the resource track marker position and looks up rules (color priority, inspiration card row)
- `getRecentCard()` — returns the most recently revealed scheme card
- `aiGetBoardNumber()` — derives AI board number from negative state of `pboard_ffffff`
- `countCards($owner, $color)` — counts faceup scheme cards of given color
- `custom_array_rotate()` — global helper in Base.php for rotating arrays with direction (used for clockwise color priority)

**Worker placement** (board actions defined in [misc/action_material.csv](misc/action_material.csv)):

- Worker color determines valid card types: green→folk, yellow→land, blue→water
- Board actions keyed as `action_{cardType}_{position}` with field `r` containing the operation expression
- When a worker is placed on a card, the action rule at that position is queued
- Workers become public resources once placed (location = card key); retrieved by any player via `pickWorker`

**Scheme cards** (defined in [misc/scheme_material.csv](misc/scheme_material.csv)):

- 6 cards (3 blue, 3 red) with fields: `t` (color), `c` (silver value 0-2), `r1` (primary action), `r2` (fallback action), `p` (upgrade priority), `comet` (0/1)
- Blue cards have `ai_placeWorker(color_options)` as r1; Red cards have influence+card-acquisition patterns
- Stored in `deck_scheme`; revealed cards move to `tableau_ffffff` with incrementing state values for ordering

**AI board data** (also in scheme_material.csv):

- `aiboard_{num}` entries define per-board rules: `t` (focus action), `r1` (rest acquisition), `r2` (resource track bonus)
- `spot_res_{num}` entries define resource track positions: `t` (priority color), `c` (inspiration card order)
- Board number is derived from the negative state of `pboard_ffffff`

**Journal track** (defined in [misc/journal_material.csv](misc/journal_material.csv)):

- Positions (`jpos_{num}`) have `conn` field listing connected positions; connection order matters: first = upper/North path, last = lower/South path
- Connectors (`jconn_{from}_{to}_{side}`) define requirements (`r` field) and side of board (`gw` field)
- AI ignores connector requirements but uses connection order for path preference (blue = upper/first, red = lower/last)

**AI-specific tokens**:

- `tracker_res_ffffff` — resource track marker (state 0-7, moves clockwise by silver value)
- `tracker_comet_ffffff` — comet track marker (state 0-10)
- Scheme cards in `tableau_ffffff` — faceup revealed cards (state >= 2 indicates reveal order)
- Workers `worker_blue_*`, `worker_yellow_*`, `worker_green_*` — in `tableau_ffffff` when in AI supply, on card keys when placed

## BGA-Specific Considerations

- This is not for beginners - assumes familiarity with BGA development
- Deployment uses SFTP (see BGA documentation for VSCode setup)
- \_ide_helper.php provides IDE autocomplete for BGA framework
- Follow BGA framework conventions for notifications, database queries, and state transitions
- Game uses modern BGA framework with namespace support: `Bga\Games\wayfarers`
