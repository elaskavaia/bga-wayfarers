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
use Bga\Games\wayfarers\OpCommon\Operation;

use function Bga\Games\wayfarers\getPart;

abstract class Op_cardBase extends Operation {
    public function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    function canAfford(string $op) {
        return !$this->game->machine->instanciateOperation($op, $this->getOwner())->isVoid();
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
            $payop = $this->getPaymentOperation($card);
            $children = $info["children"] ?? [];

            $inf = "";
            if (count($children) > 0) {
                $inf = array_key_first($children);
            }
            $can = $this->canAfford($payop);
            $res[$card] = ["q" => $can ? 0 : Material::ERR_COST, "can" => $can, "pay" => $payop, "inf" => $inf];
        }

        return $res;
    }

    function getPaymentOperation(string $card) {
        return "2n_food";
    }

    function placeCard($card) {
        $owner = $this->getOwner();
        $cardType = $this->getCardType();
        $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_$cardType", "tableau_$owner");
        $this->game->tokens->dbSetTokenLocation($card, "tableau_$owner", count($tokens) + 1);
    }
    function resolve(): void {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();

        $args = $this->getArgs();
        $info = $args["info"][$card];
        $this->queue($info["pay"]);
        $inf = $info["inf"];
        if ($inf) {
            $this->queue("food/coin", $owner, [], $inf);
            // return card influence
            $opp = getPart($inf, 2);
            $this->game->tokens->dbSetTokenLocation($inf, "tableau_$opp", 0);
        }

        $this->placeCard($card);

        $r = $this->game->getRulesFor($card, "r");
        if ($r) {
            $this->queue($r, $owner, ["card" => $card], $card);
        }
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
