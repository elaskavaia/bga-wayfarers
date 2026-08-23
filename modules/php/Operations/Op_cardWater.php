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
        $this->grantSideMatchingBonuses();
    }

    private function grantSideMatchingBonuses(): void {
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
        $data = ["reason" => "joinBonus"];
        switch ($position) {
            case 0: // influence - check for specific type
                switch ($typeChar) {
                    case "b":
                        $this->queuePool("infBlack", $data);
                        return;
                    case "u":
                        $this->queuePool("infBlue", $data);
                        return;
                    case "y":
                        $this->queuePool("infYellow", $data);
                        return;
                }
                break;
            case 1: // coin
                $this->queuePool("coin", $data);
                break;
            case 2: // food
                $this->queuePool("food", $data);
                break;
            case 3: // infCard
                $this->queuePool("infCard", $data);
                break;
        }
    }
}
