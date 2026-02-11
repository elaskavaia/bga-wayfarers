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
                $pay = $this->getPaymentOperation($card);
                $can = $this->canAfford($pay);
                $res[$card] = ["q" => $can ? 0 : Material::ERR_COST, "can" => $can, "pay" => $pay];

                if (!$can) {
                    continue;
                }
                $res[$card]["q"] = Material::ERR_PREREQ;
                foreach ($cards as $tcard => $cardInfo) {
                    if ($this->hasMatchingTags($card, $tcard) && !$cardInfo["folkon"]) {
                        // At least one position available
                        $res[$card]["q"] = Material::RET_OK;
                        break;
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
        return (int) $this->game->getRulesFor("$card", "cost", 5);
    }

    public function getPaymentOperation(?string $card = null): string {
        $cost = max(0, $this->getCost($card) - $this->getCoinDiscount());
        return "{$cost}n_coin";
    }

    /** User does the action */
    function resolve(): void {
        $cardSelected = $this->getCard();
        $owner = $this->getOwner();
        if ($cardSelected == null) {
            $this->queue($this->getType(), $owner, ["card" => $this->getCheckedArg()]);
            return;
        }

        parent::resolve();
    }

    function placeCard($card) {
        $owner = $this->getOwner();
        $cardTuck = $this->getCheckedArg();
        // Get the state of the target card to place folk card at same state
        $targetState = (int) $this->game->tokens->db->getTokenState($cardTuck);
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
            return clienttranslate("Select a Townsfolk Card to buy");
        }
        return clienttranslate("Select a card to tuck under");
    }

    public function getIconicName() {
        return "[wicon_card_folk]";
    }
}
