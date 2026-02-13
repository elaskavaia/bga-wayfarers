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

use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\OpCommon\Operation;

use function Bga\Games\wayfarers\getPart;

/**
 * Main turn operation - player selects a die, worker, or rest action
 */
class Op_turn extends Operation {
    public function auto(): bool {
        $this->game->switchActivePlayer($this->getPlayerId(), true);
        $this->game->customUndoSavepoint($this->getPlayerId(), 1);

        // Reset the influence usage flags for this player's turn
        $owner = $this->getOwner();
        $this->game->tokens->db->setTokenState("used_inf_blue_$owner", 0);
        $this->game->tokens->db->setTokenState("used_inf_yellow_$owner", 0);
        $this->game->tokens->db->setTokenState("used_inf_black_$owner", 0);

        return parent::auto();
    }

    /**
     * Queue the next turn, or end the game if this was the final turn
     * When end game is triggered, game_stage holds the player number (1-4) who triggered it.
     * After that player completes their turn (everyone got their final turn), set game_stage to 5.
     */
    function queueNextTurnOrEnd(): void {
        $gameStage = $this->game->tokens->db->getTokenState(Game::GAME_STAGE);

        // If end game was triggered (game_stage = 1-4)
        if ($gameStage >= 1 && $gameStage <= 4) {
            $triggeringPlayerNo = $gameStage;
            $currentPlayerNo = $this->game->getPlayerNoById($this->getPlayerId());

            // If the current player is the one who triggered end game,
            // that means everyone has had their final turn - end the game
            if ($currentPlayerNo == $triggeringPlayerNo) {
                $this->queue("finalScoring");
                // Don't queue another turn - game will end
                return;
            }
        }

        // Continue with the next turn
        $nextPlayerId = $this->game->getPlayerAfter($this->getPlayerId());
        $this->queue("turn", $this->game->game_getPlayerColorById($nextPlayerId));
    }

    /**
     * Get dice in player's supply (tableau)
     */
    function getDiceInPlayerSupply(): array {
        $owner = $this->getOwner();
        return $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$owner");
    }

    /**
     * Get workers in player's supply (on their tableau)
     */
    function getWorkersInSupply(): array {
        $owner = $this->getOwner();
        return $this->game->tokens->getTokensOfTypeInLocation("worker", "tableau_$owner");
    }

    /**
     * Check if blue influence has already been spent this turn
     */
    function isBlueInfluenceSpentThisTurn(): bool {
        $owner = $this->getOwner();
        $flagToken = "used_inf_blue_$owner";
        return $this->game->tokens->db->getTokenState($flagToken) > 0;
    }

    public function getPossibleMoves() {
        $res = [];

        // Select a die, worker, or rest

        // Add dice options - group by die value (1-6)
        $player_dice = $this->getDiceInPlayerSupply();
        $diceByValue = [];
        foreach ($player_dice as $dieKey => $dieInfo) {
            $dieValue = (int) $dieInfo["state"];
            if (array_key_exists($dieValue, $diceByValue)) {
                $res[$dieKey] = [
                    "q" => Material::ERR_NOT_APPLICABLE,
                    "err" => clienttranslate("Duplicate die value"),
                ];
            } else {
                $res[$dieKey] = ["q" => Material::RET_OK, "color" => "primary"];
            }
            $diceByValue[$dieValue] = $dieKey;
        }

        // Add worker options - group by color (blue, yellow, green)
        $workers = $this->getWorkersInSupply();
        $workersByColor = [];
        foreach ($workers as $workerKey => $workerInfo) {
            // Worker keys are like worker_blue_1, worker_yellow_2, etc.
            $workersByColor[getPart($workerKey, 1)] = $workerKey;
        }

        // Add one option per unique worker color
        foreach ($workersByColor as $workerKey) {
            $res[$workerKey] = ["q" => Material::RET_OK, "color" => "secondary"];
        }

        /** @var Op_rest */
        $oprest = $this->instanciateOperation("rest");
        if ($oprest->isGoodRest()) {
            $res["rest"] = ["q" => 0, "name" => clienttranslate("Rest") . " [wicon_rest1]", "color" => "secondary"];
        } else {
            $res["rest"] = ["q" => 0, "name" => clienttranslate("Rest"), "color" => "alert"];
        }

        return $res;
    }

    public function getUiArgs() {
        return ["imagebuttons" => true];
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $selected = $this->getCheckedArg();

        if ($selected === "rest") {
            $this->queue("rest");
        } elseif (str_starts_with($selected, "worker_")) {
            $this->queue("placeWorker", $owner, ["worker" => $selected]);
        } elseif (str_starts_with($selected, "dice_")) {
            $this->queue("placeDie", $owner, ["die" => $selected]);
        }
        $this->queue("turnconf");
        $this->queueNextTurnOrEnd();
    }

    public function getPrompt() {
        return clienttranslate("Select a die, worker or rest");
    }
}
