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

class Op_journal extends Operation {
    function getPossibleMoves() {
        return ["confirm"];
    }

    /** User does the action */
    function resolve(): void {
        $owner = $this->getOwner();
        $markerId = "marker_$owner";

        // Get current position on Journal Track
        $currentState = $this->game->tokens->db->getTokenState($markerId);

        // Increment position by 1
        $newState = $currentState + 1;

        // Update marker state
        $this->game->tokens->dbSetTokenState(
            $markerId,
            $newState,
            clienttranslate('${player_name} journals to position ${num}'),
            ["num" => $newState]
        );
    }
}
