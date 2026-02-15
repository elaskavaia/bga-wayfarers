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

class Op_cardLand extends Op_cardBase {
    function getCardType() {
        return "land";
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
        // Get display cards from parent
        $res = parent::getPossibleMoves();

        // Add deck as an option if not empty
        if (!$this->isDeckEmpty()) {
            $deck = $this->getDeck();
            $res[$deck] = ["q" => Material::RET_OK, "deck" => true];
        }

        return $res;
    }

    function placeCard($card) {
        $owner = $this->getOwner();
        $cardType = $this->getCardType();
        $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_$cardType", "tableau_$owner");
        $this->dbSetTokenLocation($card, "tableau_$owner", -count($tokens) - 2);
    }
}
