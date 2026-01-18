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
 * wayfarers.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\OpCommon\Operation;

class Op_turn extends Operation {
    public function auto(): bool {
        $this->game->switchActivePlayer($this->getPlayerId(), true);
        if ($this->getLocation() === null) {
            $this->game->customUndoSavepoint($this->getPlayerId(), 1);
        }

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
        $this->queue("turn", $this->game->getPlayerColorById($nextPlayerId));
    }

    function getDiceSlots() {
        $owner = $this->getOwner();

        $cards = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card", "tableau_$owner");
        $slots = [];
        foreach ($cards as $card => $info) {
            if ($this->game->getRulesFor($card, "d")) {
                $slots[$card] = [
                    "key" => $card,
                    "location" => $card,
                    "state" => count($info["children"] ?? []),
                ];
            }
        }
        return $slots;
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
     * Check if player can place a worker (has workers in supply)
     */
    function canPlaceWorker(): bool {
        return count($this->getWorkersInSupply()) > 0;
    }

    /**
     * Check if player has blue influence available to spend for a ship
     */
    function hasBlueInfluenceForShip(): bool {
        $owner = $this->getOwner();
        return $this->game->countGuildInfluence("guild_blue", $owner) > 0;
    }

    /**
     * Check if a die can be placed on a card slot (considering asset requirements)
     * @param int $dieValue - the die value (1-6)
     * @param string $cardKey - the card to place the die on
     * @return array - ["can" => bool, "needsBlueInfluence" => bool]
     */
    function canPlaceDieOnCard(int $dieValue, string $cardKey): array {
        $owner = $this->getOwner();
        $requirements = $this->game->getRulesFor($cardKey, "d", "");

        if (empty($requirements)) {
            return ["can" => true, "needsBlueInfluence" => false];
        }

        $assets = $this->game->getCaravanAssetsForDie($dieValue, $owner);
        $hasBlueInfluence = $this->hasBlueInfluenceForShip();

        $check = $this->game->checkAssetRequirements($requirements, $assets, $hasBlueInfluence);

        return [
            "can" => $check["met"],
            "needsBlueInfluence" => $check["needsBlueInfluence"],
        ];
    }

    /**
     * Check if ANY die in player's supply can be placed on a card slot
     */
    function canAnyDieBePlacedOnCard(string $cardKey): array {
        $player_dice = $this->getDiceInPlayerSupply();
        $canPlace = false;
        $needsBlueInfluence = false;

        foreach ($player_dice as $dieKey => $dieInfo) {
            $dieValue = (int) $dieInfo["state"];
            $check = $this->canPlaceDieOnCard($dieValue, $cardKey);
            if ($check["can"]) {
                $canPlace = true;
                if (!$check["needsBlueInfluence"]) {
                    // Found a die that can place without blue influence
                    return ["can" => true, "needsBlueInfluence" => false];
                }
                $needsBlueInfluence = true;
            }
        }

        return ["can" => $canPlace, "needsBlueInfluence" => $needsBlueInfluence];
    }

    public function getPossibleMoves() {
        $loc = $this->getLocation();
        $res = [];
        $owner = $this->getOwner();
        $player_dice = $this->getDiceInPlayerSupply();

        if ($loc) {
            // Step 2: Select which die to place on the chosen card
            $requirements = $this->game->getRulesFor($loc, "d", "");

            foreach ($player_dice as $key => $dieInfo) {
                $dieValue = (int) $dieInfo["state"];
                $check = $this->canPlaceDieOnCard($dieValue, $loc);

                if ($check["can"]) {
                    $res[$key] = [
                        "q" => Material::RET_OK,
                        "needsBlueInfluence" => $check["needsBlueInfluence"],
                    ];
                } else {
                    $res[$key] = ["q" => Material::ERR_COST];
                }
            }
            return $res;
        }

        // Step 1: Select action (card slot, rest, or worker)
        $res["rest"] = ["q" => 0, "name" => clienttranslate("Rest")];

        if (count($player_dice) > 0) {
            $slots = $this->getDiceSlots();
            foreach ($slots as $key => $slot) {
                $state = $slot["state"];
                if ($state > 0) {
                    // Slot already occupied
                    $res[$key] = ["q" => Material::ERR_OCCUPIED];
                } else {
                    // Check if any die can be placed here (considering assets)
                    $check = $this->canAnyDieBePlacedOnCard($key);
                    if ($check["can"]) {
                        $res[$key] = [
                            "q" => Material::RET_OK,
                            "needsBlueInfluence" => $check["needsBlueInfluence"],
                        ];
                    } else {
                        $res[$key] = ["q" => Material::ERR_COST];
                    }
                }
            }
        }

        // Worker placement option (only if player has workers in supply)
        if ($this->canPlaceWorker()) {
            $res["worker"] = ["q" => 0, "name" => clienttranslate("Place Worker")];
        }
        return $res;
    }
    public function getUiArgs() {
        return ["buttons" => false];
    }

    public function getLocation() {
        return $this->getDataField("loc", null);
    }
    function resolve(): void {
        $loc = $this->getLocation();
        $owner = $this->getOwner();
        if ($loc == null) {
            $selected = $this->getCheckedArg();
            if ($selected === "rest") {
                $this->queue("rest");
                $this->queueNextTurnOrEnd();
                return;
            }
            if ($selected === "worker") {
                $this->queue("placeWorker");
                $this->queueNextTurnOrEnd();
                return;
            }
            $this->queue($this->getType(), $owner, ["loc" => $selected]);
            return;
        }
        $die = $this->getCheckedArg();
        $dieValue = (int) $this->game->tokens->db->getTokenState($die);

        // Check if this placement requires spending blue influence for a ship
        $check = $this->canPlaceDieOnCard($dieValue, $loc);
        if ($check["needsBlueInfluence"]) {
            // Spend 1 blue influence
            $this->queue("n_infBlue", $owner, [], "ship");
        }

        $this->game->tokens->dbSetTokenLocation($die, $loc, 0, clienttranslate('${player_name} places die ${num} onto ${token_name}'), [
            "num" => $dieValue,
        ]);
        $r = $this->game->getRulesFor($loc, "dr");

        $this->game->systemAssert("parent rule empty '$loc'", $r);
        $this->queue($r, $owner, [], $loc);

        // Check for folk card tucked under this card (same state) and activate its ability
        $cardState = (int) $this->game->tokens->db->getTokenState($loc);
        $folkCards = $this->game->tokens->getTokensOfTypeInLocation("card_folk", "tableau_$owner", $cardState);
        $folkCard = array_key_first($folkCards);
        if ($folkCard !== null) {
            $folkRule = $this->game->getRulesFor($folkCard, "dr", "");
            if ($folkRule !== "") {
                $this->queue($folkRule, $owner, [], $folkCard);
            }
        }

        $this->queueNextTurnOrEnd();
    }

    public function getPrompt() {
        $loc = $this->getLocation();
        $owner = $this->getOwner();
        if ($loc == null) {
            return clienttranslate("Select an action");
        }
        return clienttranslate("Select a die");
    }
}
