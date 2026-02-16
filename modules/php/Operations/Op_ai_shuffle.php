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

use Bga\Games\wayfarers\OpCommon\AiOperation;

class Op_ai_shuffle extends AiOperation {
    /**
     * Auto-resolve:  Reshuffles its scheme deck
     */
    public function auto(): bool {
        $owner = $this->getOwner();
        //  Shuffle all scheme cards back into facedown draw pile
        $allCards = $this->game->tokens->getTokensOfTypeInLocation("card_scheme", "tableau_$owner");
        foreach (array_keys($allCards) as $cardKey) {
            $this->dbSetTokenLocation($cardKey, "deck_scheme", 0, "");
        }

        // Shuffle the deck
        $this->game->tokens->db->shuffle("deck_scheme");
        $this->notifyMessage('${player_name} shuffles scheme cards back to deck');

        return true;
    }
}
