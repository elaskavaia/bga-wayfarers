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
        $reentry = $this->getDataField("reentry", false);
        if ($reentry) {
            return parent::auto();
        }
        // Clear the "placed-this-turn" marker (state=1) on workers left on cards from the
        // previous turn so they become retrievable again.
        $placedWorkers = $this->game->tokens->getTokensOfTypeInLocation("worker", "card_%", 1);
        foreach ($placedWorkers as $key => $info) {
            $this->game->tokens->db->setTokenState($key, 0);
        }

        if ($this->getPlayerId() == Game::PLAYER_AUTOMA) {
            $this->queue("ai_turn", $this->game->getAutomaColor());
            return true;
        }
        $this->game->refillMainArea();
        $this->game->switchActivePlayer($this->getPlayerId(), true);
        $this->game->customUndoSavepoint($this->getPlayerId(), 1);

        // Reset the influence usage flags for this player's turn
        $owner = $this->getOwner();
        $this->game->tokens->db->setTokenState("used_inf_blue_$owner", 0);
        $this->game->tokens->db->setTokenState("used_inf_yellow_$owner", 0);
        $this->game->tokens->db->setTokenState("used_inf_black_$owner", 0);
        $this->notifyMessage(clienttranslate('${player_name} starts the turn'));

        if ($this->game->isSolo()) {
            $this->game->notify->all("journalTagCounts", "", [
                "player_id" => Game::PLAYER_AUTOMA,
                "color" => $this->game->getAutomaColor(),
                "counts" => $this->game->getJournalTagCounts($this->game->getAutomaColor()),
            ]);
        }

        return parent::auto();
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
                    "q" => Material::RET_OK,
                    "buttons" => false,
                    "sec" => true,
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

        // Add influence spending options
        $ops = ["spendInfYellow"];
        $owner = $this->getOwner();
        foreach ($ops as $opId) {
            $op = $this->game->machine->instanciateOperation($opId, $owner);
            $key = $op->getOpId();
            $void = $op->noValidTargets();
            if (!$void) {
                $res[$key] = ["q" => Material::RET_OK, "name" => $op->getIconicName(), "color" => "secondary"];
            }
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

        $playerId = $this->getPlayerId();
        $reentry = $this->getDataField("reentry", false);
        if (!$reentry) {
            $this->game->playerStats->inc("game_turns", 1, $playerId);
        }

        if ($selected === "rest") {
            $this->game->playerStats->inc("game_rest_actions", 1, $playerId);
            $this->queue("rest", null, ["reason" => null]);
        } elseif (str_starts_with($selected, "worker_")) {
            $this->game->playerStats->inc("game_worker_actions", 1, $playerId);
            $this->queue("placeWorker", $owner, ["worker" => $selected, "reason" => null]);
        } elseif (str_starts_with($selected, "dice_")) {
            $this->game->playerStats->inc("game_dice_actions", 1, $playerId);
            $this->queue("placeDie", $owner, ["die" => $selected, "reason" => null]);
        } elseif (str_starts_with($selected, "Op_")) {
            $op = str_replace("Op_", "", $selected);
            $this->queue($op, $owner, ["reason" => null]);
            // re-queue itself so player still gets their main action after the free spend
            $this->queue($this->getType(), $owner, $this->getData() + ["reentry" => true]);
            return;
        } else {
            $this->game->systemAssert("Invalid choice $selected");
        }
        $this->queue("turnconf", null, ["reason" => null]);
        $this->game->queueNextTurnOrEnd($this->getPlayerId());
    }

    public function getPrompt() {
        return clienttranslate("Select a die, worker or rest");
    }
}
