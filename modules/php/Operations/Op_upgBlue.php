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
 * Blue upgrade tiles (2x1 horizontal)
 */
class Op_upgBlue extends Op_upgBase {
    function getTileType(): string {
        return "blue";
    }
    function getPaymentOperation(?string $card = null) {
        return "3n_coin/2n_infBlue";
    }

    function getTileWidth(): int {
        return 2; // Blue tiles are 2x1 (horizontal)
    }

    function getTileHeight(): int {
        return 1;
    }
    function isDoubleSided(): bool {
        return true;
    }
}
