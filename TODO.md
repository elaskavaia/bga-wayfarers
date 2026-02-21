## Server

- Bonus did not ask to move inf bug?

## Client

- Deck tooltips
- Board action tooltips (also dup hot slots?)
- Miniboards
- Visual UI for insp under home space

## Solo Rules

### Phase 1: Scheme Cards Material & AI Setup

- [x] Create `misc/scheme_material.csv` with 6 scheme cards defining: silver value, first action, second action, special upgrade priority, comet flag, card color (blue/red) - images are in img/cards_scheme.jpg
- [x] Run `npm run genmat` to generate scheme card material in Material.php
- [x] Add scheme card tokens to `misc/token_material.csv` (deck_scheme, scheme_display locations)
- [x] Update `setupNewGame()` in Game.php for solo-specific setup:
  - [x] Flip one player board to AI side (choose unused board, negative state = AI side)
  - [x] Do NOT give AI any dice
  - [x] Place AI resource track marker at top-left of Resource Track. Track values 0-7
  - [x] Place AI comet track marker at 0 on Comet Track. Track values 0-10
  - [x] Place AI player marker on starting space of Journal Track
  - [x] Give AI 1 Yellow Worker and 1 Blue Worker
  - [x] Give AI 1 Influence in Yellow Guild and 1 in Blue Guild (no provisions/silver)
  - [x] Shuffle scheme cards into a facedown draw pile
  - [x] Human player is always first player; AI is always second

### Phase 2: AI Turn Infrastructure

- [x] Create `Op_ai_turn.php` - main automa turn operation
  - [x] Determine if AI should Rest (3 faceup Red or 3 faceup Blue scheme cards) or reveal a new scheme card
  - [x] On reveal: draw top scheme card, place faceup to right of draw pile
  - [x] Move AI resource track marker clockwise by scheme card's silver value
  - [x] Resolve resource track effects passed over (comet, guild influence, townsfolk card)
  - [x] Resolve first action on scheme card; fallback to second action if first is impossible
- [x] Integrate automa turn into game flow via `getNextReadyPlayer()` (already returns PLAYER_AUTOMA after human turn)
- [x] Add game state or dispatch logic to route automa turns to `Op_ai_turn` instead of normal `Op_turn`

### Phase 3: AI Prioritization System

- [x] Implement resource track color priority: track position dictates color (Black, Blue, Yellow, Green)
  - [x] Color determines priority for: influencing cards, acquiring upgrade tiles, placing/retrieving workers
  - [x] If priority color yields no benefit, move to next color clockwise
- [x] Implement scheme card sum value calculation: sum of 2 most recent faceup cards (or single card value)
  - [x] Sum value (0-4) determines positional priority: 0-1 = center-most card/tile, higher = outward
  - [x] If AI cannot interact with prioritized target, move to next possible, wrapping around
- [x] Implement inspiration card priority based on resource track row position (top/middle/bottom)

### Phase 4: AI Actions (Scheme Card Resolution)

- [x] AI card acquisition (Op_ai_cardBase.php):
  - [x] Acquire Land/Water/Space/Townsfolk Card (use sum value for position priority)
  - [x] AI ignores all costs except scheme card requirements
  - [x] AI ignores all icons on acquired cards (no comets, no influence, no free upgrades)
  - [x] AI still collects cards for end-game scoring
- [x] AI upgrade tile acquisition (Op_ai_upgAny.php):
  - [x] Use resource track color priority to determine tile color
  - [x] Use sum value for positional selection within that color
  - [x] Place tiles in winding caravan path (bottom L→R, middle R→L, top L→R)
  - [x] AI gains VP from upgrade tiles at end of game but ignores other tile icons
  - [x] Use scheme card's special upgrade priority indicator for Special pink upgrades
  - [x] If priority color has no tiles, move to next color clockwise
  - [x] Track full footprint of multi-cell tiles for collision detection
  - [x] Place tiles alongside board when caravan is full
  - [ ] Rotate rectangular tiles to fit winding path
  - [x] Resolve effects of covered caravan icons when placing tiles
- [x] AI worker placement (Op_ai_placeWorker.php):
  - [x] Green Workers go on Townsfolk Cards
  - [x] Yellow Workers go on Land Cards
  - [x] Blue Workers go on Water Cards
  - [x] When given choice of two workers, prioritize Green
  - [x] Resolve all printed actions of the space
  - [x] Uses positional priority (sum value) to select target card
  - [x] isVoid() check for fallback to second scheme card action
- [x] AI worker retrieval (Op_ai_pickWorker.php):
  - [x] Prioritize Green Workers first
  - [x] Then use resource track color priority for worker color
  - [x] Use sum value to choose among multiple workers of same color
  - [x] Handles card interaction (influence) when picking worker
  - [x] isVoid() check for fallback to second scheme card action
- [x] AI influence on cards (Op_ai_infCard.php):
  - [x] Use resource track color priority for card type selection
  - [x] Use sum value for positional selection
  - [x] Only influence cards with no existing influence
  - [x] AI has unlimited influence (created on demand)
  - [x] isVoid() check for fallback to second scheme card action

### Phase 5: AI Resting

- [x] Check last revealed scheme card for comet icon -> move comet track marker up 1
- [x] AI acquires one of: Space Card, Townsfolk Card, Upgrade Tile, or Influences a Card based on board
- [x] AI journals (Op_ai_journal.php):
  - [x] Path determined by faceup scheme card colors: majority Blue = higher path, majority Red = lower path, tie = most recent card color
  - [x] If only one path available, take that path
  - [x] In last column: AI never takes middle option; if blocked, take other available space
  - [x] Before journaling, check AI position vs human player marker:
    - Behind: spend 1 Black Influence for extra space
    - Same column: spend 2 Black Influence for extra space
    - Ahead: spend 3 Black Influence for extra space
  - [x] AI ignores all journal track requirements but gains all rewards
  - [x] In final column: AI gains Pink Upgrade Tile instead of Inspiration Card
  - [x] Op_ai_upgPink for pink upgrade tile acquisition (extends Op_ai_upgAny)
- [x] Op_ai_rest: refactored rest logic from Op_ai_turn
  - [x] Check comet on most recent scheme card and move comet tracker
  - [x] Acquire based on AI board r1 field
  - [x] Journal
  - [x] Shuffle all scheme cards back into facedown draw pile after rest

### Phase 6: AI Scoring & Game End

- [x] AI still participates in guild majorities (3 VP per guild with most influence)
- [x] AI influence is unlimited - if supply runs out, create more tokens dynamically (Op_infBase.php)
- [x] Solo game option (choose board)
- [x] Game end trigger: AI reaching final journal column also triggers end-game
- [x] All players (human + AI) take one final turn after end-game trigger

### Phase 7: Client-Side UI

- [x] AI player board display (show AI side of flipped board)
- [x] Scheme card reveal animations and faceup display area
- [x] Resource track marker visualization (AI board)
- [x] Comet track marker visualization (AI board)
- [x] AI turn narration/log messages (what the AI did each turn)
- [x] Solo end-game result screen (win/loss vs threshold or AI score)
- [x] Scheme card deck and discard pile UI elements
- [ ] UI support for AI upgrade tiles placed alongside board (state 0, no caravan position)
