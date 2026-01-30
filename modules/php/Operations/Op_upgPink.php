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
 * Pink (Special) upgrade tiles (1x1 square)
 * These are unique tiles - only 1 copy of each exists
 */
class Op_upgPink extends Op_upgBase {
    function getTileType(): string {
        return "pink";
    }

    function getPaymentOperation(?string $card = null): string {
        return "nop";
    }

    function getTileWidth(): int {
        return 1; // Pink tiles are 1x1 (square)
    }

    function getTileHeight(): int {
        return 1;
    }
}
