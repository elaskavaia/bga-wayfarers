# Design notes

## Test layout

There are two kinds of PHP tests and the file name is what says which one you are looking at.

### Unit tests - `phptests/<ClassUnderTest>Test.php`

- One file per class under test, named exactly after it: `Op_journalTest.php` covers `Op_journal`,
  `EndScoreTest.php` covers `States\EndScore`, `MaterialTest.php` covers the generated `Material`.
- The file name never describes a bug or a feature. A "discount to zero" regression in `Op_upgGreen`
  goes in `Op_upgGreenTest.php`, not in `Op_upgGreenDiscountZeroTest.php`. The story belongs in the
  method name and the docblock, with the BGA bug number if there is one.
- Built on `GameUT` (in-memory tokens and machine): construct, call the method, assert. No driver.
- When a fix spans several classes, file it under the class that owns the fix.
- `GameTest.php` is large because `Game` is large. That is a `Game` problem, not a test problem - the
  answer is to move behaviour out of `Game` into a class of its own and take its tests with it, not to
  split the tests by topic.

### Integration tests - `phptests/Campaign/Campaign_<Scenario>Test.php`

- Extend `CampaignBase`, which drives the real game through `GameDriver`: real op machine, real state
  classes, real notifications, only the token and machine stores in memory.
- Written as a scenario - `setupGame()`, then `respond()` / `skip()` the way a player would, asserting
  op types and valid targets along the way.
- Named after the scenario, not after a class: `Campaign_CardInteractCannotPaySoftlockTest`.
- Reach for one when the bug only exists in the sequence (turn order, queued ops, undo, AI interleave),
  i.e. when no single class can be blamed.

### Client tests - `src/tests/<ClassUnderTest>.spec.ts`

- Run by `npm run jstests` (mocha + chai + sinon + jsdom). Same naming rule as the PHP unit tests: one
  spec per class under test, named after it, never after a bug or a feature.
- Every spec starts with `import "./setup"`, before anything else. `setup.ts` builds the jsdom document
  and stubs the BGA globals (`gameui`, `ebg`, `$`, `_`, `define`) that the source files touch at import
  time, and redirects `require("./libs")` to `libs.stub.ts` because `libs.ts` uses top level await,
  which CommonJS cannot load.
- Support files are deliberately not named `*.spec.ts` so mocha's glob ignores them.
- A spec constructs the real `Game` with a hand-written `bga` facade of stubs and a minimal `gamedatas`,
  then calls the method. Anything needing a real browser - hover, clicks, layout, actual BGA page chrome -
  is out of reach here.

### Ad hoc checks - the visual harness

`npm run play` is the third leg, and it is not a test suite: it asserts nothing. It builds a game state in
PHP, renders it with the real client code in jsdom, and writes `staging/snapshot.html` plus a headless
Chrome screenshot `staging/snapshot.png`. It answers "what does this actually look like", which no spec
can. Full documentation in [HARNESS.md](HARNESS.md).

- Use it for anything visual - alignment, sizing, colours, whether a button or tooltip is even there.
  A client-side change is not verified until the PNG has been looked at.
- Drive it either with a scenario (`phptests/Harness/plays/*.json`, a script of the same actions the real
  client sends) or with a debug function (one PHP function that builds the state in one shot). The debug
  function is the fast path for "show me the UI of this one operation"; the scenario is for states you can
  only reach by playing into them.
- `staging/db.json` plus `--db` resumes the last state, so you are not re-shuffling on every run.
- It runs as part of `npm run predeploy`, so it has to not crash - but a green harness run proves nothing
  beyond that.
- When a harness look turns up a real bug, the regression guard is still a unit or campaign test. The
  harness finds it; the suite is what keeps it fixed.

## Live scoring

Show victory points on the player panels during play instead of only at the end.
Applies to any game whose final scoring is a pure function of board state, which is what makes it safe to
run at any moment.

### Idea

One scoring function, two modes. It either commits (writes the score, the stats and the log) or fills a
table and writes nothing. Everything else reuses what the game already has: the same end-of-game
notification and the same score sheet widget.

### What it touches

`modules/php/States/EndScore.php`

- `finalScoring(?array &$table = null)`, with `$commit = $table === null`.
- All VP go through `scoreVp()` and all stat writes through `scoreStat()`; those two helpers are the only
  places that branch on the mode, so the scoring rules themselves are written once.
- `notifyMessage` calls are wrapped in `if ($commit)` so a preview never logs.
- Preview returns before the commit-only tail (tiebreaker, total stat, notification, GameResult).
- Commit resets each player's score to 0 first, so awarded VP cannot stack on what was displayed before.

`modules/php/Game.php`

- `scoreAllTable()` runs `finalScoring` in preview mode.
- `getScoresUpdate(bool $final)` is the single producer of the `endScores` payload: it splits the automa
  entry into `aiEndScores` and zero-fills the stat keys, so a live update and the final one are
  shape-identical. `getAllDatas()` is then one `array_merge`.
- `isLiveScoringEnabled()` gates it. When the option is off nothing is computed and nothing is sent - the
  visibility decision has to be server-side, since anything sent is readable from the browser.

`modules/php/States/PlayerTurn.php`

- `onEnteringState()` calls `notifyScoringUpdate()`, i.e. every time the machine asks a human for input.
  Deliberately not a list of per-action triggers: in this game a tucked inspiration card can score off a
  single coin, so an enumerated list ends up covering every effect in the game. Undo needs no handling
  because undo reloads the UI, which re-reads `getAllDatas`.

`gameoptions.json`

- Option 102 `variant_live_scoring`, default off, label registered in `Game::initGameStateLabels`. Tables
  already in progress have no stored value and read as off.

`src/Game.ts`

- `updateLiveScores()` writes the panel counters, `notif_endScores` routes `final === false` to it.
- Click on `player_score_<id>` opens a breakdown dialog built from the last payload. It creates its own
  throwaway `ScoreSheet` instances, so the permanent ones in `#score-area` are left alone.

### Client gotchas

- Panel counters: use `Counter.setValue` during setup and `toValue` after. An animated `toValue` does not
  stick while the panels are still being built. Also write the value into `gamedatas.players[id].score`
  (and the bots equivalent), because the framework re-reads it and would restore the stored 0.
- Score sheet: passing `scores` in the `ScoreSheet` constructor renders them instantly, which is what a
  page reload wants; call `setScores()` instead to animate, which is what the end-of-game notification
  wants. Passing live scores at construction would also pop the sheet open mid-game, so gate that on
  `gameEnded`.

### Porting checklist

1. Give final scoring an optional table parameter and route every write through two helpers.
2. Add the game option and gate both the computation and the payload on it.
3. Reuse the existing end-of-game notification with a `final` flag instead of inventing a new one.
4. Send it from wherever the machine hands control back to a human.
5. Client side: panel counters first, breakdown dialog second.
