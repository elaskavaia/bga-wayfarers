## Pre-Alpha BGA Checklist

Source: https://en.doc.boardgamearena.com/Pre-release_checklist

### License

[x] BGA has a license for the game

### Metadata and Graphics

[x] gameinfos.inc.php has correct, up-to-date info
[x] Box art: 3D game box, transparent background, correct publisher icon
[ ] Game Metadata Manager images added
[x] Remove unused images from img/
[x] Images compressed into sprite sheets
[x] Individual images under 4MB, total under 15MB
[ ] Freeware fonts only, with license docs

### Server Side

[X] When giving their turn to a player, you give them some extra time with the `giveExtraTime()` function
[x] `getGameProgression()` implemented
[x] `zombie()` turn implemented
[ ] Game statistics defined and coded
[x] Notification messages: helpful but not excessive
[x] Tiebreaking logic implemented
[x] No manual DB transaction management
[x] DB schema supports game completion

### Client Side

[x] ajaxcall/bgaPerformAction only on player actions, never programmatic

### User Interface

[ ] BGA UI design guidelines compliance
[x] Check all your English messages for proper use of punctuation, capitalization, usage of present tense in notification (not past) and gender neutrality. See Translations for English rules.
[x] Non-full-width elements centered
[x] Browser zoom doesn't blur/pixelate graphics
[x] Non-obvious elements have tooltips
[x] All strings translation-ready
[-] CSS class names use game-specific prefix
[-] Consider posting on developer forum for design feedback

### Special Testing

[ ] Minified JS/CSS tested via management page
[x] Spectator mode tested (public visible, private hidden)
[x] In-game replay from notification log works
[-] Complete game replay works start to end
[x] Works in Chrome and Firefox (Edge, Safari recommended)
[x] Mobile device / Chrome mobile mode tested
[ ] Real-time mode tested with giveExtraTime()
[ ] Waiting screen compatibility verified

### Cleanup

[x] Remove unnecessary console.log statements
[x] Remove debug logging from PHP
[x] Copyright headers in all source files
[x] Remove unnecessary files from main folder (move to misc/)
[x] Delete unused graphics from img/

### Static Analysis

[ ] Run "Check project" from control panel

### Move to Alpha

[ ] Correct formal game name for project
[ ] Build new release from "manage game" page
[ ] Verify build log
[ ] Click "Request ALPHA status"
