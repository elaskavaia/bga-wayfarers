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

class Op_cardGreen extends Op_gainCard {
    function getPossibleMoves() {
        $cardSelected = $this->getCard();
        $res = [];
        $owner = $this->getOwner();
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card", "tableau_$owner");

        foreach ($cards as $tcard => &$info) {
            $info["folkon"] = 0;
            $folks = $this->game->tokens->getTokensOfTypeInLocation("card", $tcard);
            if (count($folks) > 0) {
                $info["folkon"] = 1;
            }
        }
        if ($cardSelected == null) {
            $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_folk", "mainarea");

            foreach ($tokens as $card => $info) {
                $cost = $this->getCost($card);
                $res[$card] = ["q" => Material::MA_ERR_PREREQ, "cost" => $cost, "info" => []];

                foreach ($cards as $tcard => $info) {
                    if ($this->hasMatchingTags($card, $tcard)) {
                        if ($info["folkon"] == 0) {
                            $res[$card] = ["q" => 0];
                            $res[$card]["info"][$tcard] = ["q" => 0];
                        } else {
                            $res[$card]["info"][$tcard] = ["q" => Material::MA_ERR_OCCUPIED];
                        }
                    }
                }
            }
            return $res;
        }
        // where to put it

        foreach ($cards as $tcard => $info) {
            if ($this->hasMatchingTags($cardSelected, $tcard)) {
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
        $this->game->tokens->dbSetTokenLocation(
            $cardSelected,
            $cardTuck,
            0,
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
