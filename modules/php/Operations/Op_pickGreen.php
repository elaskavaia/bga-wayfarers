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

/**
 * Pick green worker if still there
 */
class Op_pickGreen extends Operation {
    function getPos() {
        $owner = $this->getOwner();
        $markerId = "marker_$owner";
        $currentState = (int) $this->game->tokens->db->getTokenState($markerId);
        return $currentState;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $pos = $this->getPos();
        $top = floor($pos / 10);
        $workers = $this->game->tokens->getTokensOfTypeInLocation("worker", "jpos_{$top}%");
        $worker = array_key_first($workers);

        if ($worker) {
            $this->game->tokens->dbSetTokenLocation(
                $worker,
                "tableau_$owner",
                0,
                clienttranslate('${player_name} picks ${token_name} from the board')
            );
        }
    }
}
