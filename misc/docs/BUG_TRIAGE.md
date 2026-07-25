# Bug Triage - project config and state

Game-specific configuration and running state for the `game-bug-triage` skill.
The skill itself is game-independent; everything project-specific lives here.
Read this file at the start of every triage run.

## Game reference

- **BGA game id:** 2662 (Wayfarers of the South Tigris)
- **Bug list:** https://en.boardgamearena.com/bugs?game=2662
- **Single report:** https://en.boardgamearena.com/bug?id=<REPORT_ID>

## Rules sources (for `rules`-type reports)

Check a reporter's claim against these before assuming the code is wrong:

1. [RULES.md](RULES.md) - the rulebook.
2. [CHECKLIST.md](CHECKLIST.md) - implementation checklist / clarifications.

(No FORUM.md in this project yet; add one here if designer rulings get saved.)

## Deploy model

Process: **creating a git tag = deployed.** To decide if a fix is live:

- Find the fix commit: `git log --oneline -i --grep='#<reportid>'` (commit messages reference `BGA #<id>`).
- Is it in the latest tag: `git merge-base --is-ancestor <commit> <latest-tag> && echo LIVE`.
- List tags containing it: `git tag --contains <commit> --sort=creatordate | head`.

In the latest deployed tag -> **Fixed**. Committed/tagged but that tag not deployed -> **Waiting for deploy**.

To confirm what is actually live (and avoid guessing whether a tag is deployed), open the studio manage-game page: https://studio.boardgamearena.com/studiogame?game=wayfarers - the "Online version" field shows the current production version string, and the "Versions available for production" list marks one entry "(Current PRODUCTION version)" with its release-note summary. Match a report's fix to that release note to confirm it shipped. (This is the authoritative deploy check; the git-tag steps above are the local proxy.)

## Investigation and tests

- Tests live under `phptests/` (flat, mirroring op/source names, e.g. `Op_rerollTest.php`).
- A reproducing test must be **green**, asserting the current buggy behavior, with a comment pinning it for `BGA #<id>` (flip the assertion when the fix lands) - see the skill's CONFIRMED note.

## Bug triage last checked

_(none yet - no full sweep has run. 2026-07-23 was a single-report skill test, not a sweep; do not treat it as a marker.)_

## Tracked bugs

Internal record of triaged reports (root cause, fix, test) - never put this detail in a public bug comment.

- [x] BGA #233581 (rules, solo mode) - FIXED, covered by
      `phptests/Campaign/Campaign_AiInterleaveOnInfluencedWorkerTest.php::testAutomaDefersResourceTrackBonusUntilItsOwnTurn`.
      Op_ai_res now checks for a pending `turn` op owned by the automa: if one exists we are inside a human
      action, so the 4.5 bonus is inserted at that op's rank (resolves when the automa activates) instead of
      being queued into the human's cascade. Verified against the actual reported dump
      (studio table 928069 / move 115): the human is correctly offered his own folk pick - the real defect
      is that the AUTOMA acted in the MIDDLE of the human's still-unfinished worker action.
      Mechanism (traced through the op-machine):
  - Op_placeWorker.php:112 queues `cardInteract` for the placed-on card AHEAD of the worker action's own `cardFolk`.
  - Op_cardInteract.php:106-107 - when the human pays to interact with an AUTOMA-influenced card, it queues `ai_res` (automa receives the payment as resource-marker movement). `ai_res` runs next, before the human's `cardFolk`.
  - Op_ai_res.php:35-40 - crossing the 4.5 mark queues the aiboard `r2` bonus. On board 3 (Aida the Mayor) r2 = `ai_cardFolk`, so the automa ACQUIRES a Townsfolk right there, mid-human-action (and can grab the worker on the card). Clarification from game design, the action from ai_res should be postponed until AI turn
- [ ] UX (surfaced by #233367, general) - the common OR operation (Op_or, see the isVoid() filtering around Op_or.php:119-125) silently auto-removes a branch when it is void, so the player just sees fewer options with no explanation (e.g. the "buy Space Card OR Space Upgrade" choice collapsing to only the upgrade when no Space-Card slot is available). Consider logging when a branch is auto-pruned as void, so the player understands why an expected option is missing. General fix in Op_or, not specific to cardSpace. Same flavor as the missing-reason item below.
- [ ] BGA #233129 (action/display, mobile) - the zoom controls (.board_layout_controls, Boards.scss:538) are absolutely positioned at the tableau's left edge (left: 8px, z-index 900) and overlap the far-left upgrade tile / far-left card, worst on narrow mobile. Kept OPEN, nothing posted. There is NO way to hide them today (only gamepref #101 confirm-turn; localStorage stores zoom mode/scale/journal-collapse only). FIX: add a local setting to hide the zoom controls, copying it from the fate game - port fate/src/LaZoom.ts (reusable zoom module; its "Scale to fit" mode hides board_layout_controls via the controls_visible toggle, LaZoom.ts:96-101) + fate/src/LocalSettings.ts, wiring the choice as in fate/src/Game.ts:262 ("Scale to fit" vs "Show zoom controls"). Wayfarers currently has zoom code inline in Game.ts; porting LaZoom gives the hide-controls setting.
- [x] BGA #233581 (related, secondary) - missing "reason" in the log for why the automa acted, a big part of why the reporter was confused ("not sure how this happened"). FIXED: `Operation::notifyMessage` now always supplies a `reason` arg, `restracker_bonus` has a display name in token_material.csv, and the automa action templates render `${reason}` - resource marker, card acquisition, comet, worker place/pick, influence, journal, upgrade tile (the tile path needed an args passthrough in `Game::effect_gainTile`). The log now reads e.g. "Aida acquires card at position 1 based on silver values (Resource Tracker Bonus)". Only the bonus path is asserted by a test. NOT covered: the "Aida cannot do X" messages and the phase announcements (rests / shuffles / plays Scheme card), where the reason would just repeat the phase.

## Triage run log

Short log of each run: date, reports touched, outcome.

- 2026-07-23 - First run (skill test, single report). #233581: not already-fixed (no fix commit; report postdates latest tag v260716-1846). Two agent hypotheses (Op_turn marker reset; cardFolk-not-offered) were both DISPROVEN against the actual dump (studio table 928069). Real cause found by tracing the op-machine and confirmed by a Campaign integration test: automa acts mid-human-action via cardInteract -> ai_res -> 4.5 r2 = ai_cardFolk acquisition. Report still OPEN, no public comment posted. Single report only - NOT a full sweep, marker intentionally left unset.
- 2026-07-24 - #231682 (guild influence click) looked at, left OPEN unchanged (likely influence-token intercepting the tap; skipped per maintainer). #233367 (purchase space card) triaged -> NOT A BUG, player-facing rule comment posted, status flipped; secondary UX note tracked (silent void option). #233129 (zoom controls overlap) triaged -> real client-side UI overlap, kept OPEN, tracked with a note to port fate's LaZoom + LocalSettings for a hide-zoom-controls setting. Still not a full sweep, marker still unset.
- 2026-07-24 - Waiting-for-deploy sweep. Confirmed production version 260716-1846 is live via the studio manage-game page (release notes: caravan-full upgrade fix, solo scheme-card tooltips, translatable strings). All 4 waiting reports were marked "fixed on my side, next release" on 2026-07-16 (18:22-18:42), just before the 18:42 tag, and only non-fix commits sit after the tag -> all shipped. Flipped to FIXED (template comment posted, verified): #227567 (pink tile / caravan full), #230012 (missing translation), #230002 (solo scheme-card action description), #229078 (townfolk popup / playerboard). Waiting-for-deploy queue now empty.
