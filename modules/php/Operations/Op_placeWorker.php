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
        return $this->game->tokens->getTokensOfTypeInLocationWithChildren("card", "mainarea");
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
            $res = ["prompt" => clienttranslate("Select a worker to place")];
            foreach ($workers as $key => $worker) {
                $res[$key] = ["q" => Material::RET_OK];
            }
            return $res;
        }
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
            } elseif ($wcolor == "green" && !str_starts_with($key, "card_space")) {
                $res[$key] = ["q" => Material::RET_OK];
            }
            // You can't have more than 1 of the same colour worker on a card
            if (isset($res[$key]) && $res[$key]["q"] === Material::RET_OK) {
                $children = $slot["children"];
                foreach ($children as $child) {
                    if (str_starts_with($child["key"], "worker_$wcolor")) {
                        $res[$key] = [
                            "q" => Material::ERR_OCCUPIED,
                            "err" => clienttranslate("You cannot place more than 1 worker of the same color on a card"),
                        ];
                        break;
                    }
                }
            }
        }
        return $res;
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

        // Handle influence interaction if there's influence on the card (influence stays on card)
        $this->queue("cardInteract", $owner, ["card" => $targetCard, "buy" => false]);
        $state = $this->game->tokens->db->getTokenState($targetCard);
        $ctype = getPart($targetCard, 1);
        // Move worker to the card. State=1 marks it as placed-this-turn so it cannot be
        // retrieved or have its card acquired this turn (RULES.md line 252).
        // Op_turn::auto resets the state back to 0 at the start of the next turn.
        $this->dbSetTokenLocation(
            $selectedWorker,
            $targetCard,
            1,
            clienttranslate('${player_name} places ${token_name} on action ${card_type} position ${pos}'),
            ["pos" => $state, "card_type" => $this->game->getTokenName($ctype)]
        );

        $workerRule = $this->game->getRulesForAndAssert("action_{$ctype}_{$state}", "r", "");
        $this->queue($workerRule);
    }

    public function getPrompt() {
        return clienttranslate("Select where to place the worker to perform a board action");
    }
}
