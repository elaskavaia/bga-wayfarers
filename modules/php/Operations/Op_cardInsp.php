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
 * Op_cardInsp: Acquire an inspiration card (goal card)
 *
 * Inspiration cards are goal cards that players can claim when they have met
 * certain collection requirements (tags, cards, upgrades, influence, resources).
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\Material;

class Op_cardInsp extends Op_cardBase {
    function getCardType() {
        return "insp";
    }

    function getPossibleMoves() {
        $cardSelected = $this->getCard();

        if ($cardSelected == null) {
            // First step: select which inspiration card to acquire
            $res = [];
            $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_insp", "mainarea");

            // Get available positions
            $availablePositions = $this->getAvailablePositions();

            foreach (array_keys($tokens) as $card) {
                // there is requirement on playing the card
                $res[$card] = ["q" => Material::RET_OK];

                // Check if there's at least one available position
                if (count($availablePositions) == 0) {
                    $res[$card]["q"] = Material::ERR_PREREQ;
                }
            }

            return $res;
        }

        // Second step: select where to place the card (which space card to tuck under)
        $res = [];
        $availablePositions = $this->getAvailablePositions();

        foreach ($availablePositions as $spaceCard) {
            $res[$spaceCard] = ["q" => Material::RET_OK];
        }

        return $res;
    }

    /**
     * Get available positions (space cards without inspiration cards)
     * Returns array mapping position => space card key
     */
    function getAvailablePositions(): array {
        $owner = $this->getOwner();

        $spaceCards = $this->game->tokens->getTokensOfTypeInLocation("card_space", "tableau_$owner");
        $inspCards = $this->game->tokens->getTokensOfTypeInLocation("card_insp", "tableau_$owner");

        // Build set of positions occupied by inspiration cards
        $occupiedByInsp = [];
        foreach ($inspCards as $inspInfo) {
            $occupiedByInsp[(int) $inspInfo["state"]] = true;
        }

        // Find space cards without inspiration cards
        $availablePositions = [];
        foreach ($spaceCards as $cardKey => $cardInfo) {
            $pos = (int) $cardInfo["state"];
            if (!isset($occupiedByInsp[$pos])) {
                $availablePositions[$pos] = $cardKey;
            }
        }

        return $availablePositions;
    }

    function resolve(): void {
        $cardSelected = $this->getCard();
        $owner = $this->getOwner();

        if ($cardSelected == null) {
            // First step: user selected a card, queue second step
            $this->queue($this->getType(), $owner, ["card" => $this->getCheckedArg()]);
            return;
        }

        // Second step: place the card
        parent::resolve();
    }

    function placeCard($card) {
        $owner = $this->getOwner();
        $spaceCard = $this->getCheckedArg();
        // Get the state of the target space card to place inspiration card at same state
        $targetState = (int) $this->game->tokens->db->getTokenState($spaceCard);
        $this->game->tokens->dbSetTokenLocation(
            $card,
            "tableau_$owner",
            $targetState,
            clienttranslate('${player_name} acquires ${token_name}')
        );
    }

    public function getPrompt() {
        $card = $this->getCard();
        if ($card == null) {
            return clienttranslate("Select an Inspiration card to acquire");
        }
        return clienttranslate("Select a Space card to tuck under");
    }
}
