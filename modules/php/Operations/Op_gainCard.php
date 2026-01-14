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

use Bga\Games\wayfarers\OpCommon\Operation;

class Op_gainCard extends Operation {
    public function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    function getCost(string $card): int {
        return 0;
    }
    public function getCard() {
        return $this->getDataField("card", null);
    }
    function getCardType() {
        return "home";
    }

    function getPossibleMoves() {
        $res = [];
        $cardType = $this->getCardType();

        $tokens = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card_$cardType", "mainarea");

        foreach ($tokens as $card => $info) {
            $cost = $this->getCost($card);
            $children = $info["children"] ?? [];
            $ex = 0;
            if (count($children) > 0) {
                $ex = 1;
            }
            $res[$card] = ["q" => 0, "cost" => $cost, "extra_cost" => $ex];
        }

        return $res;
    }

    function effect_pay(string $card) {
        $owner = $this->getOwner();
        $this->game->effect_incCount($owner, "food", -2, $this->getOpId());
    }

    /** User does the action */
    function resolve(): void {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();
        $cardType = $this->getCardType();
        $this->effect_pay($card);
        $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_$cardType", "tableau_$owner");
        $this->game->tokens->dbSetTokenLocation($card, "tableau_$owner", count($tokens));
        $this->queue("drawTab", $owner, ["card" => $card]);
        return;
    }

    public function getPrompt() {
        return clienttranslate("Select a card to buy");
    }

    public function getUiArgs() {
        return ["buttons" => false];
    }
    public function requireConfirmation() {
        return true;
    }
}
