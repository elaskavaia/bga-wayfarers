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

/**
 * AI Rest Operation
 *
 * RULES (from RULES.md lines 284-293):
 *
 * When the AI Rests:
 * 1. Check the most recently revealed Scheme Card. If it shows a Comet in the bottom-right corner,
 *    move their Marker one space up their Comet Track.
 * 2. The AI will acquire a Space Card, Townsfolk Card, Upgrade Tile, or Influence a Card
 *    (based on their AI board's r1 field).
 * 3. The AI will Journal.
 *
 * Follow these steps in order. Do not refresh any Cards until the AI has completed their turn.
 * After finishing their Rest, shuffle all their Scheme Cards back into a facedown Draw Pile.
 */
class Op_ai_rest extends AiOperation {
    /**
     * Get AI board number from player board state (negative state value)
     */
    function getAiBoardNumber(): int {
        $owner = $this->getOwner();
        return -(int) $this->game->tokens->db->getTokenState("pboard_$owner");
    }

    /**
     * Auto-resolve: AI rests
     */
    public function auto(): bool {
        $owner = $this->getOwner();
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_scheme", "tableau_$owner", null, "token_state");
        $scheme = array_key_last($cards);
        $this->game->systemAssert("No scheme cards available for rest", $scheme);

        // Step 1: Check most recently revealed Scheme Card for Comet
        // When Resting, follow the steps shown in the blue banner at the
        // bottom of the AI Board in order, from left to right.
        // The first step will always be to check the most recently
        // revealed Scheme Card. If it shows a Comet in the bottom-
        // right corner, move their Marker 1 space up their Comet Track.
        $comet = (int) $this->game->getRulesFor($scheme, "comet", 0);
        if ($comet) {
            [$trackerId, $currentPos] = $this->game->tokens->getTrackerIdAndValue($owner, "comet");
            $newPos = $currentPos + 1;
            if ($newPos <= 10) {
                // Max comet track is 10
                $this->dbSetTokenState($trackerId, $newPos, clienttranslate('${player_name} moves comet marker to ${pos}'), [
                    "pos" => $newPos,
                ]);
            }
        }

        // Step 2: Acquire (based on AI board r1 field)
        // The second step will have them acquiring stuff, rule r1
        $boardNumber = $this->getAiBoardNumber();
        $acquire = $this->game->getRulesForAndAssert("aiboard_$boardNumber", "r1");
        $this->queue($acquire);

        // Step 3: Journal
        $this->queue("ai_journal");

        // Step 4: Shuffle all scheme cards back into facedown draw pile
        $this->queue("ai_shuffle");

        return true;
    }
}
