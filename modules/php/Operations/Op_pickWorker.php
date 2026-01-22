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

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\OpCommon\Operation;

class Op_pickWorker extends Operation {
    /** Get available workers that can be picked */
    function getAvailableWorkers(): array {
        $owner = $this->getOwner();
        $workers = [];

        // // Get green workers from journal track
        // $greenWorkers = $this->game->tokens->getTokensOfTypeInLocation("worker_green", "jbonus_%");
        // foreach ($greenWorkers as $key => $worker) {
        //     $workers[$key] = ["q" => Material::RET_OK];
        // }

        // Get workers from cards on main board (public workers)
        $publicWorkers = $this->game->tokens->getTokensOfTypeInLocation("worker", "card_%");
        foreach ($publicWorkers as $key => $worker) {
            $workers[$key] = ["q" => Material::RET_OK];
        }

        return $workers;
    }

    function getPossibleMoves() {
        return $this->getAvailableWorkers();
    }
    public function getUiArgs() {
        return ["replicate" => true];
    }
    public function canSkip() {
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $workerKey = $this->getCheckedArg();

        // Get the card the worker is on
        $workerInfo = $this->game->tokens->db->getTokenInfo($workerKey);
        $card = $workerInfo["location"];

        // Handle influence interaction if there's influence on the card (influence stays on card)
        $this->queue("cardInteract", $owner, ["card" => $card, "buy" => false]);

        // Move worker to player's tableau
        $this->game->tokens->dbSetTokenLocation($workerKey, "tableau_$owner", 0, clienttranslate('${player_name} picks ${token_name}'));
    }

    function getPrompt() {
        return clienttranslate("Select a worker to pick");
    }
}
