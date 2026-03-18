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
 * Op_cardInsp: Acquire an inspiration card
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\Material;

class Op_cardInsp extends Op_cardBase {
    // RULES:     When gaining an Inspiration Card, players may tuck it above any
    // of their Space Cards that doesn’t already have an Inspiration Card.
    // If they have no Space Cards available, or if they don’t wish to gain
    // an Inspiration Card (because they don’t think they can achieve its
    // goal), players always have the option to instead discard it for an
    // immediate effect. The immediate effect they gain is from the Worker
    // Placement spot that the Card was adjacent to (see page 16). Cards
    // discarded this way should be placed facedown under the Inspiration
    // Card Draw Pile.

    function getCardType() {
        return "insp";
    }

    function getPossibleMoves() {
        $cardSelected = $this->getCard();

        if ($cardSelected == null) {
            // First step: select which inspiration card to acquire
            $res = [];
            $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_insp", "mainarea");

            foreach (array_keys($tokens) as $card) {
                // there is NO requirement on playing the card excp placement below
                $res[$card] = ["q" => Material::RET_OK];
            }

            return $res;
        }

        // Second step: select where to place the card (which space card to tuck under)
        $res = [];
        $availablePositions = $this->getAvailablePositions();

        foreach ($availablePositions as $spaceCard) {
            $res[$spaceCard] = ["q" => Material::RET_OK];
        }

        $res["discard"] = ["q" => 0, "name" => clienttranslate("Discard")];

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
            $this->queue($this->getTypeFullExpr(), $owner, ["card" => $this->getCheckedArg()]);
            return;
        }

        $arg = $this->getCheckedArg();
        if ($arg == "discard") {
            $state = $this->game->tokens->db->getTokenState($cardSelected);
            // Gain the benefit
            $workerRule = $this->game->getRulesForAndAssert("action_insp_{$state}", "r");
            $this->queue($workerRule);
            // Discard the card
            $deck = "deck_insp";
            $extreme_pos = $this->game->tokens->db->getExtremePosition(false, $deck);
            $this->dbSetTokenLocation($cardSelected, $deck, $extreme_pos - 1, clienttranslate('${player_name} discards ${token_name}'));
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
        if (str_starts_with($spaceCard, "card_home")) {
            $targetState = 1;
        }
        $this->dbSetTokenLocation($card, "tableau_$owner", $targetState, clienttranslate('${player_name} acquires ${token_name}'));
    }

    public function getPrompt() {
        $card = $this->getCard();
        if ($card == null) {
            return clienttranslate("Select an Inspiration card to acquire or discard");
        }
        return clienttranslate("Select a Space card to tuck under");
    }
}
