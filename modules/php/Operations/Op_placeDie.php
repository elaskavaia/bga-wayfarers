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
use BgaUserException;

/**
 * Place a Die on a card in the player's tableau
 * Dice placement requires matching assets from the caravan
 */
class Op_placeDie extends Operation {
    /**
     * Get the selected die from the data field
     */
    function getSelectedDie(): string {
        return $this->getDataField("die", "");
    }

    /**
     * Get the die value
     */
    function getDieValue(): int {
        if (!$this->getSelectedDie()) {
            return 0;
        }
        return (int) $this->game->tokens->db->getTokenState($this->getSelectedDie());
    }

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

        // Check caravan column for dicePlus, diceMinus abilities (already in caravanAssets)
        if (($caravanAssets["dicePlus"] ?? 0) > 0 && $dieValue < 6) {
            $res["Op_dicePlus"] = ["q" => Material::RET_OK];
        }
        if (($caravanAssets["diceMinus"] ?? 0) > 0 && $dieValue > 1) {
            $res["Op_diceMinus"] = ["q" => Material::RET_OK];
        }

        // Add influence spending options
        $ops = ["spendInfBlue", "spendInfYellow"];
        foreach ($ops as $opId) {
            $op = $this->game->machine->instanciateOperation($opId, $owner);
            $void = $op->noValidTargets();
            if ($void) {
                $res[$op->getOpId()] = ["q" => Material::ERR_NOT_APPLICABLE, "err" => $op->getError()];
            } else {
                $res[$op->getOpId()] = ["q" => Material::RET_OK];
            }
        }

        return $res;
    }

    public function getUiArgs() {
        return ["buttons" => false];
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $selectedDie = $this->getSelectedDie();
        $dieValue = $this->getDieValue();
        $selected = $this->getCheckedArg();

        // Handle extra action - queue the operation and re-enter placeDie
        if (str_starts_with($selected, "Op_")) {
            $optype = str_replace("Op_", "", $selected);
            $this->queue($optype, $owner, ["die" => $selectedDie]);
            $this->queue("placeDie", $owner, ["die" => $selectedDie], $this->getReason());
            return;
        }

        // Normal case - place die on a card
        $cardId = $selected;
        $this->game->tokens->dbSetTokenLocation(
            $selectedDie,
            $cardId,
            $dieValue,
            clienttranslate('${player_name} places die ${new_state} onto ${token_name}'),
            [],
            $this->getPlayerId()
        );
        // Check for folk card tucked under this card (same state) and activate its ability
        $folkCard = $this->getTuckedFolk($cardId);
        if ($folkCard) {
            $folkRule = $this->game->getRulesFor($folkCard, "dr", "");
            if ($folkRule) {
                $this->queue($folkRule, $owner, [], $folkCard);
            }
        }
        // XXX player can chose order
        $r = $this->game->getRulesFor($cardId, "dr");
        $this->queue($r, $owner, [], $cardId);
    }

    public function getTuckedFolk(string $card) {
        $owner = $this->getOwner();
        $cardState = (int) $this->game->tokens->db->getTokenState($card);
        $folkCards = $this->game->tokens->getTokensOfTypeInLocation("card_folk", "tableau_$owner", $cardState);
        $folkCard = array_key_first($folkCards);
        return $folkCard;
    }

    public function getExtraArgs() {
        $dieValue = $this->getDieValue();
        return parent::getExtraArgs() + ["token_div" => "die_$dieValue"];
    }
    public function getPrompt() {
        return clienttranslate('Select where to place the die ${token_div}');
    }
}
