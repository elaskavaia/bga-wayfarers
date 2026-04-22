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

class Op_cardWater extends Op_cardBase {
    function getCardType() {
        return "water";
    }

    function getDeck(): string {
        return "deck_" . $this->getCardType();
    }

    function isDeckEmpty(): bool {
        $deck = $this->getDeck();
        $count = $this->game->tokens->db->countTokensInLocation($deck);
        return $count == 0;
    }

    function getPossibleMoves() {
        $cardSelected = $this->getCard();
        if ($cardSelected) {
            return [$cardSelected];
        }

        // Get display cards from parent
        $res = parent::getPossibleMoves();

        // Add deck as an option if not empty
        if (!$this->isDeckEmpty()) {
            $deck = $this->getDeck();
            $res[$deck] = ["q" => Material::RET_OK, "deck" => true];
        }

        return $res;
    }

    public function placeCard($card) {
        parent::placeCard($card);
        $this->checkSideMatchingBonuses();
    }

    private function checkSideMatchingBonuses(): void {
        $owner = $this->getOwner();
        $placedCard = $this->getCheckedArg();
        $placedState = (int) $this->game->tokens->db->getTokenState($placedCard);
        $prevState = $placedState - 1;

        // Find the previous card (state - 1)
        $waterCards = $this->game->tokens->getTokensOfTypeInLocation("card_water", "tableau_$owner", $prevState);
        $prevCard = array_key_first($waterCards);

        $prevC2 = "yxxx"; // starting card
        if ($prevCard) {
            $prevC2 = $this->game->getRulesForAndAssert($prevCard, "c2", "");
        }

        // Get card sides
        $placedC1 = $this->game->getRulesForAndAssert($placedCard, "c1", "");

        // Check if previous card's right side matches placed card's left side
        $this->checkMatch($prevC2, $placedC1);
    }

    private function checkMatch(string $rightSide, string $leftSide): void {
        for ($pos = 0; $pos < 4; $pos++) {
            $rightChar = $rightSide[$pos] ?? "_";
            $leftChar = $leftSide[$pos] ?? "_";

            if ($rightChar !== "_" && $leftChar === "x") {
                $this->grantBonus($pos, $rightChar);
            }
        }
    }

    private function grantBonus(int $position, string $typeChar): void {
        $owner = $this->getOwner();
        switch ($position) {
            case 0: // influence - check for specific type
                switch ($typeChar) {
                    case "b":
                        $this->queue("infBlack");
                        return;
                    case "u":
                        $this->queue("infBlue");
                        return;
                    case "y":
                        $this->queue("infYellow");
                        return;
                }
                break;
            case 1: // coin
                $this->queue("coin", $owner);
                break;
            case 2: // food
                $this->queue("food", $owner);
                break;
            case 3: // infCard
                $this->queue("infCard", $owner);
                break;
        }
    }
}
