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

/**
 *  Au turn
 */
class Op_ai_turn extends Op_turn {
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

        $this->queueNextTurnOrEnd();
        return true;
    }

    function countCards(string $owner, string $color): int {
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_scheme", "tableau_$owner");
        $count = 0;
        foreach ($cards as $cardKey => $cardInfo) {
            if ($this->game->getRulesFor($cardKey, "t") === $color) {
                $count++;
            }
        }
        return $count;
    }

    function aiGetBoardNumber(): int {
        $owner = $this->getOwner();
        return -(int) $this->game->tokens->db->getTokenState("pboard_$owner");
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
        $boardNumber = $this->aiGetBoardNumber();
        if ($silver > 0) {
            [$trackerId, $currentPos] = $this->game->tokens->getTrackerIdAndValue($owner, "res");
            $newPos = ($currentPos + $silver) % 8;
            $this->dbSetTokenState($trackerId, $newPos, clienttranslate('${player_name} moves resource marker to ${pos}'), [
                "pos" => $newPos,
            ]);

            //   - [ ] Resolve resource track effects passed over (comet, guild influence, townsfolk card)
            // Check if we passed position 4.5 (between 4 and 5)
            // Silver is only 0, 1, or 2, so we can't wrap and pass 4.5 at the same time
            if ($currentPos <= 4 && $newPos >= 5) {
                // means we passed 4.5 where bonus lies
                $bonus = $this->game->getRulesFor("aiboard_$boardNumber", "r2");
                $this->game->systemAssert("Bonus missing for aiboard_$boardNumber", $bonus);

                //$this->queue($bonus, $owner, [], "restracker_bonus");
                $this->notifyMessage("ai bonus $bonus");
            }
        }

        //   - [ ] Resolve first action on scheme card; fallback to second action if first is impossible
        $action1 = $this->game->getRulesFor($cardKey, "r1");
        $action2 = $this->game->getRulesFor($cardKey, "r2");
        $this->game->systemAssert("r1 $cardKey", $action1);
        $this->game->systemAssert("r2 $cardKey", $action2);
        $this->notifyMessage("ai actions   $action1   $action2");
        // if ($this->instanciateOperation($action1)->isVoid()) {
        //     $this->queue($action2);
        // } else {
        //     $this->queue($action1);
        // }
    }
}
