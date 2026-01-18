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

class Op_dicePlus extends Operation {
    function getSelectedDie(): string {
        return $this->getDataField("die", "");
    }

    function resolve(): void {
        $dieKey = $this->getSelectedDie();

        $currentValue = (int) $this->game->tokens->db->getTokenState($dieKey);
        $newValue = $currentValue + 1;
        $this->game->tokens->dbSetTokenState(
            $dieKey,
            $newValue,
            clienttranslate('${player_name} uses caravan to change die to ${new_state}'),
            [],
            $this->getPlayerId()
        );
    }
}
