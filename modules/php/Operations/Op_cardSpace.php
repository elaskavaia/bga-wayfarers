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

use Bga\GameFramework\NotificationMessage;

class Op_cardSpace extends Op_cardBase {
    public function getPossibleMoves() {
        // Check if there's an available position
        if ($this->getNextAvailablePosition() === null) {
            return ["err" => clienttranslate("No available position for this card")];
        }
        return parent::getPossibleMoves();
    }

    /**
     * Find the next available position where a land/water card exists but no space card
     */
    function getNextAvailablePosition(): ?int {
        $owner = $this->getOwner();

        // Get positions occupied by land and water cards
        $landCards = $this->game->tokens->getTokensOfTypeInLocation("card_land", "tableau_$owner");
        $waterCards = $this->game->tokens->getTokensOfTypeInLocation("card_water", "tableau_$owner");
        $spaceCards = $this->game->tokens->getTokensOfTypeInLocation("card_space", "tableau_$owner");

        // Build set of positions occupied by space cards
        $occupiedBySpace = [];
        foreach ($spaceCards as $spaceInfo) {
            $occupiedBySpace[(int) $spaceInfo["state"]] = true;
        }

        // Find positions that have land/water but no space card
        $availablePositions = [];
        foreach ($landCards as $cardInfo) {
            $pos = (int) $cardInfo["state"];
            if (!isset($occupiedBySpace[$pos])) {
                $availablePositions[$pos] = true;
            }
        }
        foreach ($waterCards as $cardInfo) {
            $pos = (int) $cardInfo["state"];
            if (!isset($occupiedBySpace[$pos])) {
                $availablePositions[$pos] = true;
            }
        }

        if (count($availablePositions) == 0) {
            return null;
        }

        // Return the position closest to 0
        // For positive: pick lowest (e.g., 1 before 2)
        // For negative: pick highest (e.g., -1 before -2)
        $positions = array_keys($availablePositions);
        usort($positions, fn($a, $b) => abs($a) <=> abs($b));
        return $positions[0];
    }

    function getCardType() {
        return "space";
    }

    public function getPaymentOperation(?string $card = null): string {
        $c = max(0, $this->getCost($card) - $this->getCoinDiscount());
        return "{$c}n_coin";
    }
    public function getCost(?string $card): int {
        if (!$card) {
            return 5;
        }
        return $this->getCostPos($this->game->tokens->db->getTokenState($card, 0));
    }
    function getCostPos($space): int {
        return match ($space) {
            0 => 5,
            1 => 3,
            2 => 4,
            3 => 4,
            4 => 5,
        };
    }

    function placeCard($card) {
        $owner = $this->getOwner();
        $pos = $this->getNextAvailablePosition();
        $this->game->systemAssert("Cannot find position for space card", $pos);
        $this->game->tokens->dbSetTokenLocation($card, "tableau_$owner", $pos, clienttranslate('${player_name} acquires ${token_name}'));
    }

    public function getPrompt() {
        $dis = $this->getCoinDiscount();
        if ($dis > 0) {
            return new NotificationMessage(clienttranslate('Select a Space Card to buy with Silver discount of ${dis}'), ["dis" => $dis]);
        }
        return clienttranslate("Select a Space Card to buy");
    }

    public function getIconicName() {
        return "[wicon_card_space]";
    }
}
