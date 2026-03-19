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

/**
 * Place a Die on a card in the player's tableau
 * Dice placement requires matching assets from the caravan
 */
class Op_placeDie extends Op_acquireBase {
    /**
     * Get available dice slots on player's tableau cards
     */
    function getDiceSlots(): array {
        $owner = $this->getOwner();

        $cards = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card", "tableau_$owner");
        $slots = [];
        foreach ($cards as $card => $info) {
            if ($this->game->getRulesFor($card, "d")) {
                $slots[$card] = [
                    "key" => $card,
                    "location" => $card,
                    "state" => count($info["children"]),
                ];
            }
        }
        return $slots;
    }

    /**
     * Check if blue influence has already been spent this turn
     */
    function isBlueInfluenceSpentThisTurn(): bool {
        $owner = $this->getOwner();
        $flagToken = "used_inf_blue_$owner";
        return $this->game->tokens->db->getTokenState($flagToken) > 0;
    }

    /**
     * Check if a die can be placed on a card slot (considering asset requirements)
     * @param string $cardId - the card to place the die on
     * @param array $caravanAssets - pre-computed caravan assets for the die value
     * @return array - list of missing assets (empty if can be placed)
     */
    function canPlaceDieOnCard(string $cardId, array $caravanAssets): array {
        $requirements = $this->game->getRulesFor($cardId, "d", "");

        // Copy assets so we don't modify the original
        $assets = $caravanAssets;

        // Tucked folk cards may have additional assets
        $folkCard = $this->getTuckedFolk($cardId);
        if ($folkCard !== null) {
            $folkRule = $this->game->getRulesFor($folkCard, "da", "");
            $this->game->updateMatchingAssetsFromRule($folkRule, $assets);
        }

        return $this->game->getMissingAssetRequirements($requirements, $assets);
    }

    public function getPossibleMoves() {
        $owner = $this->getOwner();
        $dieValue = $this->getDieValue();
        $slots = $this->getDiceSlots();
        $res = [];

        if ($dieValue === 0) {
            // Die was reset
            // Add dice options - group by die value (1-6)
            $player_dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$owner");
            $diceByValue = [];
            foreach ($player_dice as $dieKey => $dieInfo) {
                $dieValue = (int) $dieInfo["state"];
                $diceByValue[$dieValue] = $dieKey;
            }

            // Add one option per unique die value
            foreach ($diceByValue as $dieValue => $diceKey) {
                $res[$diceKey] = ["q" => Material::RET_OK];
            }
            return $res;
        }

        // Get caravan assets once for this die value
        $caravanAssets = $this->game->getCaravanAssetsForDie($dieValue, $owner);
        if ($this->isBlueInfluenceSpentThisTurn()) {
            $caravanAssets["ship"] = ($caravanAssets["ship"] ?? 0) + 1;
        }

        foreach ($slots as $cardId => $slot) {
            $state = $slot["state"];
            if ($state > 0) {
                // Slot already occupied
                $res[$cardId] = ["q" => Material::ERR_OCCUPIED];
            } else {
                $missing = $this->canPlaceDieOnCard($cardId, $caravanAssets);
                if (empty($missing)) {
                    $res[$cardId] = ["q" => Material::RET_OK];
                } else {
                    // XXX fix
                    $res[$cardId] = ["q" => Material::ERR_COST, "missing" => $missing];
                }
            }
        }

        $die = $this->getDie();
        // Check caravan column for dicePlus, diceMinus abilities (already in caravanAssets)
        if ((($caravanAssets["dicePlus"] ?? 0) > 0 || ($caravanAssets["diceMod"] ?? 0) > 0) && $dieValue < 6) {
            $dieValuePlus = $dieValue + 1;
            $res["Op_dicePlus"] = ["q" => Material::RET_OK, "name" => "[wicon_die_$dieValue]⤇[wicon_die_$dieValuePlus]"];
        }
        if ((($caravanAssets["diceMinus"] ?? 0) > 0 || ($caravanAssets["diceMod"] ?? 0) > 0) && $dieValue > 1) {
            $dieValueM1 = $dieValue - 1;
            $res["Op_diceMinus"] = ["q" => Material::RET_OK, "name" => "[wicon_die_$dieValue]⤇[wicon_die_$dieValueM1]"];
        }

        // Add influence spending options
        $ops = ["spendInfBlue", "spendInfYellow"];
        foreach ($ops as $opId) {
            $op = $this->game->machine->instanciateOperation($opId, $owner);
            $res[$op->getOpId()] = ["q" => Material::RET_OK, "name" => $op->getIconicName(), "color" => "secondary"];
            $void = $op->noValidTargets();
            if ($void) {
                $res[$op->getOpId()]["q"] = Material::ERR_NOT_APPLICABLE;
                $res[$op->getOpId()]["err"] = $op->getError();
            }
        }

        $res["change"] = ["q" => Material::RET_OK, "name" => clienttranslate("Switch Die")];

        return $res;
    }

    public function getUiArgs() {
        $selectedDie = $this->getDie();
        if (!$selectedDie) {
            return ["imagebuttons" => true];
        }
        return ["buttons" => false];
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $selectedDie = $this->getDie();
        $dieValue = $this->getDieValue();
        $selected = $this->getCheckedArg();
        if ($selected === "change") {
            $this->queue("placeDie", $owner, ["reason" => $this->getReason()]);
            return;
        }
        if (str_starts_with($selected, "dice_")) {
            $this->queue("placeDie", $owner, ["die" => $selected, "reason" => $this->getReason()]);
            return;
        }
        // Handle extra action - queue the operation and re-enter placeDie
        if (str_starts_with($selected, "Op_")) {
            $optype = str_replace("Op_", "", $selected);
            $this->queue($optype, $owner, ["die" => $selectedDie]);
            $this->queue("placeDie", $owner, ["die" => $selectedDie, "reason" => $this->getReason()]);
            return;
        }

        // Normal case - place die on a card
        $cardId = $selected;
        $this->dbSetTokenLocation(
            $selectedDie,
            $cardId,
            $dieValue,
            clienttranslate('${player_name} places Die ${new_state} onto ${place_name}')
        );
        // Check for folk card tucked under this card (same state) and activate its ability
        $folkCard = $this->getTuckedFolk($cardId);
        if ($folkCard) {
            $folkRule = $this->game->getRulesFor($folkCard, "dr", "");
            if ($folkRule) {
                $this->queue($folkRule, $owner, ["die" => $selectedDie, "reason" => $folkCard]);
            }
        }
        // XXX player can chose order
        $r = $this->game->getRulesFor($cardId, "dr");
        $r = $this->applyFoodDiscount($r);
        $op = $this->instanciateOperation($r, $owner, ["die" => $selectedDie, "reason" => $cardId]);
        $op->checkVoid();
        $this->queue($r, $owner, ["die" => $selectedDie, "reason" => $cardId]);
    }

    public function getExtraArgs() {
        $dieValue = $this->getDieValue();
        return parent::getExtraArgs() + ["token_div" => "wicon_die_$dieValue"];
    }
    public function getPrompt() {
        $dieValue = $this->getDieValue();
        if ($dieValue == 0) {
            return clienttranslate("Select a die");
        }
        return clienttranslate('Select where to place the die ${token_div} or');
    }
}
