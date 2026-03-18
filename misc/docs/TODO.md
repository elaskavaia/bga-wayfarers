## Server

[x] Interraction for inf placed by player, player may refuse interaction (and pay)

## Client

- Board action tooltips (also dup hot slots?)
- [x] Visual UI for insp under home space
- [x]Could the Home space card be highlighted here so you could click on it?
- [ ] When upgrading show the shadow shape of upgrade following mouse

## Bugs

- [ ] When Journalling, if you gain a Black Influence at any point on that turn you can then spend a Black Influence to Journal an additional time (you don't need to have the Black Influence at the start of the action) Table #851087 Move #63
      — waiting on designer input: should rest abilities be player-ordered, or journal resolved last?
      [ ] So we give them a choce of townfolk, but since gain coin or food does no affect anything we can auto-resolve them first and move on to choices that matter

- [ ] When journaling is possible to gain black inf while resolving tile effect - need to shedule blackInf indepennt of current count
- [ ] Can recruit book/obs folk without spot for it (home_1 is already occupied)

## SOLO AI

- [x] AI upgrade tile acquisition (Op_ai_upgAny.php):
  - [ ] Rotate rectangular tiles to fit winding path

- [ ] UI support for AI upgrade tiles placed alongside board (state 0, no caravan position)
- [ ] Can we please have the AIs cards they collect be in a facedown pile, with the number of cards in the stack on them?
