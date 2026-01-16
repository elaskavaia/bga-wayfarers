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

use Bga\Games\wayfarers\Game;
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

        $newState = $currentState + 3;

        // Update marker state
        $this->game->tokens->dbSetTokenState($markerId, $newState, clienttranslate('${player_name} journals to position ${num}'), [
            "num" => $newState,
        ]);

        // Check if end game is triggered (reaching position 30)
        if ($newState >= 30) {
            $this->triggerEndGame();
        }
    }

    /**
     * Trigger end of game condition
     * All players including the one who triggered get one more turn
     */
    function triggerEndGame(): void {
        $gameStage = $this->game->tokens->db->getTokenState(Game::GAME_STAGE);

        // Only trigger if not already triggered (game_stage < 1 means not triggered yet)
        if ($gameStage < 1) {
            // Store the triggering player's number (1-4) in game_stage
            // This marks who triggered it so we know when to end after they complete their final turn
            $playerId = $this->getPlayerId();
            $playerNo = $this->game->getPlayerNoById($playerId);

            $this->game->tokens->dbSetTokenState(
                Game::GAME_STAGE,
                $playerNo,
                clienttranslate('${player_name} triggers end of game! All players get one more turn.')
            );
        }
    }
}
