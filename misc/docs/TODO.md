## Server

[x] Interraction for inf placed by player, player may refuse interaction (and pay)
[x] Stats: Number of turns
[x] Stats: Number of dice actions
[x] Stats: Number of rest actions
[x] Stats: Number of worker actions

## Client

- Board action tooltips (also dup hot slots?)
- [x] Visual UI for insp under home space
- [x]Could the Home space card be highlighted here so you could click on it?
- [x] When upgrading show the shadow shape of upgrade following mouse
- [ ] When upgrading show the proper shape aligned to the grid instead of following the mouse

## Bugs

- [x] When Journalling, if you gain a Black Influence at any point on that turn you can then spend a Black Influence to Journal an additional time (you don't need to have the Black Influence at the start of the action) Table #851087 Move #63
      — waiting on designer input: should rest abilities be player-ordered, or journal resolved last?
      [x] So we give them a choce of townfolk, but since gain coin or food does no affect anything we can auto-resolve them first and move on to choices that matter

- [x] When journaling is possible to gain black inf while resolving tile effect - need to shedule blackInf indepennt of current count
- [x] Can recruit book/obs folk without spot/tag for it (home_1 is already occupied by pre-print)
- [x] Insp card reverse action slots
- [x] Bug: cannot place and retrive working on the same turn
- [x] SOFTLOCK: an action was offered without checking it could be performed, so the operation it queued parked in PlayerTurn with no target and no skip - a dead end for a player, and "Cannot skip this action" in a loop for a zombie (the 16/08 tournament stack trace). Reachable on turn 1 via Explore. Fixed in Op_placeWorker, Op_placeDie, Op_upgBase and Op_turn, which now check what they are about to queue; Op_seq/Op_paygain stopped reporting void when only the head had nothing to offer. Guarded by Campaign_UnaffordableActionCostTest, Op_turnTest, Op_upgBaseTest and Campaign_ZombieFuzzTest (`npm run tests:fuzz`).

- [x] Operation::whatever (the zombie handler) threw when an operation has no target and cannot be skipped. Backstop added in PlayerTurn::zombie: the stuck operation is dropped with a player notification and the game advances; live players still exit via Undo. Guarded by Campaign_ZombieDropsStuckOperationTest; Campaign_ZombieFuzzTest detects the drop notification so blocked sites still surface as failures.

- [ ] Op_placeWorker slot veto misses the Influence fee: the veto checks the board action against current resources, but resolve() queues cardInteract (pay the influence owner 1 coin or 1 provision) before the action, so a fee paid in food can starve the action the veto approved. Repro: 0 coin + 3 food + opponent influence on the position-4 land card (Explore, 3n_food) - slot offered, fee eats 1 food, payment parks with no target. Live player exits via Undo, zombie via the drop backstop. Full fix needs either fee-reserved resources in the veto (over-blocks the coin fee path) or the affordability check inside cardInteract's payment choice.

- [ ] Minor: a card acquisition whose every target is blocked (e.g. all cards carry an unpayable Influence fee) is void, so a committed one strands the player on undo as the only exit. Only cardFolk is skippable on no-valid-targets; other card ops are not. Decide per bonus whether it is forced or an active choice; possibly express optionality in the op DSL instead (e.g. ?cardFolk(free) skippable externally). See Campaign_CardInteractCannotPaySoftlockTest.

## SOLO AI

- [x] AI upgrade tile acquisition (Op_ai_upgAny.php):
  - [x] Rotate rectangular tiles to fit winding path

- [ ] UI support for AI upgrade tiles placed alongside board (state 0, no caravan position)
- [x] Can we please have the AIs cards they collect be in a facedown pile, with the number of cards in the stack on them?
