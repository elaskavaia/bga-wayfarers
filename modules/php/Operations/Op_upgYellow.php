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
 * Yellow upgrade tiles (2x1 horizontal)
 */
class Op_upgYellow extends Op_upgBase {
    function getTileType(): string {
        return "yellow";
    }
    function getPaymentOperation(?string $card = null): string {
        $c = max(0, 3 - $this->getCoinDiscount());
        return "{$c}n_coin/2n_infYellow";
    }

    function getTileWidth(): int {
        return 2; // Yellow tiles are 2x1 (horizontal)
    }

    function getTileHeight(): int {
        return 1;
    }
}
