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

/**
 * AI Upgrade Tile Acquisition (Pink/Special)
 *
 * RULES (from Op_ai_upgAny.php lines 46-49):
 * When acquiring a Special (Pink) Upgrade Tile, the AI prioritises
 * using the reference at the bottom of their most recently revealed
 * Scheme Card. If that Upgrade Tile has already been acquired, they
 * instead acquire the next available Tile in clockwise order.
 *
 * Places tiles in caravan using winding path (inherited from Op_ai_upgAny)
 */
class Op_ai_upgPink extends Op_ai_upgAny {
    /**
     * This is explicit operation for pink tiles only
     */
    function getColorPriority(): array {
        return ["pink"];
    }

    function getPositionPriority(): int {
        // When acquiring a Special (Pink) Upgrade Tile, the AI prioritises
        // using the reference at the bottom of their most recently revealed
        // Scheme Card. If that Upgrade Tile has already been acquired, they
        // instead acquire the next available Tile in clockwise order
        $lastScheme = $this->getRecentCard();
        $position = $this->game->getRulesFor($lastScheme, "p");
        return (int) $position;
    }

    public function getNextPositionPriorityDirection(int $prev): int {
        return 1;
    }
}
