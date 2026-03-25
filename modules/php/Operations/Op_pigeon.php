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
 * Pigeon asset - provides pigeon asset for dice placement.
 * Pigeons are typically used for cards that require communication/messaging abilities.
 * When queued as an operation (e.g., from a townsfolk card), it provides
 * a temporary pigeon asset for the current dice placement.
 */
class Op_pigeon extends Operation {
    public function isVoid(): bool {
        return false;
    }

    function getPossibleMoves() {
        return ["confirm"];
    }

    function resolve(): void {
        // Pigeon asset is consumed automatically when used for dice placement
        $this->notifyMessage(clienttranslate('${player_name} uses a Pigeon'));
    }

    public function getPrompt() {
        return clienttranslate('Use Pigeon asset');
    }

}
