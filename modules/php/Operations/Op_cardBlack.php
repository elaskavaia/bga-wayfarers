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

class Op_cardBlack extends Op_gainCard {
    function getPossibleMoves() {
        $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_space", "mainarea");
        $res = [];
        foreach ($tokens as $card => $info) {
            $cost = $this->getCost((int) $info["state"]);
            $res[$card] = ["q" => 0, "cost" => $cost];
        }
        return $res;
    }

    function getCost($space): int {
        return match ($space) {
            0 => 5,
            1 => 3,
            2 => 4,
            3 => 4,
            4 => 5,
        };
    }

    /** User does the action */
    function resolve(): void {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();
        $cost = $this->getCost($card);
        $this->game->effect_incCount($owner, "coin", -$cost, $this->getOpId());
        $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_space", "tableau_$owner");
        $this->game->tokens->dbSetTokenLocation($card, "tableau_$owner", count($tokens)); //XXX pick location
        return;
    }
}
