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

class Op_cardFolk extends Op_cardBase {
    function getPossibleMoves() {
        $cardSelected = $this->getCard();
        $res = [];
        $owner = $this->getOwner();
        $allCards = $this->game->tokens->getTokensOfTypeInLocation("card", "tableau_$owner");

        // Separate folk cards and other cards, build occupied states set
        $cards = [];
        $occupiedStates = [];
        foreach ($allCards as $tcard => $info) {
            if (str_starts_with($tcard, "card_folk")) {
                $occupiedStates[(int) $info["state"]] = true;
            } else {
                $cards[$tcard] = $info;
            }
        }

        // Mark which card positions are occupied by folk cards
        foreach ($cards as $tcard => &$info) {
            $cardState = (int) $info["state"];
            $info["folkon"] = $occupiedStates[$cardState] ?? false;
        }
        unset($info);
        if ($cardSelected == null) {
            $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_folk", "mainarea");

            foreach ($tokens as $card => $tokenInfo) {
                $cost = $this->getCost($card);
                $res[$card] = ["q" => Material::ERR_PREREQ, "cost" => $cost, "info" => []];

                foreach ($cards as $tcard => $cardInfo) {
                    if ($this->hasMatchingTags($card, $tcard)) {
                        if ($cardInfo["folkon"]) {
                            // Position already has a folk card - not available
                            $res[$card]["info"][$tcard] = ["q" => Material::ERR_OCCUPIED];
                        } else {
                            // Position available
                            $res[$card]["q"] = Material::RET_OK;
                            $res[$card]["info"][$tcard] = ["q" => Material::RET_OK];
                        }
                    }
                }
            }
            return $res;
        }
        // where to put it

        foreach ($cards as $tcard => $info) {
            if ($this->hasMatchingTags($cardSelected, $tcard) && !$info["folkon"]) {
                $res[$tcard] = ["q" => 0];
            }
        }
        return $res;
    }

    function hasMatchingTags($greenCard, $card) {
        $tagsGreen = $this->game->getTagsSet($greenCard);
        $tags = $this->game->getTagsSet($card);
        foreach ($tagsGreen as $tag => $x) {
            if (isset($tags[$tag])) {
                return true;
            }
        }
        return false;
    }

    function getCost($card): int {
        return (int) $this->game->getRulesFor("$card", "r", 5);
    }
    public function getCard() {
        return $this->getDataField("card", null);
    }
    /** User does the action */
    function resolve(): void {
        $cardSelected = $this->getCard();
        $owner = $this->getOwner();
        if ($cardSelected == null) {
            $this->queue($this->getType(), $owner, ["card" => $this->getCheckedArg()]);
            return;
        }
        $owner = $this->getOwner();
        $cardTuck = $this->getCheckedArg();
        $cost = $this->getCost($cardSelected);
        $this->game->effect_incCount($owner, "coin", -$cost, $this->getOpId());
        // Get the state of the target card to place folk card at same state
        $targetState = (int) $this->game->tokens->db->getTokenState($cardTuck);
        $this->game->tokens->dbSetTokenLocation(
            $cardSelected,
            "tableau_$owner",
            $targetState,
            clienttranslate('${player_name} buys Townfolk card ${token_name}')
        );
        $this->queue("drawTab", $owner, ["card" => $cardSelected]);
        return;
    }

    public function getPrompt() {
        $card = $this->getCard();
        $owner = $this->getOwner();
        if ($card == null) {
            return clienttranslate("Select a green card to buy");
        }
        return clienttranslate("Select a card to tuck under");
    }
}
