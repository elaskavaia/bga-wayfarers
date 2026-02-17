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

class Op_jtile extends Operation {
    function getPos() {
        $owner = $this->getOwner();
        $markerId = "marker_$owner";
        $currentState = (int) $this->game->tokens->db->getTokenState($markerId);
        return $currentState;
    }

    function resolve(): void {
        // get corresponding bonus
        $owner = $this->getOwner();
        $pos = $this->getPos();
        $tile = $this->game->tokens->db->getTokensOfTypeInLocationSingleKey("jtile", "jpos_$pos");
        $this->game->systemAssert("tile is null at jpos_$pos", $tile);
        $r = $this->game->getRulesForAndAssert($tile, "r");
        $this->queue($r, $owner, [], $tile);
    }
}
