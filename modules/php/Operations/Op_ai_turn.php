<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * wayfarers implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\OpCommon\AiOperation;

/**
 *  Au turn
 */
class Op_ai_turn extends AiOperation {
    public function auto(): bool {
        $this->game->customUndoSavepoint(0, 1);
        $owner = $this->getOwner();
        $this->game->systemAssert("mismatch action owner", $owner === "ffffff");

        // Determine if AI should Rest (3 faceup Red or 3 faceup Blue scheme cards) or reveal a new scheme card
        if ($this->countCards($owner, "red") >= 3 || $this->countCards($owner, "blue") >= 3) {
            // Rest
            $this->queue("ai_rest");
        } else {
            // Reveal
            $this->aiRevealScheme($owner);
        }

        // AI does not need confirmation of turn end
        $this->game->queueNextTurnOrEnd($this->getPlayerId());
        return true;
    }

    function aiRevealScheme(string $owner) {
        //         To reveal a Scheme Card, draw it from the top of the AI Scheme
        // Draw Pile and place it faceup to the right of their Draw Pile and any
        // previously revealed Scheme Cards. Then follow these steps:
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_scheme", "tableau_$owner");
        $drawn = $this->game->tokens->db->pickTokensForLocation(1, "deck_scheme", "tableau_$owner");
        $card = array_shift($drawn);
        $this->game->systemAssert("No more scheme cards to draw", $card);

        $cardKey = $card["key"];
        //   - [ ] On reveal: draw top scheme card, place faceup to right of draw pile
        // state starts with 2
        $this->dbSetTokenLocation($cardKey, "tableau_$owner", count($cards) + 2);
        //   - [ ] Move AI resource track marker clockwise by scheme card's silver value
        $silver = (int) $this->game->getRulesFor($cardKey, "c", 0);

        if ($silver > 0) {
            $this->queue("{$silver}ai_res");
        }

        //   - [ ] Resolve first action on scheme card; fallback to second action if first is impossible
        $action1 = $this->game->getRulesForAndAssert($cardKey, "r1");
        $action2 = $this->game->getRulesForAndAssert($cardKey, "r2");
        $this->notifyMessage("debug: ai actions   $action1   $action2");
        if ($this->instanciateOperation($action1)->isVoid()) {
            $this->queue($action2);
        } else {
            $this->queue($action1);
        }
    }
}
