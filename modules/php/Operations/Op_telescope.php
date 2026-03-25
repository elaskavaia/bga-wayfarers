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
 * Telescope asset - provides telescope asset for dice placement.
 * Telescope is used for observatory/space-related cards.
 * The caravan starts with a telescope in column 6 (die value 6).
 * When queued as an operation (e.g., from a townsfolk card), it provides
 * a temporary telescope asset for the current dice placement.
 */
class Op_telescope extends Operation {
    public function isVoid(): bool {
        return false;
    }

    function getPossibleMoves() {
        return ["confirm"];
    }

    function resolve(): void {
        // Telescope asset is consumed automatically when used for dice placement
        $this->notifyMessage(clienttranslate('${player_name} uses a Telescope'));
    }

    public function getPrompt() {
        return clienttranslate('Use Telescope asset');
    }

}
