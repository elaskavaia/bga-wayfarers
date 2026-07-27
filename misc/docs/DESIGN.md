# Design notes

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
