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

use function Bga\Games\wayfarers\getPart;

/**
 * Place a Worker on a card on the Main Board
 * Workers allow interaction with cards and become public resources once placed
 */
class Op_placeWorker extends Operation {
    /**
     * Get workers in player's supply (on their tableau)
     */
    function getWorkersInSupply(): array {
        $owner = $this->getOwner();
        return $this->game->tokens->getTokensOfTypeInLocation("worker", "tableau_$owner");
    }

    /**
     * Get valid card targets on the main board where workers can be placed
     * Workers can be placed on faceup cards in the mainarea
     */
    function getWorkerSlots(): array {
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card", "mainarea");
        $slots = [];
        foreach ($cards as $cardKey => $cardInfo) {
            // Check if card accepts workers (has worker slot)
            // For now, all mainarea cards can accept workers
            $slots[$cardKey] = [
                "key" => $cardKey,
                "location" => $cardKey,
            ];
        }
        return $slots;
    }

    /**
     * Get the selected worker from the first step
     */
    function getSelectedWorker(): ?string {
        return $this->getDataField("worker", null);
    }

    public function getPossibleMoves() {
        $selectedWorker = $this->getSelectedWorker();

        if ($selectedWorker === null) {
            // Step 1: Select a worker from supply
            $workers = $this->getWorkersInSupply();
            $res = [];
            foreach ($workers as $key => $worker) {
                $res[$key] = ["q" => Material::RET_OK];
            }
            return $res;
        } else {
            // Step 2: Select a card to place the worker on
            $slots = $this->getWorkerSlots();
            $res = [];
            $wcolor = getPart($selectedWorker, 1);
            foreach ($slots as $key => $slot) {
                if (str_starts_with($key, "card_insp")) {
                    $res[$key] = ["q" => Material::RET_OK];
                } elseif ($wcolor == "yellow" && str_starts_with($key, "card_land")) {
                    $res[$key] = ["q" => Material::RET_OK];
                } elseif ($wcolor == "blue" && str_starts_with($key, "card_water")) {
                    $res[$key] = ["q" => Material::RET_OK];
                } elseif ($wcolor == "green" && str_starts_with($key, "card_folk")) {
                    $res[$key] = ["q" => Material::RET_OK];
                }
            }
            return $res;
        }
    }

    public function getUiArgs() {
        return ["buttons" => false];
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $selectedWorker = $this->getSelectedWorker();

        if ($selectedWorker === null) {
            // Step 1: Worker selected, move to step 2
            $worker = $this->getCheckedArg();
            $this->queue($this->getType(), $owner, ["worker" => $worker]);
            return;
        }

        // Step 2: Place worker on selected card and trigger card effect
        $targetCard = $this->getCheckedArg();

        // Move worker to the card
        $this->game->tokens->dbSetTokenLocation(
            $selectedWorker,
            $targetCard,
            0,
            clienttranslate('${player_name} places ${token_name} on ${token_name2}'),
            ["token_name2" => $targetCard]
        );

        $state = $this->game->tokens->db->getTokenState($targetCard);

        // Get and queue the card's worker action (wr = worker rule)
        $ctype = getPart($targetCard, 1);
        $workerRule = $this->game->getRulesFor("action_{$ctype}_{$state}", "r", "");
        if ($workerRule) {
            $this->queue($workerRule);
        }
    }

    public function getPrompt() {
        $selectedWorker = $this->getSelectedWorker();
        if ($selectedWorker === null) {
            return clienttranslate("Select a worker to place");
        }
        return clienttranslate("Select a card to place the worker on");
    }
}
