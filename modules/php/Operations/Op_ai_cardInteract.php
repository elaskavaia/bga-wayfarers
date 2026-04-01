<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * wayfarers implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use function Bga\Games\wayfarers\getPart;

/**
 * AI card interaction commit phase.
 * Handles influence return and worker transfer after the player has allowed the interaction
 * (or when no opponent influence exists). The allow/deny choice is handled by Op_ai_cardInteractChoice.
 */
class Op_ai_cardInteract extends Op_cardInteract {
    public function auto(): bool {
        $owner = $this->getOwner();
        $card = $this->getCard();
        $inf = $this->getInfluenceOnCard();

        if ($inf) {
            $opp = getPart($inf, 1);

            // Return the influence token to the player's tableau (unless buy is false)
            if ($this->isBeingBought()) {
                $this->dbSetTokenLocation($inf, "tableau_$opp", 0);
            }
        }

        // If card is being bought, move any workers on it to the buyer's tableau
        if ($this->isBeingBought() && $card) {
            $workers = $this->game->tokens->getTokensOfTypeInLocation("worker", $card);
            foreach (array_keys($workers) as $workerKey) {
                $this->dbSetTokenLocation($workerKey, "tableau_$owner", 0, clienttranslate('${player_name} gains ${token_name} ${reason}'));
            }
        }
        return true;
    }
}
