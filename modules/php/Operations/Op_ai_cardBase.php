<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\OpCommon\AiOperation;

class Op_ai_cardBase extends AiOperation {
    public function getPossibleMoves() {
        $cardType = $this->getCardType();
        $tokens = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card_$cardType", "mainarea", null, "token_state");
        return array_keys($tokens);
    }

    function getCardType() {
        $t = $this->getType();
        $t = str_replace("ai_card", "", $t);
        return lcfirst($t);
    }

    // Acquire Card (use sum value for position priority)
    public function auto(): bool {
        $owner = $this->getOwner();
        $moves = $this->getPossibleMoves();
        $type = $this->getCardType();
        if ($type == "insp") {
            // inspiration card position check resource marker
            $prio = $this->getResourceMarkerRules("c");
        } else {
            $prio = $this->getPositionPriority();
        }
        $card = $moves[$prio - 1] ?? "";
        $this->game->systemAssert("Missing card on main display $prio", $card);
        $vp = $this->getCardTypeVP($type);
        // arrange cards based on VP staring at -2 for townsfolk
        $this->game->tokens->dbSetTokenLocation($card, "tableau_$owner", -1 - $vp);
        return true;
    }
}
