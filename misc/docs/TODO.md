## Server

[x] Interraction for inf placed by player, player may refuse interaction (and pay)

## Client

- Board action tooltips (also dup hot slots?)
- Visual UI for insp under home space
- [ ]Could the Home space card be highlighted here so you could click on it?

## Bugs

      https://studio.boardgamearena.com/gamereview?table=851087 Game log in text format stored in /tmp/game.log.html

- [x] I should not be getting the Vista's benefit when tucking a townsfolk under a Harbour Table #851087 Move #52
- [x] bug:AI placing 2nd worker on card
- [ ] When Journalling, if you gain a Black Influence at any point on that turn you can then spend a Black Influence to Journal an additional time (you don't need to have the Black Influence at the start of the action) Table #851087 Move #63 — waiting on designer input: should rest abilities be player-ordered, or journal resolved last?
- [x] If I have a Pigeon I shouldn't have to pay the provision to draw 3 Table #851087 Move #136 — Op_seq was not propagating parent data to children
- [x] Table #851087 Move #140 There were two prompts Select a card to keep (of the 3) Then after that there was the 1 card left but another prompt: Select a card to buy That last part is unncessary no?
- [x] I should have 5VP from the home base card + 5VP more from the other space card. This is showing that I only have 6 from Space Cards Not sure which ones it is counting, but it does get the Inspiration card right — evaluateTerm used wrong guild location
- [x] AI/Bot score is not showing — scoresheet not created during live play, only on replay

## SOLO AI

- [x] AI upgrade tile acquisition (Op_ai_upgAny.php):
  - [ ] Rotate rectangular tiles to fit winding path

- [ ] UI support for AI upgrade tiles placed alongside board (state 0, no caravan position)
- [ ] Can we please have the AIs cards they collect be in a facedown pile, with the number of cards in the stack on them?
