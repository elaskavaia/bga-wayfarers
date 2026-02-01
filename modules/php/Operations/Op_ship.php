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

/**
 * Ship asset - provides ship asset for dice placement on water cards.
 * Ships can also be obtained by spending 1 Blue Influence (once per turn).
 * When queued as an operation (e.g., from a townsfolk card), it provides
 * a temporary ship asset for the current dice placement.
 */
class Op_ship extends Operation {
    public function isVoid(): bool {
        return false;
    }

    function getPossibleMoves() {
        return ["confirm"];
    }

    function resolve(): void {
        // Ship asset is consumed automatically when used for dice placement
        $this->notifyMessage(clienttranslate('${player_name} uses a Ship'));
    }

    public function getPrompt() {
        return clienttranslate('Use Ship asset');
    }

    public function getIconicName() {
        return "[wicon_ship]";
    }
}
