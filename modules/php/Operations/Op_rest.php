<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * wayfarers implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\OpCommon\Operation;

class Op_rest extends Operation {
    /**
     * Get all dice currently placed on cards (not in player's supply)
     */
    function getPlacedDice(): array {
        $owner = $this->getOwner();
        $cards = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card", "tableau_$owner");
        $placedDice = [];
        foreach ($cards as $card => $info) {
            if (isset($info["children"])) {
                foreach ($info["children"] as $childKey => $childInfo) {
                    if (str_starts_with($childKey, "dice_")) {
                        $placedDice[$childKey] = $childInfo;
                    }
                }
            }
        }
        return $placedDice;
    }

    /**
     * Get dice in player's supply (tableau)
     */
    function getDiceInPlayerSupply(): array {
        $owner = $this->getOwner();
        return $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$owner");
    }

    /**
     * Get all cards with rest abilities on player's tableau
     * Returns assoc array of card keys that have rest abilities
     */
    function getRestAbilityCards(): array {
        $owner = $this->getOwner();
        $cards = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card", "tableau_$owner");
        $restCards = [];

        foreach ($cards as $card => $info) {
            // Check if this card has rest ability
            $hasRest = $this->game->getRulesFor($card, "rest", 0);
            if ($hasRest) {
                $restCards[$card] = $info;
            }
        }
        return $restCards;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $placedDice = $this->getPlacedDice();
        $diceInSupply = $this->getDiceInPlayerSupply();

        $activateRestingAbilities = $this->isGoodRest();

        // Notify about rest action
        $this->game->notifyMessage(clienttranslate('${player_name} rests'));

        // Queue resting abilities if eligible (0-1 dice in supply before rest)
        if ($activateRestingAbilities) {
            $restCards = $this->getRestAbilityCards();
            foreach (array_keys($restCards) as $cardKey) {
                $dr = $this->game->getRulesForAndAssert($cardKey, "dr", "");
                $this->queue($dr);
            }
        }

        // Move all placed dice back to player's tableau and roll them
        foreach ($placedDice as $dieKey => $dieInfo) {
            $this->queue("reroll", $owner, ["die" => $dieKey, "confirmed" => true]);
        }

        // Also reroll dice that were already in supply (optional per rules)
        foreach ($diceInSupply as $dieKey => $dieInfo) {
            $this->queue("reroll", $owner, ["die" => $dieKey]);
        }
    }

    public function getPrompt() {
        if ($this->isGoodRest()) {
            return clienttranslate("Confirm Rest");
        } else {
            return clienttranslate("Confirm Rest (no rest abilities will activate!)");
        }
    }

    public function isGoodRest() {
        $diceInSupply = $this->getDiceInPlayerSupply();

        if (count($diceInSupply) <= 1) {
            return true;
        }
        return false;
    }

    public function requireConfirmation() {
        if ($this->isGoodRest()) {
            return false;
        }
        return true;
    }

    public function getIconicName() {
        return "[wicon_rest]";
    }
}
