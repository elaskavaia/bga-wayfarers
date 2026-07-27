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
 * Black upgrade tiles (1x2 vertical)
 */
class Op_upgBlack extends Op_upgBase {
    function getTileType(): string {
        return "black";
    }

    function getPaymentOperation(?string $card = null): string {
        $c = max(0, 3 - $this->getCoinDiscount());
        return "{$c}n_coin/2n_infBlack";
    }

    function getTileWidth(): int {
        return 1; // Black tiles are 1x2 (vertical)
    }

    function getTileHeight(): int {
        return 2;
    }
}
