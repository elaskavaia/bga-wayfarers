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

use Bga\Games\wayfarers\OpCommon\Operation;

class Op_upgYellow extends Operation {
    function getPossibleMoves() {
        $tokens = $this->game->tokens->getTokensOfTypeInLocation("upg_yellow", "mainarea");
        $res = [];
        foreach ($tokens as $card => $info) {
            $cost = $this->getCost();
            $res[$card] = ["q" => 0, "cost" => $cost];
        }
        return $res;
    }

    function getCost(): int {
        return 3;
    }

    /** User does the action */
    function resolve(): void {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();
        $cost = $this->getCost();
        $this->game->effect_incCount($owner, "coin", -$cost, $this->getOpId());
        $this->game->tokens->dbSetTokenLocation($card, "tableau_$owner", 0); //XXX pick location
        return;
    }
}
