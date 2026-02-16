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
 * wayfarers.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\OpCommon;

use Bga\GameFramework\SystemException;

abstract class AiOperation extends CountableOperation {
    //   - [x] Implement scheme card sum value calculation: sum of 2 most recent faceup cards (or single card value)
    //   - [ ] Sum value (0-4) determines positional priority: 0-1 = center-most card/tile, higher = outward
    //   - [ ] If AI cannot interact with prioritized target, move to next possible, wrapping around
    public function getPositionPriority(): int {
        $owner = $this->getOwner();
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_scheme", "tableau_$owner", null, "token_state");
        $recentCards = array_slice($cards, -2);
        $sumValue = 0;
        foreach ($recentCards as $cardKey => $cardInfo) {
            $sumValue += (int) $this->game->getRulesFor($cardKey, "c", 0);
        }
        if ($sumValue == 0) {
            $sumValue = 1;
        }
        return $sumValue;
    }

    function aiGetBoardNumber(): int {
        $owner = $this->getOwner();
        return -(int) $this->game->tokens->db->getTokenState("pboard_$owner");
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

    public function getNextPositionPriorityDirection(int $prev): int {
        //1->2,2->3,3->2,4->3
        switch ($prev) {
            case 1:
                return 1;
            case 2:
                return 1;
            case 3:
                return -1;
            case 4:
                return -1;
        }
        throw new SystemException("Invalid position priority $prev");
    }

    function getRecentCard() {
        $owner = $this->getOwner();
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_scheme", "tableau_$owner", null, "token_state");
        return array_key_last($cards);
    }

    function getResourceMarkerPosition() {
        return $this->game->tokens->getTrackerValue($this->getOwner(), "res", 0);
    }

    static function getCardTypeVP($type) {
        //         The AI scores VP for the following: 1VP per acquired Townsfolk
        // Card; 2VP per acquired Water/Land Card; 3VP per acquired Space
        // Card;4VP per acquired Inspiration Card
        switch ($type) {
            case "insp":
                return 4;
            case "space":
                return 3;
            case "water":
                return 2;
            case "land":
                return 2;
            case "folk":
                return 1;
            default:
                return 0;
        }
    }

    function getResourceMarkerRules(string $field = "*") {
        $pos = $this->getResourceMarkerPosition();
        $ret = $this->game->getRulesFor("spot_res_$pos", $field, null);
        $this->game->systemAssert("Cannot find rules for spot_res_$pos", $ret);
        return $ret;
    }
}
