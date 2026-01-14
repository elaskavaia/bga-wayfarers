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

class Op_cardSpace extends Op_gainCard {
    public function getPossibleMoves() {
        $owner = $this->getOwner();
        $c = count($this->game->tokens->getTokensOfTypeInLocation("card_land", "tableau_$owner"));
        $c += count($this->game->tokens->getTokensOfTypeInLocation("card_water", "tableau_$owner"));
        $spaceC = count($this->game->tokens->getTokensOfTypeInLocation("card_space", "tableau_$owner"));
        if ($spaceC >= $c) {
            return ["err" => clienttranslate("Not enought space")];
        }
        return parent::getPossibleMoves();
    }
    function getCardType() {
        return "space";
    }
    public function getCost(string $card): int {
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
    function effect_pay(string $card) {
        $owner = $this->getOwner();
        $cost = $this->getCost($card);
        $this->game->effect_incCount($owner, "coin", -$cost, $this->getOpId());
    }

    /** User does the action */
    function resolve(): void {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();

        $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_space", "tableau_$owner");
        $this->game->tokens->dbSetTokenLocation($card, "tableau_$owner", count($tokens)); //XXX pick location
        return;
    }
}
