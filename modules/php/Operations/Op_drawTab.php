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

use function Bga\Games\wayfarers\getPart;

class Op_drawTab extends Operation {
    public function requireConfirmation() {
        return true;
    }

    public function getPrompt() {
        return clienttranslate("Confirm to draw cards to replenish, this cannot be undone");
    }

    public function getCard() {
        return $this->getDataField("card", "card_xxx");
    }

    function resolve(): void {
        $card = $this->getCard();
        $ctype = getPart($card, 1);

        $deck = "deck_$ctype";
        // find the missing card
        $gaps = [-1, 0, 0, 0, 0];
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_$ctype", "mainarea");
        foreach ($cards as $card => $info) {
            $state = $info["state"];
            $gaps[$state] = 1;
        }
        $prev = array_find_key($gaps, fn($x) => $x === 0);
        foreach ($cards as $card => $info) {
            $state = $info["state"];
            if ($state >= $prev) {
                $this->game->tokens->dbSetTokenState($card, $state - 1, "");
            }
        }

        $this->game->tokens->dbPickTokenForLocation(
            $deck,
            "mainarea",
            4,
            clienttranslate('${player_name} draws ${token_name} into display'),
            [],
            $this->getPlayerId()
        );
        $this->game->customUndoSavepoint($this->getPlayerId(), 1, $this->getOpId());
    }
}
