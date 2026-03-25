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
 * Camel asset - provides camel asset for dice placement on land cards.
 * When queued as an operation (e.g., from a townsfolk card), it provides
 * a temporary camel asset for the current dice placement.
 */
class Op_camel extends Operation {
    public function isVoid(): bool {
        // Camel is an asset, not an action - it's always valid when queued
        return false;
    }

    function getPossibleMoves() {
        return ["confirm"];
    }

    function resolve(): void {
        // Camel asset is consumed automatically when used for dice placement
        // This operation is typically queued to provide a temporary camel from townsfolk
        $this->notifyMessage(clienttranslate('${player_name} uses a Camel'));
    }

    public function getPrompt() {
        return clienttranslate('Use Camel asset');
    }

}
