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
    function getDiceInSupply(): array {
        $owner = $this->getOwner();
        return $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$owner");
    }

    /**
     * Get all townsfolk cards with rest abilities on player's tableau
     * Returns array of card keys that have rest abilities
     */
    function getRestAbilityCards(): array {
        $owner = $this->getOwner();
        $cards = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card", "tableau_$owner");
        $restCards = [];

        foreach ($cards as $card => $info) {
            // Check children (tucked folk cards)
            if (isset($info["children"])) {
                foreach ($info["children"] as $childKey => $childInfo) {
                    if (str_starts_with($childKey, "card_folk_")) {
                        // Check if this folk card has rest ability
                        $hasRest = $this->game->getRulesFor($childKey, "rest", 0);
                        if ($hasRest) {
                            $restCards[$childKey] = $childInfo;
                        }
                    }
                }
            }
        }
        return $restCards;
    }

    function getPossibleMoves() {
        return ["confirm"];
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $placedDice = $this->getPlacedDice();
        $diceInSupply = $this->getDiceInSupply();

        // Count dice in supply before rest to determine if resting abilities activate
        $supplyCount = count($diceInSupply);
        $activateRestingAbilities = $supplyCount <= 1;

        // Move all placed dice back to player's tableau and roll them
        foreach ($placedDice as $dieKey => $dieInfo) {
            // Roll the die (random value 1-6)
            $newValue = bga_rand(1, 6);
            $this->game->tokens->dbSetTokenLocation(
                $dieKey,
                "tableau_$owner",
                $newValue,
                clienttranslate('${player_name} rests and retrieves ${token_name}')
            );
        }

        // Also reroll dice that were already in supply (optional per rules)
        foreach ($diceInSupply as $dieKey => $dieInfo) {
            $newValue = bga_rand(1, 6);
            $this->game->tokens->dbSetTokenState($dieKey, $newValue);
        }

        // Notify about rest action
        $this->game->notifyMessage(clienttranslate('${player_name} rests'));

        // Queue resting abilities if eligible (0-1 dice in supply before rest)
        if ($activateRestingAbilities) {
            $restCards = $this->getRestAbilityCards();
            foreach (array_keys($restCards) as $cardKey) {
                $dr = $this->game->getRulesFor($cardKey, "dr", "");
                if ($dr) {
                    $this->queue($dr);
                }
            }
        }
    }

    public function getPrompt() {
        return clienttranslate("Confirm Rest");
    }
}
